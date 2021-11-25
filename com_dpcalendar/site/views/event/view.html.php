<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;

class DPCalendarViewEvent extends BaseView
{
	protected $event;

	public function init()
	{
		if ($this->getLayout() == 'empty') {
			return;
		}

		$this->state->set('filter.state_owner', true);

		$event = $this->get('Item');
		if ($event == null || !$event->id) {
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
		$levels = Factory::getUser()->getAuthorisedViewLevels();

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

		$event->text = $event->description;
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

		$event->displayEvent = new stdClass();
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

		$this->roomTitles = [];
		if ($event->locations && !empty($this->event->rooms)) {
			foreach ($event->locations as $location) {
				if (empty($location->rooms)) {
					continue;
				}

				foreach ($this->event->rooms as $room) {
					list($locationId, $roomId) = explode('-', $room, 2);

					foreach ($location->rooms as $lroom) {
						if ($locationId != $location->id || $roomId != $lroom->id) {
							continue;
						}

						$this->roomTitles[$locationId][$room] = $lroom->title;
					}
				}
			}
		}

		$this->avatar     = '';
		$this->authorName = '';
		$author           = Factory::getUser($event->created_by);
		if ($author) {
			$this->authorName = $event->created_by_alias ? $event->created_by_alias : $author->name;

			if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php')) {
				// Set the community builder username as content
				include_once(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php');
				$cbUser = CBuser::getInstance($event->created_by);
				if ($cbUser) {
					$this->authorName = $cbUser->getField('formatname', null, 'html', 'none', 'list', 0, true);
				}
			}

			$this->avatar = DPCalendarHelper::getAvatar($author->id, $author->email, $this->params);
		}

		$this->event->contact_link = '';
		if (!empty($event->contactid)) {
			JLoader::register('ContactHelperRoute', JPATH_SITE . '/components/com_contact/helpers/route.php');
			$this->event->contact_link = Route::_(
				ContactHelperRoute::getContactRoute($event->contactid . ':' . $event->contactalias, $event->contactcatid)
			);
		}
		$this->displayData['event'] = $this->event;

		$this->seriesEvents      = [];
		$this->seriesEventsTotal = 0;
		$this->originalEvent     = null;

		if ($event->original_id > 0 || $event->original_id == '-1') {
			$seriesModel             = $model->getSeriesEventsModel($this->event);
			$this->seriesEvents      = $seriesModel->getItems();
			$this->seriesEventsTotal = $seriesModel->getTotal();
			$this->originalEvent     = $model->getItem($event->original_id);
		}

		$this->noBookingMessage = $this->getBookingMessage($event);
		if ($this->originalEvent && $this->originalEvent->booking_series == 1) {
			$this->noBookingMessage = $this->getBookingMessage($this->originalEvent);
		}

		if ($this->noBookingMessage === null && $this->params->get('event_show_booking_form')) {
			require JPATH_SITE . '/components/com_dpcalendar/controllers/bookingform.php';
			// Set some variables for the booking form view
			$this->app->input->set('view', 'bookingform');
			$this->app->input->set('layout', 'default');
			$this->app->input->set('e_id', $this->event->id);
		}

		// Taxes stuff
		$this->taxRate = null;
		if ($this->country = \DPCalendar\Helper\Location::getCountryForIp()) {
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$model         = BaseDatabaseModel::getInstance('Taxrate', 'DPCalendarModel', ['ignore_request' => true]);
			$this->taxRate = $model->getItemByCountry($this->country->id);

			$this->app->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
		}

		if (!isset($event->tickets)) {
			$event->tickets = [];
		}

		foreach ($event->tickets as $ticket) {
			// Try to find the label of the ticket type
			$ticket->price_label = '';
			if ($event->price) {
				if (array_key_exists($ticket->type, $event->price->label) && $event->price->label[$ticket->type]) {
					$ticket->price_label = $event->price->label[$ticket->type];
				}
			}
		}

		if ($this->getLayout() === 'mailtickets') {
			$this->setModel(BaseDatabaseModel::getInstance('Form', 'DPCalendarModel'));
			$this->returnPage = $this->get('ReturnPage');

			Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
			Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/fields');
			$this->mailTicketsForm = Form::getInstance('com_dpcalendar.mailtickets', 'mailtickets', ['control' => 'jform']);
			$this->mailTicketsForm->setValue('subject', null, $this->translate('COM_DPCALENDAR_FIELD_MAILTICKETS_SUBJECT_DEFAULT'));
			$this->mailTicketsForm->setValue('body', null, $this->translate('COM_DPCALENDAR_FIELD_MAILTICKETS_MESSAGE_DEFAULT'));
			$this->mailTicketsForm->setValue('event_id', null, $event->id);
			HTMLHelper::_('behavior.formvalidator');
		}

		return parent::init();
	}

	/**
	 * Returns a booking message. If the string is null, then booking is possible.
	 * If it is an empty string then no booking is activated. If it is a string, then
	 * no booking is possible while the string represents the warning message.
	 *
	 * @param $event
	 *
	 * @return string|null
	 */
	private function getBookingMessage($event)
	{
		// Handle no event
		if (!$event) {
			return '';
		}

		// When booking is disabled or not available
		if ($event->capacity == '0' || $event->state == 3 || \DPCalendarHelper::isFree()) {
			return '';
		}

		// Check permissions
		$calendar = \DPCalendarHelper::getCalendar($event->catid);
		if (!$calendar || !$calendar->canBook) {
			return '';
		}

		// Check if full
		if ($event->capacity !== null && $event->capacity > 0 && $event->capacity_used >= $event->capacity && !$event->booking_waiting_list) {
			return $this->translate('COM_DPCALENDAR_VIEW_EVENT_BOOKING_MESSAGE_CAPACITY_FULL');
		}

		// Check if registration started
		$now                   = \DPCalendarHelper::getDate();
		$registrationStartDate = \DPCalendar\Helper\Booking::getRegistrationStartDate($event);
		if ($registrationStartDate->format('U') > $now->format('U')) {
			return JText::sprintf(
				'COM_DPCALENDAR_VIEW_EVENT_BOOKING_MESSAGE_REGISTRATION_START',
				$registrationStartDate->format($this->params->get('event_date_format', 'd.m.Y'), true),
				$registrationStartDate->format('H:i') != '00:00' ? $registrationStartDate->format(
					$this->params->get('event_time_format', 'h:i a'),
					true
				) : ''
			);
		}

		// Check if registration ended
		$now                = \DPCalendarHelper::getDate();
		$regstrationEndDate = \DPCalendar\Helper\Booking::getRegistrationEndDate($event);
		if ($regstrationEndDate->format('U') < $now->format('U')) {
			return JText::sprintf(
				'COM_DPCALENDAR_VIEW_EVENT_BOOKING_MESSAGE_REGISTRATION_END',
				$regstrationEndDate->format($this->params->get('event_date_format', 'd.m.Y'), true),
				$regstrationEndDate->format('H:i') != '00:00' ? $regstrationEndDate->format(
					$this->params->get('event_time_format', 'h:i a'),
					true
				) : ''
			);
		}

		// Set the ticket count
		$ticketCount = $event->max_tickets ?: 1;

		// If ticket count is higher than available space, reduce it
		if ($event->capacity !== null && $ticketCount > ($event->capacity - $event->capacity_used)) {
			$ticketCount = $event->capacity - $event->capacity_used;
		}

		if (!$ticketCount && $event->booking_waiting_list) {
			return null;
		}

		// Remove the already booked tickets from the ticket count
		foreach ($event->tickets as $ticket) {
			if ($ticket->email == $this->user->email || ($ticket->user_id && $ticket->user_id == $this->user->id)) {
				$ticketCount--;
			}
		}

		if (!$ticketCount) {
			return $this->translate('COM_DPCALENDAR_VIEW_EVENT_BOOKING_MESSAGE_CAPACITY_FULL_USER');
		}

		// All fine
		return null;
	}

	protected function prepareDocument()
	{
		if ($this->getLayout() == 'empty') {
			return;
		}

		parent::prepareDocument();

		$menus   = $this->app->getMenu();
		$pathway = $this->app->getPathway();

		// Because the application sets a default page title, we need to get it from the menu item itself
		$menu = $menus->getActive();

		$id = $menu && array_key_exists('id', $menu->query) ? (int)$menu->query['id'] : 0;

		// If the menu item does not concern this newsfeed
		if ($menu && ($menu->query['option'] != 'com_dpcalendar' || $menu->query['view'] != 'event' || $id != $this->event->id)) {
			$pathway->addItem($this->event->title, '');
		}

		$metadesc = trim($this->event->metadata->get('metadesc'));
		if (!$metadesc) {
			$metadesc = JHtmlString::truncate($this->event->description, 100, true, false);
		}
		if ($metadesc) {
			$this->document->setDescription($this->event->title . ' '
				. DPCalendarHelper::getDateStringFromEvent(
					$this->event,
					$this->params->get('event_date_format', 'd.m.Y'),
					$this->params->get('event_time_format', 'H:i'),
					true
				) . ' ' . $metadesc);
		}

		if ($this->event->metakey) {
			$this->document->setMetadata('keywords', $this->event->metakey);
		}

		if ($this->app->get('MetaAuthor') == '1' && !empty($this->event->author)) {
			$this->document->setMetaData('author', $this->event->author);
		}

		$mdata = $this->event->metadata->toArray();
		foreach ($mdata as $k => $v) {
			if ($v && $k != 'metadesc' && $k != 'metakey') {
				$this->document->setMetadata($k, $v);
			}
		}

		if ($this->params->get('event_show_page_heading', 0) != 2) {
			$this->params->set('show_page_heading', $this->params->get('event_show_page_heading', 0));
		}

		$this->heading = $this->params->get('show_page_heading') ? 1 : 0;
	}

	protected function getDocumentTitle()
	{
		$menu = $this->app->getMenu()->getActive();
		$id   = $menu && array_key_exists('id', $menu->query) ? (int)$menu->query['id'] : 0;

		// If this is not a single event menu item, set the page title to the event title
		if ($menu && ($menu->query['option'] != 'com_dpcalendar' || $menu->query['view'] != 'event' || $id != $this->event->id)) {
			return $this->event->title;
		}

		return parent::getDocumentTitle();
	}
}
