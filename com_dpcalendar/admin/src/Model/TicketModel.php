<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DateHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Location;
use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerFactoryAwareInterface;
use Joomla\CMS\Mail\MailerFactoryAwareTrait;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class TicketModel extends AdminModel implements MailerFactoryAwareInterface, UserFactoryAwareInterface
{
	use MailerFactoryAwareTrait;
	use UserFactoryAwareTrait;

	/**
	 * Flag when multiple tickets are saved but only one captcha can be rendered.
	 *
	 * @var bool
	 */
	public $ignoreCaptcha = false;

	public function save($data, bool $sendTicket = true)
	{
		$data = $this->fetchGeoInformation($data);

		$oldItem = null;
		if (!empty($data['id'])) {
			$oldItem = $this->getItem($data['id']);
		}

		$success = parent::save($data);
		if (!$success) {
			return $success;
		}

		if (!empty($oldItem->id) && !in_array($oldItem->state, [1, 4, 9]) && in_array($data['state'], [1, 4, 9])) {
			$this->getTable('Event')->book(true, $oldItem->event_id);
		}

		if (!empty($oldItem->id) && in_array($oldItem->state, [1, 4, 9]) && !in_array($data['state'], [1, 4, 9])) {
			$this->getTable('Event')->book(false, $oldItem->event_id);
		}

		if (!$sendTicket) {
			return $success;
		}

		$ticket = $this->getItem($this->getState($this->getName() . '.id'));
		if ($ticket === false) {
			return $success;
		}

		$event = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator')->getItem($ticket->event_id);
		if ($event === false) {
			return $success;
		}

		$user = $this->getUserFactory()->loadUserById($event->created_by ?: 0);
		if (empty($user->email)) {
			return $success;
		}

		// Create the ticket details for mail notification
		$params = clone ComponentHelper::getParams('com_dpcalendar');
		$params->set('show_header', false);

		$details = DPCalendarHelper::renderLayout(
			'ticket.details',
			[
				'ticket'     => $ticket,
				'event'      => $event,
				'translator' => new Translator(),
				'dateHelper' => new DateHelper(),
				'params'     => $params
			]
		);

		$additionalVars = [
			'ticketDetails' => $details,
			'ticketLink'    => RouteHelper::getTicketRoute($ticket, true),
			'ticketUid'     => $ticket->uid,
			'sitename'      => Factory::getApplication()->get('sitename'),
			'user'          => $this->getCurrentUser()->name
		];

		Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$subject = DPCalendarHelper::renderEvents([$event], Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT_EDIT'));
		$body    = DPCalendarHelper::renderEvents(
			[$event],
			Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_TICKET_BODY'),
			null,
			$additionalVars
		);

		// Send to the author
		$mailer = $this->getMailerFactory()->createMailer();
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->addRecipient($user->email);
		if ($mailer instanceof Mail) {
			$mailer->IsHTML(true);
		}
		$mailer->Send();

		// Send to the ticket holder
		$mailer = $this->getMailerFactory()->createMailer();
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		if ($mailer instanceof Mail) {
			$mailer->IsHTML(true);
		}
		$mailer->addRecipient($ticket->email);

		// Attache the new ticket
		$params->set('show_header', true);
		$fileName = Booking::createTicket($ticket, $params, true);
		if ($fileName !== '' && $fileName !== '0' && $fileName !== null) {
			$mailer->addAttachment($fileName);
		}

		$mailer->Send();
		if ($fileName !== null && file_exists($fileName)) {
			unlink($fileName);
		}

		return $success;
	}

	protected function canDelete($record): bool
	{
		if (parent::canDelete($record)) {
			return true;
		}

		if (empty($record->booking_id)) {
			return false;
		}

		if (!empty($record->user_id) && $record->user_id == $this->getCurrentUser()->id) {
			return true;
		}

		$booking = $this->getTable('Booking');
		$booking->load($record->booking_id);

		return $booking->token === Factory::getApplication()->getInput()->get('token');
	}

	public function publish(&$pks, $value = 1)
	{
		$pks = (array)$pks;

		$oldTickets = [];
		foreach ($pks as $pk) {
			$oldTickets[$pk] = $this->getItem($pk);
		}

		$success = parent::publish($pks, $value);
		if (!$success) {
			return $success;
		}

		$activeStates = [1, 4];
		foreach ($pks as $pk) {
			if ($oldTickets[$pk] === false || $value == $oldTickets[$pk]->state) {
				continue;
			}

			if (in_array($value, $activeStates) && !in_array($oldTickets[$pk]->state, $activeStates)) {
				$this->getTable('Event')->book(true, $oldTickets[$pk]->event_id);
			}
			if (in_array($value, $activeStates)) {
				continue;
			}
			if (!in_array($oldTickets[$pk]->state, $activeStates)) {
				continue;
			}
			$this->getTable('Event')->book(false, $oldTickets[$pk]->event_id);
		}

		return $success;
	}

	public function delete(&$pks)
	{
		$pks = (array)$pks;

		$events = [];
		foreach ($pks as $pk) {
			$ticket = $this->getItem($pk);

			// Only decrement when an active ticket
			if ($ticket === false || !in_array($ticket->state, [1, 4])) {
				continue;
			}

			$event = $this->getTable('Event');
			$event->load($ticket->event_id);
			$events[] = $event;
		}

		$success = parent::delete($pks);
		if (!$success) {
			return $success;
		}

		foreach ($events as $event) {
			$event->book(false);
		}

		return $success;
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);
		if (!$item) {
			return $item;
		}

		$user   = $this->getCurrentUser();
		$params = new Registry();
		$params->set(
			'access-edit',
			in_array($item->state, [0, 2, 3, 5]) && $user->id == $item->user_id
		);
		$params->set(
			'access-delete',
			in_array($item->state, [0, 1, 2, 3, 5]) && $user->id == $item->user_id
		);

		// Do not allow to delete paid bookings
		$booking = $this->getTable('Booking');
		$booking->load($item->booking_id);
		if ($booking->price) {
			$params->set('access-delete', false);
		}
		$item->booking_token = $booking->token;

		// If user is event author or admin allow to edit
		$event = $this->getTable('Event');
		$event->load($item->event_id);
		if ($event->id) {
			$item->event_calid            = $event->catid;
			$item->event_title            = $event->title;
			$item->start_date             = $event->start_date;
			$item->end_date               = $event->end_date;
			$item->all_day                = $event->all_day;
			$item->show_end_time          = $event->show_end_time;
			$item->event_prices           = $event->price;
			$item->event_options          = $event->booking_options;
			$item->event_payment_provider = $event->payment_provider;
			$item->event_terms            = $event->terms;
			$item->event_author           = $event->created_by;
			$item->event_original_id      = $event->original_id;
			$item->event_rrule            = $event->rrule;
		}

		if (!$user->guest && ($user->id == $event->created_by
			|| $user->authorise('dpcalendar.admin.book', 'com_dpcalendar' . (empty($event->catid) ? '' : '.category.' . $event->catid)))) {
			$params->set('access-edit', true);
		}

		$app = Factory::getApplication();

		$bookingFromSession = $app instanceof CMSWebApplicationInterface ? $app->getSession()->get('com_dpcalendar.booking_id', 0) : 0;
		if ($user->guest && $bookingFromSession != $item->booking_id) {
			$params->set('access-edit', false);
			$params->set('access-delete', false);
		}
		$item->params = $params;

		if ($item->country) {
			$country = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator')->getItem($item->country);
			if ($country) {
				$app->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
				$item->country_code       = $country->short_code;
				$item->country_code_value = Text::_('COM_DPCALENDAR_COUNTRY_' . $country->short_code);
			}
		}

		if ($item->price == '0.00') {
			$item->price = 0;
		}

		$item->type_label = '';
		if (!empty($event->price) && is_string($event->price)) {
			$prices = json_decode((string)$event->price);
			if (!empty($prices->label) && !empty($prices->label[$item->type])) {
				$item->type_label = $prices->label[$item->type];
			}
		}

		return $item;
	}

	public function getTable($type = 'Ticket', $prefix = 'Administrator', $config = [])
	{
		$table = parent::getTable($type, $prefix, $config);
		$table->check();

		return $table;
	}

	protected function canEditState($record): bool
	{
		if (parent::canEditState($record) || empty($record->booking_id)) {
			return true;
		}

		if (!empty($record->user_id) && $record->user_id == $this->getCurrentUser()->id) {
			return true;
		}

		$booking = $this->getTable('Booking');
		$booking->load($record->booking_id);

		return $booking->token === Factory::getApplication()->getInput()->get('token');
	}

	public function getForm($data = [], $loadData = true)
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');

		$form = $this->loadForm('com_dpcalendar.ticket', 'ticket', ['control' => 'jform', 'load_data' => $loadData]);

		$item = $this->getItem(empty($data['id']) ? null : $data['id']);

		$user = $this->getCurrentUser();

		$event = $item ? $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator')->getItem($item->event_id) : null;

		if ((!$event || $user->id != $event->created_by)
			&& !$user->authorise('dpcalendar.admin.book', 'com_dpcalendar' . ($event ? '.category.' . $event->catid : ''))) {
			$form->removeField('latitude');
			$form->removeField('longitude');
			$form->removeField('price');
			$form->removeField('state');
		}

		$form->setFieldAttribute('event_id', 'disabled', 'true');
		$form->setFieldAttribute('booking_id', 'disabled', 'true');

		if (!DPCalendarHelper::isCaptchaNeeded() || $this->getState('captcha.disabled')) {
			$form->removeField('captcha');
		}

		if (Factory::getApplication()->isClient('site')) {
			$form->removeField('user_id');
			$form->removeField('type');
		}

		$this->modifyField($form, 'country');
		$this->modifyField($form, 'province');
		$this->modifyField($form, 'city');
		$this->modifyField($form, 'zip');
		$this->modifyField($form, 'street');
		$this->modifyField($form, 'number');
		$this->modifyField($form, 'telephone');
		$this->modifyField($form, 'public');

		return $form;
	}

	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = '')
	{
		// Handle the optional arguments.
		$options['control'] = ArrayHelper::getValue((array)$options, 'control', false);

		// Create a signature hash. But make sure, that loading the data does not create a new instance
		$sigoptions = $options;

		if (isset($sigoptions['load_data'])) {
			unset($sigoptions['load_data']);
		}

		$hash = md5($source . serialize($sigoptions));

		// Check if we can use a previously loaded form.
		if (!$clear && isset($this->_forms[$hash])) {
			return $this->_forms[$hash];
		}

		// Get the form.
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/model/form');
		Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/model/field');

		$source = trim($source ?: '');

		if ($source === '' || $source === '0') {
			throw new \InvalidArgumentException(sprintf('%1$s(%2$s, *%3$s*)', __METHOD__, $name, gettype($source)));
		}

		// Instantiate the form.
		$form = new Form($name, $options);

		// Load the data.
		if (str_starts_with($source, '<')) {
			if ($form->load($source, false, $xpath) == false) {
				throw new \RuntimeException(sprintf('%s() could not load form', __METHOD__));
			}
		} elseif ($form->loadFile($source, false, $xpath) == false) {
			throw new \RuntimeException(sprintf('%s() could not load file', __METHOD__));
		}

		$data = isset($options['load_data']) && $options['load_data'] ? $this->loadFormData() : [];

		// Allow for additional modification of the form, and events to be triggered.
		// We pass the data because plugins may require it.
		$this->preprocessForm($form, $data);

		// Load the data into the form after the plugins have operated.
		$form->bind($data);

		return $form;
	}

	private function modifyField(Form $form, string $name): void
	{
		$app = Factory::getApplication();

		$params = $this->getState('params');
		if (!$params) {
			$params = ComponentHelper::getParams('com_dpcalendar');

			if ($app instanceof SiteApplication) {
				$params = $app->getParams();
			}
		}

		$state = $params->get('ticket_form_' . $name, 1);
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
		$app  = Factory::getApplication();
		$data = $app instanceof CMSWebApplicationInterface ? $app->getUserState('com_dpcalendar.edit.ticket.data', []) : [];

		if (empty($data)) {
			$data = $this->getItem();
		}

		if (is_array($data) && !empty($data['event_calid'])) {
			$data['catid'] = $data['event_calid'];
		}

		if (is_object($data) && !empty($data->event_calid)) {
			// @phpstan-ignore-next-line
			$data->catid = $data->event_calid;
		}

		$this->preprocessData('com_dpcalendar.ticket', $data);

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	public function getReturnPage(): string
	{
		return base64_encode((string)($this->getState('return_page', '') ?: Uri::base(true)));
	}

	/**
	 * @param \stdClass $ticket
	 * @param ?\stdClass $event
	 */
	public function sendCertificate($ticket, $event = null): void
	{
		// Check if ticket is checked in
		if ($ticket->state != 9 || $ticket->certificate_send_date !== null) {
			return;
		}

		if (!$event || !$event->id) {
			$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator', ['ignore_request' => true]);
			$event = $model->getItem($ticket->event_id);
		}

		if (!$event || !$event->id) {
			return;
		}

		// Create the ticket details for mail notification
		$params = clone ComponentHelper::getParams('com_dpcalendar');
		$params->set('show_header', false);

		$details = DPCalendarHelper::renderLayout(
			'ticket.details',
			[
				'ticket'     => $ticket,
				'event'      => $event,
				'translator' => new Translator(),
				'dateHelper' => new DateHelper(),
				'params'     => $params
			]
		);

		$additionalVars = [
			'ticketDetails' => $details,
			'ticketLink'    => RouteHelper::getTicketRoute($ticket, true),
			'ticketUid'     => $ticket->uid,
			'sitename'      => Factory::getApplication()->get('sitename'),
			'user'          => $this->getCurrentUser()->name
		];

		$subject = DPCalendarHelper::renderEvents([$event], Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_TICKET_CHECKED_IN_SUBJECT'));
		$body    = DPCalendarHelper::renderEvents(
			[$event],
			Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_TICKET_CHECKED_IN_BODY'),
			null,
			$additionalVars
		);

		// Send to the ticket holder
		$mailer = $this->getMailerFactory()->createMailer();
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->addRecipient($ticket->email);
		if ($mailer instanceof Mail) {
			$mailer->IsHTML(true);
		}

		// Attach the certificate
		$fileName = Booking::createCertificate($ticket, $params, true);
		if ($fileName !== '' && $fileName !== '0' && $fileName !== null) {
			$mailer->addAttachment($fileName);
		}

		$mailer->Send();

		if ($fileName !== null && file_exists($fileName)) {
			unlink($fileName);
		}

		$query = $this->getDatabase()->getQuery(true);
		$query->update('#__dpcalendar_tickets')
			->set('certificate_send_date = ' . $this->getDatabase()->quote(DPCalendarHelper::getDate()->toSql()))
			->where('id = ' . $ticket->id);
		$this->getDatabase()->setQuery($query)->execute();
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->getInput()->getInt('t_id', 0);
		$this->setState('ticket.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->getInput()->get('return', '', 'base64');
		if (!Uri::isInternal(base64_decode((string)$return))) {
			$return = '';
		}

		$this->setState('return_page', base64_decode((string)$return));
		$this->setState('captcha.disabled', false);

		$params = $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');
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
		$this->setState('params', $params);
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	private function fetchGeoInformation(array $data)
	{
		// Refetch geo information when location has changed
		if (!$data['id'] || !empty($data['latitude'])) {
			return $data;
		}

		$oldItem = $this->getItem($data['id']);

		$locationData = ArrayHelper::toObject($data);
		if (!empty($locationData->country)) {
			$table = $this->getTable('Country');
			$table->load($locationData->country);
			$locationData->country = $table->short_code;
		}

		// Fetch the latitude/longitude if location has changed
		$location    = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format([$locationData]);
		$oldLocation = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format([$oldItem]);
		if ($oldLocation == $location) {
			return $data;
		}

		$data['latitude']  = 0;
		$data['longitude'] = 0;

		if (!$location) {
			return $data;
		}

		$location = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($location, false);
		if (!$location->latitude) {
			return $data;
		}

		$data['latitude']  = $location->latitude;
		$data['longitude'] = $location->longitude;

		return $data;
	}
}
