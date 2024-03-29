<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Plugin;

defined('_JEXEC') or die();

use DigitalPeak\ThinHTTP as HTTP;
use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\Ical;
use DPCalendar\Helper\Location;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Parser\Parser;
use Sabre\VObject\Reader;

\JLoader::import('joomla.plugin.plugin');

\JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

\JLoader::import('components.com_dpcalendar.tables.event', JPATH_ADMINISTRATOR);
\JLoader::import('components.com_dpcalendar.tables.location', JPATH_ADMINISTRATOR);

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');

/**
 * This is the base class for the DPCalendar plugins.
 */
abstract class DPCalendarPlugin extends CMSPlugin
{
	public $_name;
	public $params;
	public $_type;
	public $_subject;
	public $extCalendarsCache;
	protected $identifier;
	protected $cachingEnabled   = true;
	protected $autoloadLanguage = true;

	/** @var CMSApplication $app */
	protected $app;

	public function getIdentifier()
	{
		return $this->identifier;
	}

	public function fetchEvent($eventId, $calendarId)
	{
		$calendar = $this->getDbCal($calendarId);
		if (empty($calendar)) {
			return null;
		}

		$eventId = urldecode($eventId);
		$pos     = strrpos($eventId, '_');
		if ($pos === false) {
			return null;
		}

		$s = substr($eventId, $pos + 1);
		if ($s == 0) {
			$uid = substr($eventId, 0, $pos);
			\JLoader::import('components.com_dpcalendar.vendor.autoload', JPATH_ADMINISTRATOR);

			$content = $this->getContent($calendarId, DPCalendarHelper::getDate('2000-01-01'), null, new Registry());
			if (is_array($content)) {
				$content = implode(PHP_EOL, $content);
			}

			$cal = Reader::read($content);

			foreach ($cal->VEVENT as $event) {
				if ((string)$event->UID !== $uid) {
					continue;
				}

				return $this->createEventFromIcal($event, $calendarId, [(string)$event->UID => $event]);
			}
		}

		$start = null;
		if (strlen($s) === 8) {
			$start = DPCalendarHelper::getDate(
				substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2) . ' 00:00',
				true,
				// Set here the timezone when available so it can be converted back correctly
				$calendar->params->get('timezone')
			);
		} else {
			$start = DPCalendarHelper::getDate(
				substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2) . ' ' . substr($s, 8, 2) . ':' . substr($s, 10, 2),
				false
			);
		}
		// Start date must be inclusive, @see VEvent::isInTimeRange
		$start->modify('-1 second');

		$end = clone $start;
		$end->modify('+1 day');

		$tmpEvent = $this->createEvent($eventId, $calendarId);
		foreach ($this->fetchEvents($calendarId, new Registry(), $start, $end) as $event) {
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
	 * @param Date     $startDate
	 * @param Date     $endDate
	 * @param Registry $options
	 *
	 * @return array
	 */
	public function fetchEvents($calendarId, Registry $options, Date $startDate = null, Date $endDate = null)
	{
		$s = $startDate;
		if ($s instanceof Date) {
			$s = clone $startDate;
		}
		$e = $endDate;
		if ($e instanceof Date) {
			$e = clone $endDate;
		}

		DPCalendarHelper::increaseMemoryLimit(100 * 1024 * 1024);

		// Remove any time limit
		@set_time_limit(0);

		$content = $this->getContent($calendarId, $s, $e, $options);
		if (empty($content)) {
			return [];
		}
		if (is_array($content)) {
			$content = implode(PHP_EOL, $content);
		}

		\JLoader::import('components.com_dpcalendar.vendor.autoload', JPATH_ADMINISTRATOR);
		$cal = null;

		try {
			$cal = Reader::read($content, Parser::OPTION_IGNORE_INVALID_LINES);
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());
			$this->log('Content is:' . nl2br(substr($content, 0, 200)));

			return [];
		}

		if ($startDate == null) {
			$startDate = DPCalendarHelper::getDate();
		}
		if ($endDate == null) {
			$endDate = DPCalendarHelper::getDate();
			$endDate->modify('+5 year');
		}
		$data = $cal->VEVENT;
		if (empty($data)) {
			return [];
		}

		$originals = [];
		foreach ($cal->VEVENT as $tmp) {
			$originals[] = clone $tmp;
		}

		try {
			if ($options->get('expand', true)) {
				// Needs a timezone rest as e want to work always in UTC
				$startDate->setTimezone(new \DateTimeZone('UTC'));
				$endDate->setTimezone(new \DateTimeZone('UTC'));
				$cal = $cal->expand($startDate, $endDate);
			}
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());

			return [];
		}

		$data = $cal->VEVENT;
		if (empty($data)) {
			return [];
		}

		$tmp = [];
		foreach ($data as $event) {
			$tmp[] = $event;
		}
		$data = $tmp;

		$events      = [];
		$filter      = strtolower($options->get('filter', ''));
		$limit       = $options->get('limit', null);
		$publishDate = $options->get('publish_date') ? DPCalendarHelper::getDate()->toSql() : null;
		$order       = strtolower($options->get('order', 'asc'));

		$dbCal = $this->getDbCal($calendarId);
		foreach ($data as $event) {
			if ($filter !== '' && $filter !== '0') {
				$string = StringHelper::strtolower($event->SUMMARY) . ' ' . StringHelper::strtolower($event->DESCRIPTION) . ' ' . StringHelper::strtolower($event->LOCATION);
				if (!DPCalendarHelper::matches($string, $filter)) {
					continue;
				}
			}

			$tmpEvent                 = $this->createEventFromIcal($event, $calendarId, $originals);
			$tmpEvent->access_content = $dbCal->access_content;

			if (!$this->matchLocationFilterEvent($tmpEvent, $options)) {
				continue;
			}

			// Check if now is before publish up date
			if ($publishDate && $tmpEvent->publish_up && $tmpEvent->publish_up > $publishDate) {
				continue;
			}

			// Check if now is after publish down date
			if ($publishDate && $tmpEvent->publish_down && $tmpEvent->publish_down < $publishDate) {
				continue;
			}

			$at        = strpos($tmpEvent->id, '@');
			$delimiter = strrpos($tmpEvent->id, '_');
			if ($at !== false && $delimiter !== false) {
				$tmpEvent->id = substr_replace($tmpEvent->id, '', $at, $delimiter - $at);
			}

			$events[] = $tmpEvent;
		}

		usort(
			$events,
			static function ($event1, $event2) use ($order): int {
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
			return array_slice($events, 0, $limit);
		}

		return $events;
	}

	protected function fetchCalendars($calendarIds = null)
	{
		if ($this->extCalendarsCache === null) {
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');

			$model = BaseDatabaseModel::getInstance('Extcalendars', 'DPCalendarModel', ['ignore_request' => true]);
			$model->getState();
			$model->setState('filter.plugin', str_replace('dpcalendar_', '', $this->_name));
			$model->setState('filter.state', 1);
			$model->setState('list.limit', -1);
			$model->setState('list.ordering', 'a.ordering');

			$this->extCalendarsCache = $model->getItems();
		}

		$user      = Factory::getUser();
		$calendars = [];
		foreach ($this->extCalendarsCache as $calendar) {
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
			if (!$cal->sync_date) {
				$cal->sync_date = null;
			}

			$cal->sync_token = $calendar->sync_token;

			$action         = $calendar->params->get('action-create', 'false');
			$cal->canCreate = $user->authorise('core.create', 'com_dpcalendar.extcalendar.' . $calendar->id) &&
				($action == 'true' || $action === true || $action == 1);
			$action       = $calendar->params->get('action-edit', 'false');
			$cal->canEdit = $user->authorise('core.edit', 'com_dpcalendar.extcalendar.' . $calendar->id) &&
				($action == 'true' || $action === true || $action == 1);
			$action         = $calendar->params->get('action-delete', 'false');
			$cal->canDelete = $user->authorise('core.delete', 'com_dpcalendar.extcalendar.' . $calendar->id) &&
				($action == 'true' || $action === true || $action == 1);
			$calendars[] = $cal;
		}

		return $calendars;
	}

	protected function getContent($calendarId, Date $startDate = null, Date $endDate = null, Registry $options = null)
	{
		$calendar = $this->getDbCal($calendarId);
		if (empty($calendar)) {
			return '';
		}

		try {
			$content = $this->fetchContent(
				str_replace('webcal://', 'https://', $calendar->params->get('uri')),
				[
					CURLOPT_SSL_VERIFYHOST => $calendar->params->get('ssl_verify') ? 2 : 0,
					CURLOPT_SSL_VERIFYPEER => $calendar->params->get('ssl_verify') ? 1 : 0
				]
			);

			if (strpos($content, 'BEGIN:') === false) {
				throw new \Exception($content);
			}

			$content = str_replace("BEGIN:VCALENDAR\r\n", '', $content);
			$content = str_replace("BEGIN:VCALENDAR\n", '', $content);
			$content = str_replace("\r\nEND:VCALENDAR", '', $content);
			$content = str_replace("\nEND:VCALENDAR", '', $content);

			return "BEGIN:VCALENDAR\n" . $content . "\nEND:VCALENDAR";
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());

			return '';
		}
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
	public function saveEvent($eventId, $calendarId, array $data)
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
	public function deleteEvent($eventId, $calendarId)
	{
		return false;
	}

	/**
	 * Dummy placeholder for plugins which do not support event editing.
	 *
	 * @param string $eventId
	 * @param string $calendarId
	 */
	public function prepareForm($eventId, $calendarId, $form, $data): void
	{
		$this->cleanupFormForEdit($calendarId, $form, $data);
	}

	public function onEventFetch($eventId)
	{
		if (strpos($eventId, (string) $this->identifier) !== 0) {
			return;
		}

		$params = $this->params;

		// Sometimes it changes the id
		$eventId = str_replace($this->identifier . ':', $this->identifier . '-', $eventId);
		$id      = explode('-', str_replace($this->identifier . '-', '', $eventId), 2);
		if (count($id) < 2 || !is_numeric($id[0])) {
			return;
		}

		$cache = Factory::getCache('plg_' . $this->_type . '_' . $this->_name);
		$cache->setCaching($params->get('cache', 1) == '1' && $this->cachingEnabled);
		$cache->setLifeTime($params->get('cache_time', 900) / 60);
		$cache->options['locking'] = false;

		try {
			$event = $cache->get(fn ($eventId, $calendarId) => $this->fetchEvent($eventId, $calendarId), [$id[1], $id[0]]);
			$cache->gc();

			return $event;
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());

			return $this->fetchEvent($id[1], $id[0]);
		}
	}

	public function onEventsFetch($calendarId, Date $startDate = null, Date $endDate = null, Registry $options = null)
	{
		if ($calendarId && strpos($calendarId, (string) $this->identifier) !== 0) {
			return [];
		}

		$params = $this->params;

		$id = str_replace($this->identifier . '-', '', $calendarId ?: '');

		$cache = Factory::getCache('plg_' . $this->_type . '_' . $this->_name, 'callback');
		$cache->setCaching($params->get('cache', 1) == '1' && $this->cachingEnabled);
		$cache->setLifeTime($params->get('cache_time', 900) / 60);
		$cache->options['locking'] = false;

		if (!$options instanceof Registry) {
			$options = new Registry();
		}

		if ($startDate instanceof Date) {
			// If now we cache at least for the minute
			$startDate->setTime($startDate->format('H', true), $startDate->format('i'));
		}

		try {
			// Remove the id, when PHP 8.2.5 is obsolete
			$events = $cache->get(
				fn ($calendarId, ?Date $startDate = null, ?Date $endDate = null, ?Registry $options = null): array => $this->fetchEvents($calendarId, $options, $startDate, $endDate),
				[$id, $startDate, $endDate, $options],
				md5($calendarId . $startDate . $endDate . serialize($options))
			);
			$cache->gc();
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());

			$events = $this->fetchEvents($id, $options, $startDate, $endDate);
		}

		return $events;
	}

	public function onCalendarsFetch($calendarIds = null, $type = null)
	{
		if (!empty($type) && $this->identifier != $type) {
			return [];
		}

		$ids = [];
		if (!empty($calendarIds)) {
			if (!is_array($calendarIds)) {
				$calendarIds = [$calendarIds];
			}
			foreach ($calendarIds as $calendarId) {
				if (strpos($calendarId, (string) $this->identifier) === 0) {
					$ids[] = (int)str_replace($this->identifier . '-', '', $calendarId);
				}
			}
			if ($ids === []) {
				return [];
			}
		}

		return $this->fetchCalendars($ids);
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
		if (strpos($data['catid'], $this->identifier . '-') !== 0) {
			return false;
		}

		$calendarId = str_replace($this->identifier . '-', '', $data['catid']);

		$calendar = $this->getDbCal($calendarId);

		if ((!isset($data['id']) || empty($data['id'])) && !$calendar->canCreate) {
			// No create permission
			Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

			return false;
		}

		if (isset($data['id']) && $data['id'] && !$calendar->canEdit) {
			// No edit permission
			Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

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
			$cache = Factory::getCache('plg_' . $this->_type . '_' . $this->_name);
			$cache->clean();
		}

		return $newEventId;
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
		if (strpos($eventId, $this->identifier . '-') !== 0) {
			return false;
		}

		$eventId = str_replace($this->identifier . ':', $this->identifier . '-', $eventId);
		$id      = explode('-', str_replace($this->identifier . '-', '', $eventId), 2);
		if (count($id) < 2) {
			return false;
		}

		$calendar = $this->getDbCal($id[0]);
		if (!$calendar->canDelete) {
			Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');

			return false;
		}

		$success = $this->deleteEvent($id[1], $id[0]);
		if ($success != false) {
			$cache = Factory::getCache('plg_' . $this->_type . '_' . $this->_name);
			$cache->clean();
		}

		return $success;
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
		if (!($form instanceof Form)) {
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		if ($form->getName() != 'com_dpcalendar.event') {
			return true;
		}

		$data = (object)$data;

		$catId = null;
		if (!empty($data->catid)) {
			$catId = $data->catid;
		}

		// When there is no calendar, then get it from the form
		if (empty($catId) && $formField = $form->getField('catid')) {
			$catId = $formField->getAttribute('default', null);

			// Do not print errors
			$oldErrorHandling = libxml_use_internal_errors(true);
			try {
				// Decode the input first when calendars have accents in titles
				$decoded = html_entity_decode($formField->__get('input'), ENT_QUOTES, 'utf-8');
				$xml     = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?>' . $decoded);

				// Get the available calendars
				$options = $xml->xpath('//option');

				// Choose the first available calendar
				if (!$catId && $options && $firstChoice = reset($options)) {
					$catId = (string)$firstChoice->attributes()->value;
				}
			} catch (\Exception $e) {
				// Ignore
			}

			// Cleanup
			libxml_clear_errors();
			libxml_use_internal_errors($oldErrorHandling);
		}

		if (!$catId || strpos($catId, (string) $this->identifier) !== 0) {
			return true;
		}

		$eventId = '';
		if (!empty($data->id)) {
			$id = str_replace($this->identifier . ':', $this->identifier . '-', $data->id);
			$id = explode('-', str_replace($this->identifier . '-', '', $id), 2);
			if (count($id) == 2) {
				$eventId = [1];
			}
		}

		return $this->prepareForm($eventId, str_replace($this->identifier . '-', '', $catId), $form, $data);
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
		$calendar->params          = new Registry();
		$calendar->native          = $this->params->get('cache', 1) == 2;

		return $calendar;
	}

	/**
	 *
	 * @return \stdClass
	 */
	protected function createEvent($id, $calendarId)
	{
		$event                                    = new \stdClass();
		$event->id                                = $this->identifier . '-' . $calendarId . '-' . $id;
		$event->alias                             = $id;
		$event->catid                             = $this->identifier . '-' . $calendarId;
		$event->category_access                   = 1;
		$event->category_alias                    = $calendarId;
		$event->category_title                    = DPCalendarHelper::getCalendar($event->catid)->title;
		$event->parent_alias                      = '';
		$event->parent_id                         = 0;
		$event->original_id                       = 0;
		$event->title                             = '';
		$event->rrule                             = null;
		$event->exdates                           = null;
		$event->recurrence_id                     = null;
		$event->start_date                        = '';
		$event->end_date                          = '';
		$event->show_end_time                     = true;
		$event->all_day                           = 0;
		$event->color                             = '';
		$event->url                               = '';
		$event->price                             = [];
		$event->locations                         = [];
		$event->rooms                             = [];
		$event->roomTitles                        = [];
		$event->hits                              = 0;
		$event->capacity                          = 0;
		$event->capacity_used                     = 0;
		$event->waiting_list_count                = 0;
		$event->booking_options                   = null;
		$event->booking_waiting_list              = 0;
		$event->booking_series                    = 0;
		$event->booking_opening_date              = null;
		$event->booking_closing_date              = null;
		$event->event_booking_cancel_closing_date = null;
		$event->description                       = '';
		$event->schedule                          = '';
		$event->state                             = 1;
		$event->access                            = 1;
		$event->access_content                    = 1;
		$event->language                          = '*';
		$event->created                           = '';
		$event->created_by                        = 0;
		$event->created_by_alias                  = '';
		$event->host_ids                          = '';
		$event->hostContacts                      = [];
		$event->modified                          = '';
		$event->modified_by                       = 0;
		$event->params                            = '';
		$event->metadesc                          = null;
		$event->metakey                           = null;
		$event->metadata                          = new Registry();
		$event->author                            = null;
		$event->xreference                        = $event->id;
		$event->images                            = null;
		$event->checked_out                       = null;
		$event->publish_up                        = null;
		$event->publish_down                      = null;

		return $event;
	}

	/**
	 *
	 * @return \stdClass
	 */
	private function createEventFromIcal(VEvent $event, $calendarId, array $originals)
	{
		$allDay = !$event->DTSTART->hasTime();
		// Microsoft has a special property to flag all day events
		if (isset($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'})) {
			$allDay = strtolower($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'}) == 'true';
		}

		$startDate = DPCalendarHelper::getDate($event->DTSTART->getDateTime()->format('U'), $allDay);

		$endDate = null;
		if ($event->DURATION != null) {
			$endDate  = clone $startDate;
			$duration = DateTimeParser::parseDuration($event->DURATION, true);
			$endDate->modify($duration);
			if ($allDay) {
				$endDate->modify('-1 day');
			}
		} elseif (!$event->DTEND) {
			$endDate = clone $startDate;
			$endDate->setTime(23, 59, 59);
		} else {
			$endDate = DPCalendarHelper::getDate($event->DTEND->getDateTime()->format('U'), $allDay);
			if ($allDay) {
				$endDate->modify('-1 day');
			}
		}

		// Search for the original to get the rrule
		$original = null;
		foreach ($originals as $tmp) {
			if ((string)$tmp->UID === (string)$event->UID && $tmp->{'RECURRENCE-ID'} === null && $tmp->RRULE !== null) {
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
				if ($recurrenceDate != $o->DTSTART->getDateTime()->format('U')) {
					continue;
				}
				if ((string)$o->UID !== (string)$event->UID) {
					continue;
				}
				if ($o->RRULE !== null) {
					continue;
				}
				$event = $o;
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

		$title           = (string)$event->SUMMARY;
		$title           = str_replace('\n', ' ', $title);
		$title           = str_replace('\N', ' ', $title);
		$tmpEvent->title = Ical::icalDecode($title);

		$tmpEvent->alias       = ApplicationHelper::stringURLSafe($tmpEvent->title);
		$tmpEvent->description = Ical::icalDecode((string)$event->DESCRIPTION);

		// When no tags exist, then convert new lines to br's
		if (strip_tags($tmpEvent->description) === $tmpEvent->description) {
			$tmpEvent->description = nl2br($tmpEvent->description);
		}

		$created = $event->CREATED;
		if (!empty($created)) {
			$tmpEvent->created = DPCalendarHelper::getDate($created->getDateTime()->format('U'))->toSql();
		}
		$modified = $event->{'LAST-MODIFIED'};
		if (!empty($modified)) {
			$tmpEvent->modified = DPCalendarHelper::getDate($modified->getDateTime()->format('U'))->toSql();
		}

		$description = (string)$event->{'X-ALT-DESC'};
		if ($description !== '' && $description !== '0') {
			$desc = $description;
			if (is_array($desc)) {
				$desc = implode(' ', $desc);
			}
			$tmpEvent->description = Ical::icalDecode($desc);
		}

		$author = (string)$event->ORGANIZER;
		if ($author !== '' && $author !== '0') {
			$tmpEvent->created_by_alias = str_replace('MAILTO:', '', $author);
		}

		if (isset($event->ATTENDEE)) {
			$tmpEvent->bookings = [];
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
				if ($booking->name === '') {
					continue;
				}
				if ($booking->name === '0') {
					continue;
				}
				$tmpEvent->bookings[] = $booking;
			}
		}

		// Add none standard properties
		$color = (string)$event->{'x-color'};
		if ($color !== '' && $color !== '0' && !DPCalendarHelper::getCalendar($tmpEvent->catid)->color_force) {
			$tmpEvent->color = $color;
		}

		$url = (string)$event->URL;
		if ($url !== '' && $url !== '0') {
			$tmpEvent->url = $url;
		}

		$url = (string)$event->{'x-url'};
		if ($url !== '' && $url !== '0') {
			$tmpEvent->url = $url;
		}

		$alias = (string)$event->{'x-alias'};
		if ($alias !== '' && $alias !== '0') {
			$tmpEvent->alias = $alias;
		}

		$language = (string)$event->{'x-language'};
		if ($language !== '' && $language !== '0') {
			$tmpEvent->language = $language;
		}

		$publishUp = (string)$event->{'x-publish-up'};
		if ($publishUp !== '' && $publishUp !== '0') {
			$tmpEvent->publish_up = $publishUp;
		}

		$publishDown = (string)$event->{'x-publish-down'};
		if ($publishDown !== '' && $publishDown !== '0') {
			$tmpEvent->publish_down = $publishDown;
		}

		$tmpEvent->images = new \stdClass();
		$tmpImageData     = (string)$event->{'x-image'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_full = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-full'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_full = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-full-alt'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_full_alt = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-full-caption'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_full_caption = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-intro'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_intro = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-intro-alt'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_intro_alt = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-intro-caption'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_intro_caption = $tmpImageData;
		}

		$showEndTime = $event->{'x-show-end-time'};
		if ($showEndTime) {
			$tmpEvent->show_end_time = (string)$showEndTime != '0';
		}

		if ($event->ATTACH) {
			foreach ($event->ATTACH as $attachment) {
				if (!$attachment->parameters || !array_key_exists('FMTTYPE', $attachment->parameters)) {
					continue;
				}

				if (strpos($attachment->parameters['FMTTYPE']->getValue(), 'image/') !== 0) {
					continue;
				}

				$tmpEvent->images = (object)['image_full' => $attachment->getValue(), 'image_intro' => $attachment->getValue()];
			}
		}

		$location  = (string)$event->LOCATION;
		$locations = [];
		if ($location !== '' && $location !== '0') {
			$geo = (string)$event->GEO;
			if ($geo !== '' && $geo !== '0' && strpos($geo, ';') !== false) {
				static $locationModel = null;
				if ($locationModel == null) {
					BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
					$locationModel = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
					$locationModel->getState();
					$locationModel->setState('list.limit', 1);
				}
				[$latitude, $longitude] = explode(';', $geo);
				$locationModel->setState('filter.latitude', $latitude);
				$locationModel->setState('filter.longitude', $longitude);

				$tmp = $locationModel->getItems();
				if (!empty($tmp)) {
					$locations = $tmp;

					$tmpEvent->location_ids = [];
					foreach ($tmp as $dpLocation) {
						if ($dpLocation->state != 1) {
							continue;
						}
						$tmpEvent->location_ids[] = $dpLocation->id;
					}
				} else {
					[$latitude, $longitude] = explode(';', $geo);

					$locations[] = Location::get($latitude . ',' . $longitude, true, $location);
				}
			} else {
				$locations[] = Location::get(Ical::icalDecode($location));
			}
		}
		$tmpEvent->locations = array_filter($locations, static fn ($location): bool => (int)$location->state === 1);
		$tmpEvent->all_day   = $allDay ? 1 : 0;

		if ($original !== null && $recId !== null) {
			$tmpEvent->original_id = $this->identifier . '-' . $calendarId . '-' . $event->UID . '_0';
		}
		if ($original !== null && $recId === null) {
			$tmpEvent->rrule       = (string)$original->RRULE;
			$tmpEvent->original_id = -1;
		}

		return $tmpEvent;
	}

	protected function getDbCal($calendarId)
	{
		$calendars = $this->fetchCalendars([$calendarId]);
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
		return str_replace(["\r\n", "\r", "\n"], $replace, $text);
	}

	protected function log($message)
	{
		Factory::getApplication()->enqueueMessage((string)$message, 'warning');
	}

	protected function matchLocationFilterEvent($event, Registry $options)
	{
		if ($options->get('radius') == -1) {
			return true;
		}
		$location    = $options->get('location');
		$locationIds = $options->get('location_ids', []);
		if (empty($location) && empty($locationIds)) {
			return true;
		}

		$locationFilterData            = new \stdClass();
		$locationFilterData->latitude  = null;
		$locationFilterData->longitude = null;

		if (is_object($location)) {
			$locationFilterData = $location;
		}

		$radius = $options->get('radius');
		if ($options->get('length-type') == 'm') {
			$radius *= 0.62137119;
		}

		if (!$locationFilterData->latitude
			&& is_string($location) && strpos($location, 'latitude=') !== false && strpos($location, 'longitude=') !== false) {
			[$latitude, $longitude]        = explode(';', $location);
			$locationFilterData->latitude  = str_replace('latitude=', '', $latitude);
			$locationFilterData->longitude = str_replace('longitude=', '', $longitude);
		} elseif (!$locationFilterData->latitude && !empty($location) && is_string($location)) {
			$locationFilterData = Location::get($location);
		}

		$within = false;
		foreach ($event->locations as $loc) {
			if (!in_array($loc->id, $locationIds) &&
				!Location::within($loc, $locationFilterData->latitude, $locationFilterData->longitude, $radius)
			) {
				continue;
			}
			$within = true;
			break;
		}

		return $within;
	}

	protected function cleanupFormForEdit($ccalendarId, Form $form, $data)
	{
		$hideFieldsets             = [];
		$hideFieldsets['params']   = 'jbasic';
		$hideFieldsets[]           = 'booking';
		$hideFieldsets[]           = 'publishing';
		$hideFieldsets['metadata'] = 'jmetadata';

		foreach ($hideFieldsets as $group => $name) {
			foreach ($form->getFieldset($name) as $field) {
				if (!is_string($group)) {
					$group = null;
				}

				if ($field->fieldname == 'xreference') {
					continue;
				}

				$form->removeField(DPCalendarHelper::getFieldName($field), $group);
			}
		}

		$form->removeField('show_end_time');
		$form->removeField('alias');
		$form->removeField('state');
		$form->removeField('schedule');
		$form->removeField('publish_up');
		$form->removeField('publish_down');
		$form->removeField('access');
		$form->removeField('featured');
		$form->removeField('access_content');
		$form->removeField('language');
		$form->removeField('metadesc');
		$form->removeField('metakey');
		$form->removeField('price');
		$form->removeField('earlybird');
		$form->removeField('image_intro', 'images');
		$form->removeField('image_intro_alt', 'images');
		$form->removeField('image_intro_caption', 'images');
		$form->removeField('spacer1', 'images');
		$form->removeField('image_full', 'images');
		$form->removeField('image_full_alt', 'images');
		$form->removeField('image_full_caption', 'images');
		$form->removeField('rooms');
		$form->setFieldAttribute('location_ids', 'multiple', false);

		foreach ($form->getGroup('com_fields') as $item) {
			$form->removeField($item->fieldname, $item->group);
		}
	}

	protected function fetchContent($uri, $curlOptions = [])
	{
		if (empty($uri)) {
			return '';
		}

		$internal = !filter_var($uri, FILTER_VALIDATE_URL);
		if ($internal && strpos($uri, '/') !== 0) {
			$uri = JPATH_ROOT . '/' . $uri;
		}

		if ($internal && Folder::exists($uri)) {
			$content = '';
			foreach (Folder::files($uri, '\.ics', true, true) as $file) {
				$content .= file_get_contents($file);
			}

			return $content;
		}

		if ($internal) {
			return file_get_contents($uri);
		}

		$u   = Uri::getInstance($uri);
		$uri = $u->toString(['scheme', 'user', 'pass', 'host', 'port', 'path']);
		$uri .= $u->toString(['query', 'fragment']);

		$headers = [
			'Accept-Language: ' . Factory::getUser()->getParam('language', Factory::getLanguage()->getTag()),
			'Accept: */*'
		];
		$data = (new HTTP())->get($uri, null, null, $headers, $curlOptions);
		if (!empty($data->dp->headers['Content-Encoding']) && $data->dp->headers['Content-Encoding'] == 'gzip') {
			return gzinflate(substr($data->dp->body, 10, -8));
		}

		return $data->dp->body;
	}
}
