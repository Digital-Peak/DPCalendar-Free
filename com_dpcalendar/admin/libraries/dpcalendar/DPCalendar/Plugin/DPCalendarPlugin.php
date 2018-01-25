<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Plugin;

defined('_JEXEC') or die();

use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Sabre\VObject\Parser\Parser;
use Sabre\VObject\Reader;

\JLoader::import('joomla.plugin.plugin');

\JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
\JLoader::import('components.com_dpcalendar.helpers.ical', JPATH_ADMINISTRATOR);

\JLoader::import('components.com_dpcalendar.tables.event', JPATH_ADMINISTRATOR);
\JLoader::import('components.com_dpcalendar.tables.location', JPATH_ADMINISTRATOR);

\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');

/**
 * This is the base class for the DPCalendar plugins.
 */
abstract class DPCalendarPlugin extends \JPlugin
{

	public $extCalendarsCache = null;
	protected $identifier = null;
	protected $cachingEnabled = true;
	protected $autoloadLanguage = true;

	public function fetchEvent($eventId, $calendarId)
	{
		$eventId = urldecode($eventId);
		$pos     = strrpos($eventId, '_');
		if ($pos === false) {
			return null;
		}
		$s = substr($eventId, $pos + 1);
		if ($s == 0) {
			$uid = substr($eventId, 0, $pos);
			\JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);

			$content = $this->getContent($calendarId, \DPCalendarHelper::getDate('2000-01-01'), null, new Registry());
			if (is_array($content)) {
				$content = implode(PHP_EOL, $content);
			}

			$cal = Reader::read($content);

			foreach ($cal->VEVENT as $event) {
				if ((string)$event->UID != $uid) {
					continue;
				}

				return $this->createEventFromIcal($event, $calendarId, array((string)$event->UID => $event));
			}
		}
		$start = null;
		if (strlen($s) == 8) {
			$start = \JFactory::getDate(substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2) . ' 00:00');
		} else {
			$start = \JFactory::getDate(
				substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2) . ' ' . substr($s, 8, 2) . ':' . substr($s, 10, 2)
			);
		}

		$end = clone $start;
		$end->modify('+1 day');

		$tmpEvent = $this->createEvent($eventId, $calendarId);
		foreach ($this->fetchEvents($calendarId, $start, $end, new Registry()) as $event) {
			if ($event->id == $tmpEvent->id) {
				return $event;
			}
		}

		return null;
	}

	/**
	 * The options can have the following parameters:
	 * - filter: Select only events which match the filter
	 * - limit: The amount of events which should be returned
	 * - expand: If recurring events should be expanded
	 * - location: The event must be around this location based on the givn
	 * radius
	 * - radius: Comes into action when a location is set. Defines how close the
	 * events need to be.
	 * - length-type: The length type in kilometers or miles
	 *
	 * @param string   $content
	 * @param \JDate   $startDate
	 * @param \JDate   $endDate
	 * @param Registry $options
	 *
	 * @return array
	 */
	public function fetchEvents($calendarId, \JDate $startDate = null, \JDate $endDate = null, Registry $options)
	{
		$s = $startDate;
		if ($s) {
			$s = clone $startDate;
		}
		$e = $endDate;
		if ($e) {
			$e = clone $endDate;
		}

		\DPCalendarHelper::increaseMemoryLimit(100 * 1024 * 1024);

		// Remove any time limit
		@set_time_limit(0);

		$content = $this->getContent($calendarId, $s, $e, $options);
		if (empty($content)) {
			return array();
		}
		if (is_array($content)) {
			$content = implode(PHP_EOL, $content);
		}

		if (empty($options)) {
			$options = new Registry();
		}

		\JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);
		$cal = null;

		try {
			$cal = Reader::read($content, Parser::OPTION_IGNORE_INVALID_LINES);
		} catch (\Exception $e) {
			$this->log($e->getMessage());
			$this->log('Content is:' . nl2br(substr($content, 0, 200)));

			return array();
		}

		if ($startDate == null) {
			$startDate = \DPCalendarHelper::getDate();
		}
		if ($endDate == null) {
			$endDate = \DPCalendarHelper::getDate();
			$endDate->modify('+5 year');
		}
		$data = $cal->VEVENT;
		if (empty($data)) {
			return array();
		}

		$originals = array();
		foreach ($cal->VEVENT as $tmp) {
			$originals[] = clone $tmp;
		}

		try {
			if ($options->get('expand', true)) {
				$cal = $cal->expand($startDate, $endDate);
			}
		} catch (\Exception $e) {
			$this->log($e->getMessage());

			return array();
		}

		$data = $cal->VEVENT;
		if (empty($data)) {
			return array();
		}

		$tmp = array();
		foreach ($data as $event) {
			$tmp[] = $event;
		}
		$data = $tmp;

		$events = array();
		$filter = strtolower($options->get('filter', null));
		$limit  = $options->get('limit', null);
		$order  = strtolower($options->get('order', 'asc'));

		$dbCal = $this->getDbCal($calendarId);
		foreach ($data as $event) {
			if (!empty($filter)) {
				$string = StringHelper::strtolower($event->SUMMARY) . ' ' . StringHelper::strtolower($event->DESCRIPTION) . ' ' . StringHelper::strtolower($event->LOCATION);
				if (!\DPCalendarHelper::matches($string, $filter)) {
					continue;
				}
			}

			$tmpEvent                 = $this->createEventFromIcal($event, $calendarId, $originals);
			$tmpEvent->access_content = $dbCal->access_content;

			if (!$this->matchLocationFilterEvent($tmpEvent, $options)) {
				continue;
			}

			$at        = strpos($tmpEvent->id, '@');
			$delimiter = strrpos($tmpEvent->id, '_');
			if ($at !== false && $delimiter !== false) {
				$tmpEvent->id = substr_replace($tmpEvent->id, '', $at, $delimiter - $at);
			}

			$events[] = $tmpEvent;
		}

		usort($events,
			function ($event1, $event2) use ($order) {
				$first  = $event1;
				$second = $event2;
				if (strtolower($order) == 'desc') {
					$first  = $event2;
					$second = $event1;
				}

				return strcmp($first->start_date, $second->start_date);
			}
		);

		if (!empty($limit) && count($events) >= $limit) {
			$events = array_slice($events, 0, $limit);
		}

		return $events;
	}

	protected function fetchCalendars($calendarIds = null)
	{
		if ($this->extCalendarsCache === null) {
			\JLoader::import('joomla.application.component.model');
			\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');

			$this->extCalendarsCache = \JModelLegacy::getInstance('Extcalendars', 'DPCalendarModel', array('ignore_request' => true));
		}

		$model = $this->extCalendarsCache;
		$model->getState();
		$model->setState('filter.plugin', str_replace('dpcalendar_', '', $this->_name));
		$model->setState('filter.state', 1);
		$model->setState('list.limit', -1);
		$model->setState('list.ordering', 'a.ordering');

		$user      = \JFactory::getUser();
		$calendars = array();
		foreach ($model->getItems() as $calendar) {
			if (!empty($calendarIds) && !in_array($calendar->id, $calendarIds)) {
				continue;
			}

			$cal                 = $this->createCalendar($calendar->id, $calendar->title, $calendar->description, $calendar->color);
			$cal->params         = $calendar->params;
			$cal->color_force    = $calendar->color_force;
			$cal->access         = $calendar->access;
			$cal->access_content = $calendar->access_content;
			$cal->sync_date      = $calendar->sync_date;
			$cal->icalurl        = $this->getIcalUrl($cal);

			// Null the sync date
			if (!$cal->sync_date || $cal->sync_date == \JFactory::getDbo()->getNullDate()) {
				$cal->sync_date = null;
			}

			$cal->sync_token = $calendar->sync_token;

			$action         = $calendar->params->get('action-create', 'false');
			$cal->canCreate = $user->authorise('core.create', 'com_dpcalendar.extcalendar.' . $calendar->id) &&
				($action == 'true' || $action === true || $action == 1);
			$action         = $calendar->params->get('action-edit', 'false');
			$cal->canEdit   = $user->authorise('core.edit', 'com_dpcalendar.extcalendar.' . $calendar->id) &&
				($action == 'true' || $action === true || $action == 1);
			$action         = $calendar->params->get('action-delete', 'false');
			$cal->canDelete = $user->authorise('core.delete', 'com_dpcalendar.extcalendar.' . $calendar->id) &&
				($action == 'true' || $action === true || $action == 1);
			$calendars[]    = $cal;
		}

		return $calendars;
	}

	protected function getContent($calendarId, \JDate $startDate = null, \JDate $endDate = null, Registry $options)
	{
		$calendar = $this->getDbCal($calendarId);
		if (empty($calendar)) {
			return '';
		}
		$content = \DPCalendarHelper::fetchContent(str_replace('webcal://', 'https://', $calendar->params->get('uri')));

		if ($content instanceof \Exception) {
			$this->log($content->getMessage());

			return '';
		}

		$content = str_replace("BEGIN:VCALENDAR\r\n", '', $content);
		$content = str_replace("BEGIN:VCALENDAR\n", '', $content);
		$content = str_replace("\r\nEND:VCALENDAR", '', $content);
		$content = str_replace("\nEND:VCALENDAR", '', $content);

		return "BEGIN:VCALENDAR\n" . $content . "\nEND:VCALENDAR";
	}

	/**
	 * Dummy placeholder for plugins which do not support event editing.
	 *
	 * @param string $eventId
	 * @param string $calendarId
	 * @param array  $data
	 *
	 * @return string false
	 */
	public function saveEvent($eventId = null, $calendarId, array $data)
	{
		return false;
	}

	/**
	 * Dummy placeholder for plugins which do not support event deleteing.
	 *
	 * @param string $eventId
	 * @param string $calendarId
	 *
	 * @return boolean
	 */
	public function deleteEvent($eventId = null, $calendarId)
	{
		return false;
	}

	/**
	 * Dummy placeholder for plugins which do not support event editing.
	 *
	 * @param string $eventId
	 * @param string $calendarId
	 */
	public function prepareForm($eventId, $calendarId, $form, $data)
	{
	}

	public function onEventFetch($eventId)
	{
		if (strpos($eventId, $this->identifier) !== 0) {
			return;
		}

		$params = $this->params;

		// Sometimes it changes the id
		$eventId = str_replace($this->identifier . ':', $this->identifier . '-', $eventId);
		$id      = explode('-', str_replace($this->identifier . '-', '', $eventId), 2);
		if (count($id) < 2 || !is_numeric($id[0])) {
			return;
		}

		$cache = \JFactory::getCache('plg_' . $this->_type . '_' . $this->_name);
		$cache->setCaching($params->get('cache', 1) == '1' && $this->cachingEnabled);
		$cache->setLifeTime($params->get('cache_time', 900) / 60);
		$cache->options['locking'] = false;

		try {
			$event = $cache->get(array($this, 'fetchEvent'), array($id[1], $id[0]));
			$cache->gc();

			return $event;
		} catch (\Exception $e) {
			$this->log($e->getMessage());

			return $this->fetchEvent($id[1], $id[0]);
		}
	}

	public function onEventsFetch($calendarId, \JDate $startDate = null, \JDate $endDate = null, Registry $options = null)
	{
		if (strpos($calendarId, $this->identifier) !== 0) {
			return;
		}

		$params = $this->params;

		$id = str_replace($this->identifier . '-', '', $calendarId);

		$cache = \JFactory::getCache('plg_' . $this->_type . '_' . $this->_name, 'callback');
		$cache->setCaching($params->get('cache', 1) == '1' && $this->cachingEnabled);
		$cache->setLifeTime($params->get('cache_time', 900) / 60);
		$cache->options['locking'] = false;

		if ($options == null) {
			$options = new Registry();
		}

		if ($startDate) {
			// If now we cache at least for the minute
			$startDate->setTime($startDate->format('H', true), $startDate->format('i'));
		}

		try {
			$events = $cache->get(array($this, 'fetchEvents'), array($id, $startDate, $endDate, $options));
			$cache->gc();
		} catch (\Exception $e) {
			$this->log($e->getMessage());

			$events = $this->fetchEvents($id, $startDate, $endDate, $options);
		}

		return $events;
	}

	public function onCalendarsFetch($calendarIds = null, $type = null)
	{
		if (!empty($type) && $this->identifier != $type) {
			return;
		}

		$ids = array();
		if (!empty($calendarIds)) {
			if (!is_array($calendarIds)) {
				$calendarIds = array($calendarIds);
			}
			foreach ($calendarIds as $calendarId) {
				if (strpos($calendarId, $this->identifier) === 0) {
					$ids[] = (int)str_replace($this->identifier . '-', '', $calendarId);
				}
			}
			if (empty($ids)) {
				return;
			}
		}

		return $this->fetchCalendars($ids);
	}

	public function onEventBeforeDisplay(&$event)
	{
	}

	public function onEventAfterDisplay(&$event)
	{
	}

	public function onEventBeforeCreate(&$event)
	{
	}

	public function onEventAfterCreate(&$event)
	{
	}

	public function onEventBeforeSave(&$event)
	{
	}

	/**
	 * This function is called when an external event is going
	 * to be saved.
	 * This function is dependant when a calendar has canEdit or
	 * canCreate set to true.
	 *
	 * @param array $data
	 *
	 * @return boolean
	 */
	public function onEventSave(array $data)
	{
		if (strpos($data['catid'], $this->identifier) !== 0) {
			return false;
		}

		$calendarId = str_replace($this->identifier . '-', '', $data['catid']);

		$calendar = $this->getDbCal($calendarId);

		if ((!isset($data['id']) || empty($data['id'])) && !$calendar->canCreate) {
			// No create permission
			\JFactory::getApplication()->enqueueMessage(\JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

			return false;
		}

		if (isset($data['id']) && $data['id'] && !$calendar->canEdit) {
			// No edit permission
			\JFactory::getApplication()->enqueueMessage(\JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

			return false;
		}

		$newEventId = false;
		if (!isset($data['id']) || empty($data['id'])) {
			$newEventId = $this->saveEvent(null, $calendarId, $data);
		} else {
			$eventId = $data['id'];
			$eventId = str_replace($this->identifier . ':', $this->identifier . '-', $eventId);
			$id      = explode('-', str_replace($this->identifier . '-', '', $eventId), 2);
			if (count($id) < 2) {
				return false;
			}

			$newEventId = $this->saveEvent($id[1], $id[0], $data);
		}
		if ($newEventId != false) {
			$cache = \JFactory::getCache('plg_' . $this->_type . '_' . $this->_name);
			$cache->clean();
		}

		return $newEventId;
	}

	public function onEventAfterSave(&$event)
	{
	}

	public function onEventBeforeDelete($event)
	{
	}

	/**
	 * This function is called when an external event is going
	 * to be deleted.
	 * This function is dependant when a calendar has canDelete
	 * set to true.
	 *
	 * @param string $eventId
	 *
	 * @return boolean
	 */
	public function onEventDelete($eventId)
	{
		if (strpos($eventId, $this->identifier) !== 0) {
			return false;
		}

		$eventId = str_replace($this->identifier . ':', $this->identifier . '-', $eventId);
		$id      = explode('-', str_replace($this->identifier . '-', '', $eventId), 2);
		if (count($id) < 2) {
			return false;
		}

		$calendar = $this->getDbCal($id[0]);
		if (!$calendar->canDelete) {
			\JFactory::getApplication()->enqueueMessage(\JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');

			return false;
		}

		$success = $this->deleteEvent($id[1], $id[0]);
		if ($success != false) {
			$cache = \JFactory::getCache('plg_' . $this->_type . '_' . $this->_name);
			$cache->clean();
		}

		return $success;
	}

	public function onEventAfterDelete($event)
	{
	}

	public function onDPCalendarDoAction($action, $pluginName, $data = null)
	{
		if (str_replace('dpcalendar_', '', $this->_name) != $pluginName) {
			return;
		}
		if (!method_exists($this, $action)) {
			return;
		}

		return $this->$action($data);
	}

	public function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof \JForm)) {
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		if (!in_array($form->getName(), array('com_dpcalendar.event'))) {
			return true;
		}

		$eventId = \JFactory::getApplication()->input->getInt('e_id');
		if (empty($eventId)) {
			return true;
		}

		if (strpos($eventId, $this->identifier) !== 0) {
			return true;
		}

		$eventId = str_replace($this->identifier . ':', $this->identifier . '-', $eventId);
		$id      = explode('-', str_replace($this->identifier . '-', '', $eventId), 2);
		if (count($id) < 2) {
			return true;
		}

		return $this->prepareForm($id[1], $id[0], $form, $data);
	}

	protected function createCalendar($id, $title, $description, $color = '3366CC')
	{
		$calendar                  = new \stdClass();
		$calendar->id              = $this->identifier . '-' . $id;
		$calendar->title           = $title;
		$calendar->description     = $description;
		$calendar->plugin_name     = $this->_name;
		$calendar->level           = 1;
		$calendar->color           = $color;
		$calendar->color_force     = 0;
		$calendar->access          = 1;
		$calendar->access_content  = 1;
		$calendar->created_user_id = 0;
		$calendar->external        = true;
		$calendar->system          = $this->identifier;
		$calendar->canCreate       = false;
		$calendar->canEdit         = false;
		$calendar->canEditOwn      = false;
		$calendar->canDelete       = false;
		$calendar->canBook         = false;
		$calendar->sync_date       = null;
		$calendar->sync_token      = null;
		$calendar->native          = $this->params->get('cache', 1) == 2;

		return $calendar;
	}

	/**
	 *
	 * @return \stdClass
	 */
	protected function createEvent($id, $calendarId)
	{
		$event                   = new \stdClass();
		$event->id               = $this->identifier . '-' . $calendarId . '-' . $id;
		$event->alias            = $id;
		$event->catid            = $this->identifier . '-' . $calendarId;
		$event->category_access  = 1;
		$event->category_alias   = $calendarId;
		$event->category_title   = \DPCalendarHelper::getCalendar($event->catid)->title;
		$event->parent_alias     = '';
		$event->parent_id        = 0;
		$event->original_id      = 0;
		$event->title            = '';
		$event->rrule            = null;
		$event->recurrence_id    = null;
		$event->start_date       = '';
		$event->end_date         = '';
		$event->show_end_time    = true;
		$event->all_day          = false;
		$event->color            = '';
		$event->url              = '';
		$event->price            = array();
		$event->locations        = array();
		$event->rooms            = null;
		$event->hits             = 0;
		$event->capacity         = 0;
		$event->capacity_used    = 0;
		$event->description      = '';
		$event->state            = 1;
		$event->access           = 1;
		$event->access_content   = 1;
		$event->language         = '*';
		$event->created          = '';
		$event->created_by       = 0;
		$event->created_by_alias = '';
		$event->modified         = '';
		$event->modified_by      = 0;
		$event->params           = '';
		$event->metadesc         = null;
		$event->metakey          = null;
		$event->metadata         = new Registry();
		$event->author           = null;
		$event->xreference       = $event->id;

		return $event;
	}

	/**
	 *
	 * @return \stdClass
	 */
	private function createEventFromIcal(\Sabre\VObject\Component\VEvent $event, $calendarId, array $originals)
	{
		$allDay = !$event->DTSTART->hasTime();
		// Microsoft has a special property to flag all day events
		if (isset($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'})) {
			$allDay = strtolower($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'}) == 'true';
		}

		$startDate = \DPCalendarHelper::getDate($event->DTSTART->getDateTime()->format('U'), $allDay);

		$endDate = null;
		if ($event->DURATION != null) {
			$endDate  = clone $startDate;
			$duration = \Sabre\VObject\DateTimeParser::parseDuration($event->DURATION, true);
			$endDate->modify($duration);

			if ($allDay) {
				$endDate->modify('-1 day');
			}
		} else {
			if (!$event->DTEND) {
				$endDate = clone $startDate;
				$endDate->setTime(23, 59, 59);
			} else {
				$endDate = \DPCalendarHelper::getDate($event->DTEND->getDateTime()->format('U'), $allDay);
				if ($allDay) {
					$endDate->modify('-1 day');
				}
			}
		}

		// Search for the original to get the rrule
		$original = null;
		foreach ($originals as $tmp) {
			if ((string)$tmp->UID == (string)$event->UID && $tmp->{'RECURRENCE-ID'} === null && $tmp->RRULE !== null) {
				$original = $tmp;

				if ($event->{'RECURRENCE-ID'} === null &&
					$event->DTSTART->getDateTime()->format('U') == (string)$original->DTSTART->getDateTime()->format('U') &&
					$event->RRULE === null
				) {
					$event->add('RECURRENCE-ID', (string)$event->DTSTART);
					$event->{'RECURRENCE-ID'}->parameters = $event->DTSTART->parameters;
				}
				break;
			}
		}

		// Find the override in the originals
		if ($event->{'RECURRENCE-ID'}) {
			$recurrenceDate = $event->{'RECURRENCE-ID'}->getDateTime()->format('U');
			foreach ($originals as $o) {
				if ($recurrenceDate == $o->DTSTART->getDateTime()->format('U') && (string)$o->UID == (string)$event->UID && $o->RRULE === null) {
					$event = $o;
				}
			}
		}

		$id    = 0;
		$recId = $event->{'RECURRENCE-ID'};
		if ($original !== null && $recId === null) {
			$id = $event->UID . '_0';
		} else {
			$id = $event->UID . '_' . ($allDay ? $startDate->format('Ymd') : $startDate->format('YmdHi'));
		}

		$tmpEvent      = $this->createEvent($id, $calendarId);
		$tmpEvent->uid = (string)$event->UID;
		if (!empty($recId)) {
			$tmpEvent->recurrence_id = (string)$recId;
		}
		$tmpEvent->start_date = $startDate->toSql();
		$tmpEvent->end_date   = $endDate->toSql();

		$title                 = (string)$event->SUMMARY;
		$title                 = str_replace('\n', ' ', $title);
		$title                 = str_replace('\N', ' ', $title);
		$tmpEvent->title       = \DPCalendar\Helper\Ical::icalDecode($title);
		$tmpEvent->alias       = \JApplicationHelper::stringURLSafe($tmpEvent->title);
		$tmpEvent->description = \DPCalendar\Helper\Ical::icalDecode((string)$event->DESCRIPTION);

		$created = $event->CREATED;
		if (!empty($created)) {
			$tmpEvent->created = \DPCalendarHelper::getDate($created->getDateTime()->format('U'))->toSql();
		}
		$modified = $event->{'LAST-MODIFIED'};
		if (!empty($modified)) {
			$tmpEvent->modified = \DPCalendarHelper::getDate($modified->getDateTime()->format('U'))->toSql();
		}

		$description = (string)$event->{'X-ALT-DESC'};
		if (!empty($description)) {
			$desc = $description;
			if (is_array($desc)) {
				$desc = implode(' ', $desc);
			}
			$tmpEvent->description = \DPCalendar\Helper\Ical::icalDecode($desc);
		}

		$author = (string)$event->ORGANIZER;
		if (!empty($author)) {
			$tmpEvent->created_by_alias = str_replace('MAILTO:', '', $author);
		}

		if (isset($event->ATTENDEE)) {
			$tmpEvent->bookings = array();
			foreach ($event->ATTENDEE as $child) {
				$booking        = new \stdClass();
				$booking->name  = '';
				$booking->email = str_replace('MAILTO:', '', $child);
				$booking->id    = md5($booking->email);
				foreach ($child->parameters() as $param) {
					if ($param->name == 'CN') {
						$booking->name = (string)$param;
					}
				}

				// A name is at least required
				if (!empty($booking->name)) {
					$tmpEvent->bookings[] = $booking;
				}
			}
		}

		// Add none standard properties
		$color = (string)$event->{'x-color'};
		if (!empty($color) && !\DPCalendarHelper::getCalendar($tmpEvent->catid)->color_force) {
			$tmpEvent->color = $color;
		}
		$url = (string)$event->{'x-url'};
		if (!empty($url)) {
			$tmpEvent->url = $url;
		} else {
			if ($url = (string)$event->URL) {
				$tmpEvent->url = $url;
			}
		}
		$alias = (string)$event->{'x-alias'};
		if (!empty($alias)) {
			$tmpEvent->alias = $alias;
		}
		$language = (string)$event->{'x-language'};
		if (!empty($language)) {
			$tmpEvent->language = $language;
		}
		$image = (string)$event->{'x-image'};
		if (!empty($image)) {
			$tmpEvent->images = json_encode(array('image1' => $image));
		}

		$location  = (string)$event->LOCATION;
		$locations = array();
		if (!empty($location)) {
			$geo = (string)$event->GEO;
			if (!empty($geo) && strpos($geo, ';') !== false) {
				static $locationModel = null;
				if ($locationModel == null) {
					\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
					$locationModel = \JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
					$locationModel->getState();
					$locationModel->setState('list.limit', 1);
				}
				list ($latitude, $longitude) = explode(';', $geo);
				$locationModel->setState('filter.latitude', $latitude);
				$locationModel->setState('filter.longitude', $longitude);

				$tmp = $locationModel->getItems();
				if (!empty($tmp)) {
					$locations = $tmp;

					$tmpEvent->location_ids = array();
					foreach ($tmp as $dpLocation) {
						$tmpEvent->location_ids[] = $dpLocation->id;
					}
				} else {
					list ($latitude, $longitude) = explode(';', $geo);
					$locationObject = \DPCalendar\Helper\Location::get($latitude . ',' . $longitude, true, $location);

					$locations[] = $locationObject;
				}
			} else {
				$locations[] = \DPCalendar\Helper\Location::get(\DPCalendar\Helper\Ical::icalDecode($location));
			}
		}
		$tmpEvent->locations = $locations;
		$tmpEvent->all_day   = $allDay;

		if ($original !== null) {
			if ($recId !== null) {
				$tmpEvent->original_id = $this->identifier . '-' . $calendarId . '-' . $event->UID . '_0';
			} else {
				$tmpEvent->rrule       = (string)$original->RRULE;
				$tmpEvent->original_id = -1;
			}
		}

		return $tmpEvent;
	}

	protected function getDbCal($calendarId)
	{
		$calendars = $this->fetchCalendars(array($calendarId));
		if (empty($calendars)) {
			return null;
		}

		return $calendars[0];
	}

	protected function getIcalUrl($calendar)
	{
		return null;
	}

	protected function replaceNl($text, $replace = '')
	{
		return str_replace(array("\r\n", "\r", "\n"), $replace, $text);
	}

	protected function log($message)
	{
		\JFactory::getApplication()->enqueueMessage((string)$message, 'warning');
	}

	protected function matchLocationFilterEvent($event, Registry $options)
	{
		if ($options->get('radius') == -1) {
			return true;
		}
		$location    = $options->get('location');
		$locationIds = $options->get('location_ids', array());
		if (empty($location) && empty($locationIds)) {
			return true;
		}

		$locationFilterData            = new  \stdClass();
		$locationFilterData->latitude  = null;
		$locationFilterData->longitude = null;

		$radius = $options->get('radius');
		if ($options->get('length-type') == 'm') {
			$radius = $radius * 0.62137119;
		}

		if (strpos($location, 'latitude=') !== false && strpos($location, 'longitude=') !== false) {
			list ($latitude, $longitude) = explode(';', $location);
			$locationFilterData->latitude  = str_replace('latitude=', '', $latitude);
			$locationFilterData->longitude = str_replace('longitude=', '', $longitude);
		} else {
			if (!empty($location)) {
				$locationFilterData = \DPCalendar\Helper\Location::get($location);
			}
		}

		$within = false;
		foreach ($event->locations as $loc) {
			if (!in_array($loc->id, $locationIds) &&
				!\DPCalendar\Helper\Location::within($loc, $locationFilterData->latitude, $locationFilterData->longitude, $radius)
			) {
				continue;
			}
			$within = true;
			break;
		}

		return $within;
	}
}
