<?php

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Event;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryAwareInterface;
use Joomla\CMS\Form\FormFactoryAwareTrait;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Component\Contact\Site\Helper\RouteHelper;

class HtmlView extends BaseView implements FormFactoryAwareInterface, UserFactoryAwareInterface
{
	use FormFactoryAwareTrait;
	use UserFactoryAwareTrait;

	/** @var array */
	protected $roomTitles;

	/** @var string */
	protected $avatar;

	/** @var string */
	protected $authorName;

	/** @var array */
	protected $seriesEvents;

	/** @var int */
	protected $seriesEventsTotal;

	/** @var ?\stdClass */
	protected $originalEvent;

	/** @var ?string */
	protected $noBookingMessage;

	/** @var ?\stdClass */
	protected $taxRate;

	/** @var ?\stdClass */
	protected $country;

	/** @var string */
	protected $returnPage;

	/** @var Form */
	protected $mailTicketsForm;

	/** @var int */
	protected $heading;

	/** @var \stdClass */
	protected $event;

	protected function init(): void
	{
		if ($this->getLayout() === 'empty') {
			return;
		}

		$this->state->set('filter.state_owner', true);

		$event = $this->get('Item');
		if (!$event || !$event->id) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND'), 404);
		}

		// Use the options from the event
		$this->params->merge($event->params);

		if ($this->params->get('event_redirect_to_url', 0) && $event->url) {
			$this->app->redirect($event->url);
		}

		// Add router helpers.
		$event->slug = $event->alias ? ($event->id . ':' . $event->alias) : $event->id;

		// Check the access to the event
		$levels = $this->getCurrentUser()->getAuthorisedViewLevels();

		if (!in_array($event->access, $levels) ||
			((in_array($event->access, $levels) && (isset($event->category_access) && !in_array($event->category_access, $levels))))
		) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		if ($this->getLayout() === 'mailtickets' && !$event->params->get('send-tickets-mail')) {
			$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'), 'error');
			$this->app->redirect($this->router->getEventRoute($event->id, $event->catid));
		}

		$event->tags = new TagsHelper();
		$event->tags->getItemTags('com_dpcalendar.event', $event->id);

		PluginHelper::importPlugin('dpcalendar');
		PluginHelper::importPlugin('content');

		$event->text = $event->description ?: '';
		$this->app->triggerEvent(
			'onContentPrepare',
			[
				'com_dpcalendar.event',
				&$event,
				&$event->params,
				0
			]
		);
		$event->description = $event->text;

		if ($event->introText) {
			$event->text = $event->introText;
			$this->app->triggerEvent(
				'onContentPrepare',
				[
					'com_dpcalendar.event',
					&$event,
					&$event->params,
					0
				]
			);
			$event->introText = $event->text;
		}

		$event->displayEvent = new \stdClass();
		$results             = $this->app->triggerEvent(
			'onContentAfterTitle',
			['com_dpcalendar.event', &$event, &$event->params, 0]
		);
		$event->displayEvent->afterDisplayTitle = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentBeforeDisplay',
			['com_dpcalendar.event', &$event, &$event->params, 0]
		);
		$event->displayEvent->beforeDisplayContent = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentAfterDisplay',
			['com_dpcalendar.event', &$event, &$event->params, 0]
		);
		$event->displayEvent->afterDisplayContent = trim(implode("\n", $results));

		$this->event = $event;

		$model = $this->getModel();

		if ($this->params->get('event_count_clicks', 1)) {
			$model->hit();
		}

		$this->avatar              = '';
		$this->authorName          = $event->created_by_alias ?: '';
		$this->event->contact_link = '';
		$author                    = $this->getUserFactory()->loadUserById($event->created_by);
		if (!empty($author->id)) {
			$this->authorName = $event->created_by_alias ?: $author->name;

			if (file_exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php')) {
				// Set the community builder username as content
				include_once JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php';
				// @phpstan-ignore-next-line
				$cbUser = \CBuser::getInstance($event->created_by);
				if ($cbUser) {
					$this->authorName = $cbUser->getField('formatname', null, 'html', 'none', 'list', 0, true);
				}
			}

			$this->avatar = DPCalendarHelper::getAvatar($author->id, $author->email, $this->params);

			if (is_dir(JPATH_ROOT . '/components/com_dpusers')) {
				$component = ComponentHelper::getComponent('com_dpusers');
				$items     = $this->app->getMenu()->getItems('component_id', $component->id);
				if (!is_array($items)) {
					$items = [$items];
				}

				foreach ($items as $item) {
					if (array_intersect(Access::getGroupsByUser($event->created_by), $item->getParams()->get('groups_ids', [])) === []) {
						continue;
					}

					$this->event->contact_link = Route::_('index.php?option=com_dpusers&view=user&id=' . (int)$event->created_by . '&Itemid=' . $item->id);
				}
			}
		}

		if (!empty($event->contactid) && !empty($event->contactcatid)) {
			$this->event->contact_link = Route::_(
				RouteHelper::getContactRoute($event->contactid, $event->contactcatid)
			);
		}

		$hosts = [];
		foreach (array_unique(explode(',', (string)($this->event->host_ids ?: ''))) as $host) {
			if ($host === '' || $host === '0') {
				continue;
			}

			$user = $this->getUserFactory()->loadUserById((int)$host);

			foreach ($this->event->hostContacts as $hostContact) {
				if ($hostContact->user_id != $user->id) {
					continue;
				}

				// @phpstan-ignore-next-line
				$user->link = Route::_(
					RouteHelper::getContactRoute($hostContact->id, $hostContact->catid)
				);
			}

			$hosts[] = $user;
		}
		$this->event->hosts = $hosts;

		$this->displayData['event'] = $this->event;

		$this->seriesEvents      = [];
		$this->seriesEventsTotal = 0;

		if ($event->original_id > 0 || $event->original_id == '-1') {
			$seriesModel = $model->getSeriesEventsModel($this->event);
			$seriesModel->setState('list.limit', (int)$this->params->get('event_series_max', 5));
			$this->seriesEvents      = $seriesModel->getItems();
			$this->seriesEventsTotal = $seriesModel->getTotal();
			$this->originalEvent     = $model->getItem($event->original_id) ?: $this->originalEvent;
		}

		$this->noBookingMessage = $this->getBookingMessage($event);
		if ($this->originalEvent && $this->originalEvent->booking_series == 1) {
			$this->noBookingMessage = $this->getBookingMessage($this->originalEvent);
		}

		if ($this->noBookingMessage === null && $this->params->get('event_show_booking_form')) {
			// Set some variables for the booking form view
			$this->app->getInput()->set('view', 'bookingform');
			$this->app->getInput()->set('layout', 'default');
			$this->app->getInput()->set('e_id', $this->event->id);
		}

		// Taxes stuff
		$this->country = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getCountryForIp();
		if ($this->country instanceof \stdClass) {
			$model         = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Taxrate', 'Administrator', ['ignore_request' => true]);
			$this->taxRate = $model->getItemByCountry($this->country->id);

			$this->app->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
		}

		if (!isset($event->tickets)) {
			$event->tickets = [];
		}

		foreach ($event->tickets as $ticket) {
			// Try to find the label of the ticket type
			$ticket->price_label = '';
			if (!$event->price) {
				continue;
			}
			if (!(array_key_exists($ticket->type, $event->price->label) && $event->price->label[$ticket->type])) {
				continue;
			}
			$ticket->price_label = $event->price->label[$ticket->type];
		}

		if ($this->getLayout() === 'mailtickets') {
			$this->setModel($this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Form', 'Site'));
			$this->returnPage = $this->get('ReturnPage');

			$this->mailTicketsForm = $this->getFormFactory()->createForm('com_dpcalendar.mailtickets', ['control' => 'jform']);
			$this->mailTicketsForm->loadFile(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms/mailtickets.xml');

			$this->mailTicketsForm->setValue(
				'subject',
				null,
				$this->app->getUserState(
					'com_dpcalendar.form.event.mailticketsdata.subject',
					$this->translate('COM_DPCALENDAR_FIELD_MAILTICKETS_SUBJECT_DEFAULT')
				)
			);
			$this->mailTicketsForm->setValue(
				'body',
				null,
				$this->app->getUserState(
					'com_dpcalendar.form.event.mailticketsdata.message',
					$this->translate('COM_DPCALENDAR_FIELD_MAILTICKETS_MESSAGE_DEFAULT')
				)
			);
			$this->mailTicketsForm->setValue('event_id', null, $event->id);
			HTMLHelper::_('behavior.formvalidator');
		}

		parent::init();
	}

	/**
	 * Returns a booking message. If the string is null, then booking is possible.
	 * If it is an empty string then no booking is activated. If it is a string, then
	 * no booking is possible while the string represents the warning message.
	 *
	 * @param \stdClass $event
	 */
	private function getBookingMessage($event): ?string
	{
		// When booking is disabled or not available
		if ($event->capacity == '0' || $event->state == 3 || DPCalendarHelper::isFree()) {
			return '';
		}

		// Check permissions
		$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);
		if (!$calendar instanceof CalendarInterface || !$calendar->canBook()) {
			return '';
		}

		// Check if full
		if ($event->capacity !== null && $event->capacity > 0 && $event->capacity_used >= $event->capacity && !$event->booking_waiting_list) {
			return $this->translate('COM_DPCALENDAR_VIEW_EVENT_BOOKING_MESSAGE_CAPACITY_FULL');
		}

		// Check if registration started
		$now                   = DPCalendarHelper::getDate();
		$registrationStartDate = Booking::getRegistrationStartDate($event);
		if ($registrationStartDate->format('U') > $now->format('U')) {
			return Text::sprintf(
				'COM_DPCALENDAR_VIEW_EVENT_BOOKING_MESSAGE_REGISTRATION_START',
				$registrationStartDate->format($this->params->get('event_date_format', 'd.m.Y'), true),
				$registrationStartDate->format('H:i') !== '00:00' ? $registrationStartDate->format(
					$this->params->get('event_time_format', 'h:i a'),
					true
				) : ''
			);
		}

		// Check if registration ended
		$now                = DPCalendarHelper::getDate();
		$regstrationEndDate = Booking::getRegistrationEndDate($event);
		if ($regstrationEndDate->format('U') < $now->format('U')) {
			return Text::sprintf(
				'COM_DPCALENDAR_VIEW_EVENT_BOOKING_MESSAGE_REGISTRATION_END',
				$regstrationEndDate->format($this->params->get('event_date_format', 'd.m.Y'), true),
				$regstrationEndDate->format('H:i') !== '00:00' ? $regstrationEndDate->format(
					$this->params->get('event_time_format', 'h:i a'),
					true
				) : ''
			);
		}

		// Set the ticket count
		$ticketCount = $event->max_tickets ?: 1;

		// Remove the already booked tickets from the ticket count
		foreach ($event->tickets as $ticket) {
			if ($ticket->email == $this->user->email || ($ticket->user_id && $ticket->user_id == $this->user->id)) {
				$ticketCount--;
			}
		}

		// If ticket count is higher than available space, reduce it
		if ($event->capacity !== null && $ticketCount > ($event->capacity - $event->capacity_used)) {
			$ticketCount = $event->capacity - $event->capacity_used;
		}

		if (!$ticketCount && $event->booking_waiting_list) {
			return null;
		}

		if (!$ticketCount) {
			return $this->translate('COM_DPCALENDAR_VIEW_EVENT_BOOKING_MESSAGE_CAPACITY_FULL_USER');
		}

		// All fine
		return null;
	}

	protected function prepareDocument(): void
	{
		if ($this->getLayout() == 'empty') {
			return;
		}

		parent::prepareDocument();

		$menu = $this->app->getMenu()->getActive();

		$id = $menu && array_key_exists('id', $menu->query) ? (int)$menu->query['id'] : 0;
		if ($menu && ($menu->query['option'] != 'com_dpcalendar' || $menu->query['view'] != 'event' || $id != $this->event->id)) {
			$this->app->getPathway()->addItem(strip_tags((string)$this->event->title), '');
		}

		// The meta prefix
		$metaPrefix = strip_tags((string)$this->event->title) . ' '
		. DPCalendarHelper::getDateStringFromEvent(
			$this->event,
			$this->params->get('event_date_format', 'd.m.Y'),
			$this->params->get('event_time_format', 'H:i'),
			true
		) . ' ';

		// Get the metadesc property
		$metaDesc = trim((string)$this->event->metadata->get('metadesc', ''));

		// Build it from the description
		if ($metaDesc === '' || $metaDesc === '0') {
			// Add meta prefix only when it is set to empty
			$metaDesc = ($this->params->get('event_prefix_meta_description', '1') == '2' ? $metaPrefix : '')
				. HTMLHelper::_('string.truncate', $this->event->description, 100, true, false);
		}

		// Prefix it when forced
		if ($this->params->get('event_prefix_meta_description', '1') == '1') {
			$metaDesc = $metaPrefix . $metaDesc;
		}

		$document = $this->getDocument();

		// Set the meta description when available
		if ($metaDesc !== '' && $metaDesc !== '0') {
			$document->setDescription(trim($metaDesc));
		}

		if ($this->event->metakey) {
			$document->setMetadata('keywords', $this->event->metakey);
		}

		if ($this->app->get('MetaAuthor') == '1' && !empty($this->event->author)) {
			$document->setMetaData('author', $this->event->author);
		}

		$mdata = $this->event->metadata->toArray();
		foreach ($mdata as $k => $v) {
			if (!$v) {
				continue;
			}
			if ($k == 'metadesc') {
				continue;
			}
			if ($k == 'metakey') {
				continue;
			}
			$document->setMetadata($k, $v);
		}

		if ($this->params->get('event_show_page_heading', 0) != 2) {
			$this->params->set('show_page_heading', $this->params->get('event_show_page_heading', 0));
		}

		$this->heading = $this->params->get('show_page_heading') ? 1 : 0;
	}

	protected function getDocumentTitle(): string
	{
		$menu = $this->app->getMenu()->getActive();
		$id   = $menu && array_key_exists('id', $menu->query) ? (int)$menu->query['id'] : 0;

		// If this is not a single event menu item, set the page title to the event title
		if ($menu && ($menu->query['option'] != 'com_dpcalendar' || $menu->query['view'] != 'event' || $id != $this->event->id)) {
			return strip_tags((string)$this->event->title);
		}

		return parent::getDocumentTitle();
	}
}
