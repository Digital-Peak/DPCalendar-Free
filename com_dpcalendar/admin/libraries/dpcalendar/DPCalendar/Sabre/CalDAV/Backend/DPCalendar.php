<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2013 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Sabre\CalDAV\Backend;

use Sabre\VObject;
use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;

\JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
\JLoader::import('components.com_dpcalendar.helpers.ical', JPATH_ADMINISTRATOR);

\JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

class DPCalendar extends CalDAV\Backend\PDO
{
	public function getCalendarsForUser($principalUri)
	{
		$calendars = parent::getCalendarsForUser($principalUri);

		$user = \JFactory::getUser();

		// The calendar instance to get the calendars from
		$cal = \DPCalendarHelper::getCalendar('root');

		// Check if we are a guest
		if ($user->guest) {
			// Get the calendar and ignoring the access flag, this is needed on authentication
			$cal = \JCategories::getInstance('DPCalendar', array('access' => false))->get('root');
		}

		foreach ($cal->getChildren(true) as $calendar) {
			$writePermission = $user->authorise('core.edit', 'com_dpcalendar.category.' . $calendar->id) &&
				$user->authorise('core.delete', 'com_dpcalendar.category.' . $calendar->id);

			$params      = new \JRegistry($calendar->params);
			$calendars[] = array(
				'id'                                                                 => 'dp-' . $calendar->id,
				'uri'                                                                => 'dp-' . $calendar->id,
				'principaluri'                                                       => $principalUri,
				'{' . CalDAV\Plugin::NS_CALENDARSERVER . '}getctag'                  => $params->get('etag', 1),
				'{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(array(
					'VEVENT',
					'VTODO'
				)),
				'{DAV:}displayname'                                                  => $calendar->title,
				'{urn:ietf:params:xml:ns:caldav}calendar-description'                => $calendar->description,
				'{urn:ietf:params:xml:ns:caldav}calendar-timezone'                   => '',
				'{http://apple.com/ns/ical/}calendar-order'                          => 1,
				'{http://apple.com/ns/ical/}calendar-color'                          => $params->get('color', '3366CC')
			);
		}

		return $calendars;
	}

	public function getMultipleCalendarObjects($calendarId, array $uris)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			$model = \JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
			$model->setState('category.id', str_replace('dp-', '', $calendarId));
			$model->setState('category.recursive', false);
			$model->setState('list.limit', 10000);
			$model->setState('filter.ongoing', true);
			$model->setState('filter.state', 1);
			$model->setState('filter.language', \JFactory::getLanguage());
			$model->setState('filter.publish_date', true);
			$model->setState('list.start-date', '0');
			$model->setState('list.end-date', \DPCalendarHelper::getDate(self::MAX_DATE)->format('U'));
			$model->setState('list.ordering', 'start_date');
			$model->setState('list.direction', 'asc');
			$model->setState('filter.expand', false);

			$data = array();
			foreach ($model->getItems() as $event) {
				if (key_exists($event->uid, $data) || $event->original_id > 0) {
					continue;
				}
				$data[$event->uid] = $this->toSabreArray($event);
			}
			$this->log('Getting multiple calendar objects ' . implode(',', $uris) . ' on calendar ' . $calendarId);

			return $data;
		}

		return parent::getMultipleCalendarObjects($calendarId, $uris);
	}

	public function getCalendarObject($calendarId, $objectUri)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			$event = $this->getTable();
			$event->load(array('uid' => $objectUri));

			// If we hit an instance, load the original event
			if ($event->original_id > 0) {
				$event->load(array('id' => $event->original_id));
			}

			if (!empty($event->id)) {
				// The event needs to be loaded trough the model to get
				// locations, tags, etc.
				$model = \JModelLegacy::getInstance('Event', 'DPCalendarModel', array('ignore_request' => true));
				$event = $model->getItem($event->id);
				$this->log('Getting calendar object ' . $objectUri . ' on calendar ' . $calendarId);

				return $this->toSabreArray($event);
			}

			return null;
		}

		return parent::getCalendarObject($calendarId, $objectUri);
	}

	public function getCalendarObjects($calendarId)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			$model = \JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
			$model->setState('category.id', str_replace('dp-', '', $calendarId));
			$model->setState('category.recursive', false);
			$model->setState('list.limit', 10000);
			$model->setState('filter.ongoing', true);
			$model->setState('filter.state', 1);
			$model->setState('filter.language', \JFactory::getLanguage());
			$model->setState('filter.publish_date', true);
			$model->setState('list.start-date', '0');
			$model->setState('list.end-date', \DPCalendarHelper::getDate(self::MAX_DATE)->format('U'));
			$model->setState('list.ordering', 'start_date');
			$model->setState('list.direction', 'asc');
			$model->setState('filter.expand', false);

			$data = array();
			foreach ($model->getItems() as $event) {
				if (key_exists($event->uid, $data) || $event->original_id > 0) {
					continue;
				}
				$data[$event->uid] = $this->toSabreArray($event);
			}

			return $data;
		}

		return parent::getCalendarObjects($calendarId);
	}

	public function calendarQuery($calendarId, array $filters)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			$timeRange = array();
			if (count($filters['comp-filters']) > 0 && !$filters['comp-filters'][0]['is-not-defined']) {
				$componentType = $filters['comp-filters'][0]['name'];

				if ($componentType == 'VEVENT' && isset($filters['comp-filters'][0]['time-range'])) {
					$timeRange = $filters['comp-filters'][0]['time-range'];
				}
			}

			$model = \JModelLegacy::getInstance('Events', 'DPCalendarModel');
			$model->getState();
			$model->setState('list.limit', 1000);
			$model->setState('category.id', str_replace('dp-', '', $calendarId));
			$model->setState('category.recursive', true);
			$model->setState('filter.ongoing', 1);

			if (is_array($timeRange) && key_exists('start', $timeRange) && !empty($timeRange['start'])) {
				$model->setState('list.start-date', $timeRange['start']->getTimeStamp());
			}
			if (is_array($timeRange) && key_exists('end', $timeRange) && !empty($timeRange['end'])) {
				$model->setState('list.end-date', $timeRange['end']->getTimeStamp());
			}

			$data = array();
			foreach ($model->getItems() as $event) {
				if (!$this->validateFilterForObject(array('uri' => $event->uid, 'calendarid' => $calendarId), $filters)) {
					continue;
				}
				$data[$event->uid] = $event->uid;
			}

			return $data;
		}

		return parent::calendarQuery($calendarId, $filters);
	}

	public function createCalendarObject($calendarId, $objectUri, $calendarData)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			$this->log('Creating calendar object ' . $objectUri . ' on calendar ' . $calendarId);

			$calendar = \DPCalendarHelper::getCalendar(str_replace('dp-', '', $calendarId));
			if (!$calendar || !$calendar->canCreate) {
				$this->log('No permission to create ' . $objectUri . ' on calendar ' . $calendarId);
				throw new Forbidden();
			}

			$event        = $this->getTable();
			$vEvent       = VObject\Reader::read($calendarData)->VEVENT;
			$event->alias = \JApplicationHelper::stringURLSafe($vEvent->SUMMARY->getValue());
			$event->catid = str_replace('dp-', '', $calendarId);
			$event->state = 1;
			$event->uid   = $objectUri;

			$this->merge($event, $vEvent);
			\DPCalendarHelper::increaseEtag($event->catid);

			return null;
		}

		return parent::createCalendarObject($calendarId, $objectUri, $calendarData);
	}

	public function updateCalendarObject($calendarId, $objectUri, $calendarData)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			$this->log('Updating calendar object ' . $objectUri . ' on calendar ' . $calendarId);

			$calendar = \DPCalendarHelper::getCalendar(str_replace('dp-', '', $calendarId));
			if (!$calendar || !$calendar->canEdit) {
				$this->log('No permission to update ' . $objectUri . ' on calendar ' . $calendarId);
				throw new Forbidden();
			}

			$event = $this->getTable();
			$event->load(array('uid' => $objectUri));
			$obj = VObject\Reader::read($calendarData);

			if ($event->original_id == '-1') {
				foreach ($obj->VEVENT as $vEvent) {
					if ($vEvent->RRULE && $vEvent->RRULE->getValue()) {
						$this->merge($event, $vEvent);
					}
				}

				$db = \JFactory::getDbo();
				$db->setQuery('select * from #__dpcalendar_events where original_id = ' . $db->quote($event->id));
				$children = $db->loadObjectList('', 'DPCalendarTableEvent');

				foreach ($obj->VEVENT as $vEvent) {
					if (!isset($vEvent->{'RECURRENCE-ID'})) {
						continue;
					}
					$startDate = (string)$vEvent->{'RECURRENCE-ID'}->getValue();

					foreach ($children as $child) {
						if ($child->recurrence_id == $startDate) {
							$this->merge($child, $vEvent);
							break;
						}
					}
				}
			} else {
				$this->merge($event, $obj->VEVENT);
			}

			\DPCalendarHelper::increaseEtag($event->catid);

			return null;
		}

		return parent::updateCalendarObject($calendarId, $objectUri, $calendarData);
	}

	public function deleteCalendarObject($calendarId, $objectUri)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			$this->log('Deleting calendar object ' . $objectUri . ' on calendar ' . $calendarId);

			$calendar = \DPCalendarHelper::getCalendar(str_replace('dp-', '', $calendarId));
			if (!$calendar || (!$calendar->canDelete && !$calendar->canEditOwn)) {
				$this->log('No permission to delete ' . $objectUri . ' on calendar ' . $calendarId);
				throw new Forbidden();
			}

			$event = $this->getTable();
			$event->load(array('uid' => $objectUri));

			if (!$calendar->canDelete && $event->created_by != \JFactory::getUser()->id) {
				$this->log('No permission to delete ' . $objectUri . ' on calendar ' . $calendarId . ' because not the owner');
				throw new Forbidden();
			}

			$event->state = -2;
			$event->store();
			$model = \JModelLegacy::getInstance(
				'Form',
				'DPCalendarModel',
				array('event_before_delete' => 'nooperationtocatch', 'event_after_delete' => 'nooperationtocatch')
			);
			$model->delete($event->id);

			if ($model->getError()) {
				throw new \Sabre\DAV\Exception\BadRequest('Error happened deleting the event: ' . $model->getError());
			}

			\DPCalendarHelper::increaseEtag(str_replace('dp-', '', $calendarId));

			return;
		}

		return parent::deleteCalendarObject($calendarId, $objectUri);
	}

	public function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			$this->log('Update calendar ' . $calendarId . 'with propatch');
			\DPCalendarHelper::increaseEtag(str_replace('dp-', '', $calendarId));
			return;
		}

		return parent::updateCalendar($calendarId, $propPatch);
	}

	private function getTable($type = 'Event')
	{
		\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');

		return \JTable::getInstance($type, 'DPCalendarTable');
	}

	private function merge(\JTable $dpEvent, $vEvent)
	{
		if (isset($vEvent->SUMMARY)) {
			$dpEvent->title = $vEvent->SUMMARY->getValue();
		} else {
			$dpEvent->title = '(no title)';
		}

		if (isset($vEvent->DESCRIPTION)) {
			$dpEvent->description = $vEvent->DESCRIPTION->getValue();
		}
		$dpEvent->all_day = strlen($vEvent->DTSTART->getValue()) > 10 ? 0 : 1;

		$start = $vEvent->DTSTART->getDateTime();
		if ($dpEvent->all_day) {
			$start = $start->setTime(0, 0, 0);
		} else {
			$start = $start->setTimezone(new \DateTimeZone('UTC'));
		}
		$dpEvent->start_date = $start->format(\JFactory::getDbo()->getDateFormat());

		$end = $vEvent->DTEND->getDateTime();
		if ($dpEvent->all_day) {
			$end = $end->setTime(0, 0, 0);
			$end = $end->modify('-1 day');
		} else {
			$end = $end->setTimezone(new \DateTimeZone('UTC'));
		}
		$dpEvent->end_date = $end->format(\JFactory::getDbo()->getDateFormat());

		/*
		 * Most CalDAV clients do not support this attribute, means it will
		 * revert the description when updating a native DPCalendar event.
		 * if (isset($vEvent->{'X-ALT-DESC'}) &&
		 * $vEvent->{'X-ALT-DESC'}->getValue())
		 * {
		 * $dpEvent->description = $vEvent->{'X-ALT-DESC'}->getValue();
		 * }
		 */

		if (isset($vEvent->{'X-COLOR'}) && $vEvent->{'X-COLOR'}->getValue()) {
			$dpEvent->color = $vEvent->{'X-COLOR'}->getValue();
		}
		if (isset($vEvent->{'X-URL'}) && $vEvent->{'X-URL'}->getValue()) {
			$dpEvent->url = $vEvent->{'X-URL'}->getValue();
		}
		if (isset($vEvent->RRULE) && $vEvent->RRULE->getValue()) {
			$dpEvent->rrule = $vEvent->RRULE->getValue();
		}
		if (isset($vEvent->LOCATION) && $vEvent->LOCATION->getValue()) {
			$locationString = $vEvent->LOCATION->getValue();

			// The ical creator escapes , and ; so we need to turn them back
			$locationString = str_replace('\,', ',', $locationString);
			$locationString = str_replace('\;', ';', $locationString);

			\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$location = null;
			if (isset($vEvent->GEO) && $vEvent->GEO->getValue()) {
				$parts = explode(';', $vEvent->GEO->getValue());
				if (count($parts) == 2) {
					$model = \JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
					$model->getState();
					$model->setState('list.limit', 1);
					$model->setState('filter.latitude', $parts[0]);
					$model->setState('filter.longitude', $parts[1]);

					$locations = $model->getItems();
					if (!empty($locations)) {
						$location = reset($locations);
					}
				}
			}

			if (!$location) {
				$model = \JModelLegacy::getInstance('Locations', 'DPCalendarModel');
				$model->getState();
				$model->setState('list.limit', 10000);

				$locations = $model->getItems();
				foreach ($locations as $l) {
					if ($l->title == $locationString || $l->alias == $locationString || \DPCalendar\Helper\Location::format($l) == $locationString) {
						$location = $l;
						break;
					}
				}
				if (!$location) {
					$location = \DPCalendar\Helper\Location::get($locationString);
				}
			}
			if ($location) {
				$dpEvent->location_ids = array($location->id);
			}
		}

		$model = \JModelLegacy::getInstance(
			'Form',
			'DPCalendarModel',
			array('event_before_save' => 'nooperationtocatch', 'event_after_save' => 'nooperationtocatch')
		);
		$model->save($dpEvent->getProperties());

		if ($model->getError()) {
			throw new \Sabre\DAV\Exception\BadRequest('Error happened storing the event: ' . $model->getError());
		}
	}

	private function toSabreArray($event)
	{
		$ical = \DPCalendar\Helper\Ical::createIcalFromEvents(array($event));
		$data = array(
			'id'           => $event->id,
			'uri'          => $event->uid,
			'lastmodified' => \DPCalendarHelper::getDate($event->modified)->format('U'),
			'calendarid'   => 'dp-' . $event->catid,
			'size'         => strlen($ical),
			'etag'         => '"' . md5($ical) . '"',
			'calendardata' => $ical
		);

		return $data;
	}

	private function log($message)
	{
		$path = JPATH_ROOT . '/logs/dpcalendar.debug.log';
		file_put_contents($path, date('c') . ' ' . $message . PHP_EOL, FILE_APPEND);
	}
}
