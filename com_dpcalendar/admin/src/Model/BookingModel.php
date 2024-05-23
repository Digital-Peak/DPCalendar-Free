<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\AdjustCustomFields;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\AssignUserGroups;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\CollectEventsAndTickets;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\CreateOrUpdateTickets;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\CreateUser;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\FetchLocationData;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SendInviteMail;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SendNewBookingMail;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SendNotificationMail;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SendPaidBookingMail;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SendWaitingListMail;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SetupForMail;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SetupForNew;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SetupForUpdate;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SetupLanguage;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use DigitalPeak\Component\DPCalendar\Administrator\Table\BookingTable;
use DigitalPeak\Component\DPCalendar\Administrator\Table\EventTable;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryAwareInterface;
use Joomla\CMS\Mail\MailerFactoryAwareTrait;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use League\Pipeline\PipelineBuilder;

class BookingModel extends AdminModel implements MailerFactoryAwareInterface, UserFactoryAwareInterface
{
	use MailerFactoryAwareTrait;
	use UserFactoryAwareTrait;

	private array $events = [];

	public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'id',
				'a.id',
				'uid',
				'a.uid',
				'name',
				'a.name',
				'state',
				'a.state',
				'price',
				'a.price',
				'booking_name',
				'book_date'
			];
		}

		parent::__construct($config, $factory, $formFactory);
	}

	/**
	 * Saves the given data. If internal is set to true, no safety overrides like user id are performed.
	 */
	public function save($data, ?bool $internal = false)
	{
		$app = Factory::getApplication();

		/** @var MVCFactoryInterface $factory */
		$factory = $this->bootComponent('dpcalendar')->getMVCFactory();

		$user = $this->getCurrentUser();

		$payload                    = new \stdClass();
		$payload->data              = $data;
		$payload->eventsWithTickets = [];
		$payload->events            = [];
		$payload->tickets           = [];
		$payload->oldItem           = array_key_exists('id', $data) && $data['id'] ? $this->getItem($payload->data['id']) : null;

		$token = $app->getInput()->get('token');
		if (!$payload->oldItem && $token) {
			$payload->oldItem = $this->getItem(['token' => $token]);
		}

		PluginHelper::importPlugin('dpcalendar');
		PluginHelper::importPlugin('dpcalendarpay');

		$builder = new PipelineBuilder();
		$builder->add(new CollectEventsAndTickets($app, $this));
		$builder->add(
			new FetchLocationData(
				$factory->createModel('Country', 'Administrator', ['ignore_request' => true]),
				$factory->createModel('Geo', 'Administrator', ['ignore_request' => true])
			)
		);

		if (!$payload->oldItem) {
			$builder->add(new SetupForNew(
				$app,
				$user,
				$factory->createModel('Taxrate', 'Administrator', ['ignore_request' => true]),
				$factory->createModel('Coupon', 'Administrator', ['ignore_request' => true]),
				$this->getParams(),
				$app->isClient('site') && $internal !== true
			));
		} else {
			$builder->add(new SetupForUpdate(
				$app,
				$user,
				$this,
				$factory->createModel('Coupon', 'Administrator', ['ignore_request' => true])
			));
		}

		$builder->build()($payload);

		$success = parent::save($payload->data);
		if (!$success) {
			return $success;
		}

		// Set up id for payment system
		$id = $this->getState($this->getName() . '.id');
		$app->getInput()->set('b_id', $id);

		if (!$token && $app instanceof CMSWebApplicationInterface) {
			// Set the session only when a token is not available to not mix up permissions
			$app->getSession()->set('com_dpcalendar.booking_id', $id);
		}

		$payload->item = $this->getItem($token ? ['token' => $token] : null);

		// When on a payment provider callback, eg. PayPal, then it can have a token attribute as well
		if (!$payload->item) {
			$payload->item = $this->getItem();
		}

		// If the user is not an admin, the getItem function returns null as the booking doesn't belong to it
		if ($internal && !$payload->item) {
			$payload->item = $this->getTable();
			$payload->item->load($id);
		}

		$builder = new PipelineBuilder();
		$builder->add(new SetupLanguage($app, $this->getUserFactory()));
		$builder->add(new AdjustCustomFields());
		$builder->add(new CreateUser($app, $this->getDatabase(), $this->bootComponent('users')->getMVCFactory()->createModel('Registration', 'Site', ['ignore_request' => true])));
		$builder->add(new AssignUserGroups());
		$builder->add(new CreateOrUpdateTickets($factory->createModel('Ticket', 'Administrator', ['ignore_request' => true])));
		$builder->add(new SetupForMail($app, $this->getParams()));
		$builder->add(new SendNewBookingMail($this->getMailerFactory()->createMailer(), $app, $this->getUserFactory()));
		$builder->add(new SendPaidBookingMail($this->getMailerFactory()->createMailer(), $this->getUserFactory()));
		$builder->add(new SendNotificationMail($this->getMailerFactory()->createMailer(), $this->getUserFactory()));
		$builder->add(new SendWaitingListMail($this->getMailerFactory()->createMailer(), $this->getUserFactory()));
		$builder->add(new SendInviteMail($this->getMailerFactory()->createMailer()));

		$builder->build()($payload);

		$app->triggerEvent('onDPCalendarAfterBookingFinished', [$payload->item, $payload->tickets]);

		return $success;
	}

	public function publish(&$pks, $value = 1, ?string $token = null)
	{
		$bookings = [];
		foreach ((array)$pks as $bookingId) {
			$bookings[$bookingId] = $this->getItem($bookingId) ?: ($token !== null && $token !== '' && $token !== '0' ? $this->getItem(['token' => $token]) : false);

			// Check if booking can be cancelled
			if ($value != 6) {
				continue;
			}

			if (Booking::openForCancel($bookings[$bookingId] ?: new \stdClass())) {
				continue;
			}

			return false;
		}

		PluginHelper::importPlugin('dpcalendar');
		PluginHelper::importPlugin('dpcalendarpay');

		$success = parent::publish($pks, $value);
		if (!$success) {
			return $success;
		}

		foreach ($bookings as $booking) {
			if (empty($booking)) {
				continue;
			}

			foreach ($booking->tickets as $ticket) {
				$this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ticket', 'Administrator')->publish($ticket->id, $value);
			}

			$builder = new PipelineBuilder();
			$builder->add(new CollectEventsAndTickets(
				Factory::getApplication(),
				$this
			));
			$builder->add(new SetupForMail(Factory::getApplication(), $this->getParams()));
			$builder->add(new SendNotificationMail($this->getMailerFactory()->createMailer(), $this->getUserFactory()));
			$builder->build()(
				(object)[
				'oldItem' => $booking,
				'item'    => $this->getItem($booking->id) ?: ($token !== null && $token !== '' && $token !== '0' ? $this->getItem(['token' => $token]) : null)]
			);
		}

		return $success;
	}

	public function delete(&$pks)
	{
		PluginHelper::importPlugin('dpcalendar');
		PluginHelper::importPlugin('dpcalendarpay');

		$success = parent::delete($pks);
		if (!$success) {
			return $success;
		}

		foreach ((array)$pks as $pk) {
			foreach ($this->getTickets($pk, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, -2]) as $ticket) {
				$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ticket', 'Administrator');
				$model->delete($ticket->id);
			}
		}

		return $success;
	}

	protected function canEditState($record): bool
	{
		if (parent::canEditState($record)) {
			return true;
		}

		$app = Factory::getApplication();

		if (!empty($record->id)) {
			// Check public bookings
			if (!$this->getCurrentUser()->id
				&& $app instanceof CMSWebApplicationInterface && $app->getSession()->get('com_dpcalendar.booking_id', 0) == $record->id) {
				return true;
			}

			if ($this->getCurrentUser()->id && !empty($record->user_id) && $this->getCurrentUser()->id == $record->user_id) {
				return true;
			}
		}

		return !empty($record->token) && $record->token === $app->getInput()->get('token');
	}

	protected function canDelete($record): bool
	{
		if (parent::canDelete($record)) {
			return true;
		}

		$app = Factory::getApplication();

		if (!empty($record->id)) {
			// Check public bookings
			if (!$this->getCurrentUser()->id
				&& $app instanceof CMSWebApplicationInterface && $app->getSession()->get('com_dpcalendar.booking_id', 0) == $record->id) {
				return true;
			}

			if ($this->getCurrentUser()->id && !empty($record->user_id) && $this->getCurrentUser()->id == $record->user_id) {
				return true;
			}
		}

		return !empty($record->token) && $record->token === $app->getInput()->get('token');
	}

	public function getTable($type = 'Booking', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');

		$form = $this->loadForm('com_dpcalendar.booking', 'booking', ['control' => 'jform', 'load_data' => $loadData]);

		if (!DPCalendarHelper::isCaptchaNeeded()) {
			$form->removeField('captcha');
		}

		$item = $this->getItem(empty($data['id']) ? null : $data['id']);

		$user     = $this->getCurrentUser();
		$isAuthor = array_filter(empty($item->tickets) ? [] : $item->tickets, static fn ($ticket): bool => !$user->guest && $user->id == $ticket->event_author) !== [];
		if (!$isAuthor && !$user->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			$form->removeField('latitude');
			$form->removeField('longitude');
			$form->removeField('price');
			$form->removeField('state');
		}

		$form->removeField('transaction_id');
		$form->removeField('type');
		$form->removeField('payer_email');

		$this->modifyField($form, 'country');
		$this->modifyField($form, 'province');
		$this->modifyField($form, 'city');
		$this->modifyField($form, 'zip');
		$this->modifyField($form, 'street');
		$this->modifyField($form, 'number');
		$this->modifyField($form, 'telephone');

		$eventIds = $data['event_id'] ?? [];
		if (empty($eventIds) && $item && !empty($item->tickets)) {
			$eventIds = [];
			foreach ($item->tickets as $ticket) {
				$eventIds[$ticket->event_id] = [];
			}
		}

		if ($eventIds && $item) {
			// Clear the cache, doggy
			$reflection = new \ReflectionProperty(FieldsHelper::class, 'fieldsCache');
			$reflection->setAccessible(true);

			foreach ($eventIds as $eventId => $requestData) {
				$event = $this->getEvent($eventId);

				$item->catid = $event->catid;

				$reflection->setValue(null, null);
				$itemFields = FieldsHelper::getFields('com_dpcalendar.booking', $item);

				$reflection->setValue(null, null);
				foreach (FieldsHelper::getFields('com_dpcalendar.booking') as $field) {
					$has = array_filter($itemFields, static fn ($f): bool => $f->id == $field->id);

					if ($has !== []) {
						continue;
					}

					$form->removeField($field->name, 'com_fields');
				}
				break;
			}
		}

		return $form;
	}

	private function modifyField(Form $form, string $name): void
	{
		$app    = Factory::getApplication();
		$params = $this->getState('params');
		if (!$params) {
			$params = ComponentHelper::getParams('com_dpcalendar');

			if ($app instanceof SiteApplication) {
				$params = $app->getParams();
			}
		}

		$state = $params->get('booking_form_' . $name, 1);
		switch ($state) {
			case 0:
				if ($app instanceof SiteApplication) {
					$form->removeField($name);
				}
				break;
			case 2:
				$form->setFieldAttribute($name, 'required', 'true');
				break;
		}
	}

	protected function loadFormData()
	{
		$app = Factory::getApplication();

		$data = $app instanceof CMSWebApplicationInterface ?
			$app->getUserState('com_dpcalendar.edit.booking.data', $app->getUserState('com_dpcalendar.edit.bookingform.data', [])) : [];

		$data = empty($data) ? $this->getItem() : (object)$data;

		if ($data === false || !$data->id) {
			$data = $this->getTable();
			$data->check();
			$data->id = 0;
			$data     = (object)$data->getData();
		}

		// If no booking is found load the form with some old data
		if (!$data->id && !$this->getCurrentUser()->guest) {
			$this->getDatabase()
				->setQuery('select id from #__dpcalendar_bookings where user_id = ' . $this->getCurrentUser()->id . ' order by id desc limit 1');
			$row = $this->getDatabase()->loadAssoc();
			if ($row) {
				$tmp            = get_object_vars($data);
				$data           = $this->getItem($row['id']) ?: new \stdClass();
				$data->id       = null;
				$data->event_id = null;
				$data->state    = null;

				foreach ($tmp as $key => $value) {
					if ($value) {
						$data->{$key} = $value;
					}
				}
			}
		}

		foreach ($this->getDefaultValues($data) as $key => $value) {
			$data->{$key} = $value;
		}

		$this->preprocessData('com_dpcalendar.booking', $data);

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	public function getReturnPage(): string
	{
		return base64_encode((string)($this->getState('return_page', '') ?: Uri::base(true)));
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->getInput()->getInt('b_id', 0);
		$this->setState('booking.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->getInput()->get('return', null, 'base64');
		if (!$return || !Uri::isInternal(base64_decode((string)$return))) {
			$return = null;
		}

		$this->setState('return_page', $return ? base64_decode((string)$return) : $return);

		$params = $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');
		$this->harmonizeParams($params);
		$this->setState('params', $params);
	}

	/**
	 * Returns the booking id which is assigned to the given user.
	 * If none is assigned it returns false.
	 */
	public function assign(array $user): ?BookingTable
	{
		$app = Factory::getApplication();

		$bookingFromSession = $app instanceof CMSWebApplicationInterface ? $app->getSession()->get('com_dpcalendar.booking_id', 0) : 0;
		if (!$bookingFromSession) {
			return null;
		}

		$u = ArrayHelper::toObject($user);
		if (empty($u->id)) {
			return null;
		}

		$booking = $this->getTable();
		$booking->load($bookingFromSession);

		$booking->user_id = $u->id;
		$booking->store();

		foreach ($this->getTickets($bookingFromSession) as $ticket) {
			$t = $this->getTable('Ticket');
			$t->load($ticket->id);
			$t->user_id = $u->id;
			$t->store();
		}

		if ($app instanceof CMSWebApplicationInterface) {
			$app->getSession()->set('com_dpcalendar.booking_id', 0);
		}

		return $booking;
	}

	/**
	 * @param int|array $pk
	 */
	public function getItem($pk = null)
	{
		// Unset token parameter when empty to prevent empty string comparison with null
		if (is_array($pk) && empty($pk['token'])) {
			unset($pk['token']);
		}

		$item = parent::getItem($pk);
		if (!$item || !$item->id) {
			return $item;
		}

		if ($item->price == '0.00') {
			$item->price = 0;
		}

		$app = Factory::getApplication();

		if (!empty($item->country)) {
			$country = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator', ['ignore_request' => true])->getItem($item->country);
			if ($country) {
				$app->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
				$item->country_code       = $country->short_code;
				$item->country_code_value = Text::_('COM_DPCALENDAR_COUNTRY_' . $country->short_code);
			}
		}

		if (!empty($item->coupon_id)) {
			$coupon = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Coupon', 'Administrator', ['ignore_state' => true])->getItem($item->coupon_id);
			if ($coupon && $coupon->id) {
				$item->coupon_id = $coupon->code;
			}
		}

		$token = is_array($pk) && !empty($pk['token']) ? $pk['token'] : null;

		$user          = $this->getCurrentUser();
		$item->tickets = $this->getTickets($item->id, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, -2], $token);
		$isAuthor      = array_filter($item->tickets, static fn ($ticket): bool => !$user->guest && $user->id == $ticket->event_author) !== [];

		$params = new Registry();
		$params->set(
			'access-edit',
			in_array($item->state, [0, 2, 3, 5]) && ($user->id == $item->user_id || $token)
		);
		$params->set(
			'access-delete',
			in_array($item->state, [0, 1, 2, 3, 5]) && ($user->id == $item->user_id || $token)
		);

		// Deny delete when booking has a price or cancel date is passed
		if ($item->price || !Booking::openForCancel($item, [0, 1, 2, 3, 5])) {
			$params->set('access-delete', false);
		}

		// If user is author or admin allow
		if ($isAuthor || $user->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			$params->set('access-edit', true);
			$params->set('access-delete', true);
		}

		$item->params = $params;

		// When the user is not a guest, but an author, owner or admin then allow
		if (!$user->guest && ($isAuthor || $user->id == $item->user_id || $user->authorise('dpcalendar.admin.book', 'com_dpcalendar'))) {
			return $item;
		}

		// When the item is fetched by the token accept it
		if ($token) {
			return $item;
		}

		if (!$app instanceof CMSWebApplicationInterface) {
			return $item;
		}

		// If the user is a guest and has the booking in the session then allow
		$bookingFromSession = $app->getSession()->get('com_dpcalendar.booking_id', 0);
		if ($user->guest && $bookingFromSession == $item->id) {
			// Do not allow to edit
			$params->set('access-edit', false);
			$params->set('access-delete', false);

			return $item;
		}

		return false;
	}

	public function getEvent(string $eventId = null, bool $force = false): EventTable
	{
		if ($eventId == null) {
			$eventId = Factory::getApplication()->getInput()->get('e_id');
		}

		if (!isset($this->events[$eventId]) || $force) {
			$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Site');
			$event = $this->getTable('Event', 'Administrator');

			if ($e = $model->getItem($eventId)) {
				$event->bind($e);
				$event->tickets   = $e->tickets;
				$event->locations = $e->locations;
			}

			if (empty($event->tickets)) {
				$event->tickets = [];
			}

			$this->events[$eventId] = $event;
		}

		return $this->events[$eventId];
	}

	public function getTickets(int $bookingId, array $state = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], ?string $token = null): array
	{
		if ($token === null) {
			$token = Factory::getApplication()->getInput()->get('token');
		}

		$ticketsModel = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Tickets', 'Administrator', ['ignore_request' => true]);
		$ticketsModel->setState('filter.booking_id', $bookingId);
		$ticketsModel->setState('filter.state', $state);
		$ticketsModel->setState('list.limit', 10000);

		if ($token) {
			$ticketsModel->setState('filter.ticket_holder', false);
		}

		return $ticketsModel->getItems();
	}

	public function getProfileData(int $id): array
	{
		$query = $this->getDatabase()->getQuery(true);
		$query->select('profile_key, profile_value')->from('#__user_profiles')
			->where('user_id = ' . $id)->where("profile_key LIKE 'profile.%'")->order('ordering');
		$this->getDatabase()->setQuery($query);

		return $this->getDatabase()->loadRowList();
	}

	/**
	 * @param \stdClass $item
	 */
	private function getDefaultValues($item): array
	{
		$params = $this->getParams();
		$app    = Factory::getApplication();
		$data   = [];

		// Set the default values from the params
		if (!$item->country) {
			$data['country'] = $params->get('booking_form_default_country');
		}

		if (!$app instanceof SiteApplication || $this->getCurrentUser()->guest) {
			return $data;
		}

		$userId = $this->getCurrentUser()->id;

		$contact = $app->bootComponent('contact')->getMVCFactory()->createTable('Contact', 'Administrator');
		$contact->load(['user_id' => $userId]);
		if ($contact->id && !$item->country) {
			$data['country'] = $contact->country;
		}
		if ($contact->id && !$item->province) {
			$data['province'] = $contact->state;
		}
		if ($contact->id && !$item->city) {
			$data['city'] = $contact->suburb;
		}
		if ($contact->id && !$item->zip) {
			$data['zip'] = $contact->postcode;
		}
		if ($contact->id && !$item->street) {
			$data['street'] = $contact->address;
		}
		if ($contact->id && !$item->telephone) {
			$data['telephone'] = $contact->telephone;
		}

		$this->getDatabase()->setQuery(
			'SELECT profile_key, profile_value FROM #__user_profiles WHERE user_id = ' . (int)$userId . " AND profile_key LIKE 'profile.%'"
			. ' ORDER BY ordering'
		);

		foreach ($this->getDatabase()->loadRowList() as $profileData) {
			$value = json_decode((string)$profileData[1]);
			switch ($profileData[0]) {
				case 'profile.address1':
				case 'profile.address2':
					if ($value && !$item->street) {
						$data['street'] = $value;
					}
					break;
				case 'profile.city':
					if ($value && !$item->city) {
						$data['city'] = $value;
					}
					break;
				case 'profile.postal_code':
					if ($value && !$item->zip) {
						$data['zip'] = $value;
					}
					break;
				case 'profile.region':
					if ($value && !$item->province) {
						$data['province'] = $value;
					}
					break;
				case 'profile.phone':
					if ($value && !$item->telephone) {
						$data['telephone'] = $value;
					}
					break;
			}
		}

		return $data;
	}

	private function getParams(): Registry
	{
		$app    = Factory::getApplication();
		$params = $this->getState('params');
		if (!$params && $app instanceof SiteApplication) {
			$params = $app->getParams();
		}

		if (!$params) {
			$params = ComponentHelper::getParams('com_dpcalendar');
		}

		$this->harmonizeParams($params);

		return $params;
	}

	private function harmonizeParams(Registry $params): void
	{
		if (!$params->get('booking_form_fields_order_')) {
			$params->set(
				'booking_form_fields_order_',
				DPCalendarHelper::getComponentParameter('booking_form_fields_order_', new \stdClass())
			);
		}
		if (!$params->get('booking_fields_order')) {
			$params->set(
				'booking_fields_order',
				DPCalendarHelper::getComponentParameter('booking_fields_order', new \stdClass())
			);
		}
		if (!$params->get('ticket_form_fields_order_')) {
			$params->set(
				'ticket_form_fields_order_',
				DPCalendarHelper::getComponentParameter('ticket_form_fields_order_', new \stdClass())
			);
		}
		if (!$params->get('ticket_fields_order')) {
			$params->set(
				'ticket_fields_order',
				DPCalendarHelper::getComponentParameter('ticket_fields_order', new \stdClass())
			);
		}
	}
}
