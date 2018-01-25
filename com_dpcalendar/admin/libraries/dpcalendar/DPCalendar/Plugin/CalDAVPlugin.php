<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Plugin;

defined('_JEXEC') or die();

abstract class CalDAVPlugin extends SyncPlugin
{

	abstract protected function createCalDAVEvent($uid, $icalData, $calendarId);

	abstract protected function updateCalDAVEvent($uid, $icalData, $calendarId);

	abstract protected function deleteCalDAVEvent($uid, $calendarId);

	abstract protected function getOriginalData($uid, $calendarId);

	public function deleteEvent($eventId, $calendarId)
	{
		$oldEvent = $this->fetchEvent($eventId, $calendarId);
		$eventId  = substr($eventId, 0, strrpos($eventId, '_'));

		if (empty($oldEvent->original_id) || $oldEvent->original_id == '-1') {
			$this->deleteCalDAVEvent($eventId, $calendarId);
		} else {
			$calendar = \DPCalendarHelper::getCalendar($oldEvent->catid);

			\JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);

			$c = \Sabre\VObject\Reader::read($this->getOriginalData($eventId, $calendarId));

			$original = null;
			foreach ($c->children as $index => $tmp) {
				if ($tmp->name != 'VEVENT') {
					continue;
				}
				if ((string)$tmp->{'RECURRENCE-ID'} == $oldEvent->recurrence_id) {
					unset($c->children[$index]);
				}
				if ($tmp->RRULE !== null) {
					$original = $tmp;
				}
			}

			$exdate = $original->EXDATE;
			if ($exdate === null) {
				$original->add('EXDATE', '');
				$original->EXDATE->add('VALUE', 'DATE' . ($oldEvent->all_day ? '' : '-TIME'));
			}
			$rec = \DPCalendarHelper::getDate($oldEvent->start_date, $oldEvent->all_day)->format('Ymd' . ($oldEvent->all_day ? '' : '\THis\Z'));

			try {
				$original->EXDATE = trim($exdate . ',' . $rec, ',');

				// Echo '<pre>' . $c->serialize() . '</pre>'; die();
				$this->updateCalDAVEvent($eventId, $c->serialize(), $calendarId);
			} catch (\Sabre\VObject\Recur\NoInstancesException $e) {
				$this->deleteCalDAVEvent($eventId, $calendarId);
			}
		}

		return true;
	}

	public function saveEvent($eventId = null, $calendarId, array $data)
	{
		$params = $this->params;
		try {
			$calendar = \DPCalendarHelper::getCalendar($data['catid']);
			$event    = null;
			$oldEvent = null;
			if (!empty($eventId)) {
				$event = \JTable::getInstance('Event', 'DPCalendarTable');
				$event->bind($this->fetchEvent($eventId, $calendarId));
				$oldEvent = clone $event;
			} else {
				$event = \JTable::getInstance('Event', 'DPCalendarTable');
			}
			$event->bind($data);
			$event->id             = $eventId;
			$event->category_title = $calendar->title;
			if (isset($data['location'])) {
				$event->locations = array(\DPCalendar\Helper\Location::get($data['location'], false));
			}
			if (isset($data['location_ids']) && is_array($data['location_ids'])) {
				$event->locations = \DPCalendar\Helper\Location::getLocations($data['location_ids']);
			}
			$ical = \DPCalendar\Helper\Ical::createIcalFromEvents(array($event));

			$start = strpos($ical, 'UID:') + 4;
			$end   = strpos($ical, PHP_EOL, $start + 1);
			$uid   = substr($ical, $start, $end - $start);
			if (empty($eventId)) {
				$eventId = $uid;
				$this->createCalDAVEvent($uid, $ical, $calendarId);
			} else {
				$eventId = substr($eventId, 0, strrpos($eventId, '_'));
				$ical    = str_replace($uid, $eventId, $ical);

				\JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);

				$c      = \Sabre\VObject\Reader::read($this->getOriginalData($eventId, $calendarId));
				$vevent = null;
				foreach ($c->VEVENT as $index => $tmp) {
					if ((string)$tmp->UID != $eventId) {
						continue;
					}
					if ($event->original_id == '0') {
						$vevent = $tmp;
						break;
					} else if ((string)$tmp->{'RECURRENCE-ID'} == $event->recurrence_id) {
						$vevent = $tmp;
						break;
					}
				}
				if ($vevent === null) {
					$vevent = \Sabre\VObject\Component::create('VEVENT');
					$c->add($vevent);
					$vevent->UID = $eventId;
					if (!empty($event->original_id) && $event->original_id != '-1') {
						if ($oldEvent->all_day) {
							$rec                       = \DPCalendarHelper::getDate($oldEvent->start_date, $oldEvent->all_day)->format('Ymd');
							$vevent->{'RECURRENCE-ID'} = $rec;
							$vevent->{'RECURRENCE-ID'}->add('VALUE', 'DATE');
						} else {
							$rec                       = \DPCalendarHelper::getDate($oldEvent->start_date, $oldEvent->all_day)->format('Ymd\THis\Z');
							$vevent->{'RECURRENCE-ID'} = $rec;
							$vevent->{'RECURRENCE-ID'}->add('VALUE', 'DATE-TIME');
						}
					}
				} else if ($event->original_id == '0' || $event->original_id == '-1') {
					unset($c->VEVENT);
					unset($vevent->EXDATE);
					$c->add($vevent);
				}

				$vevent->SUMMARY = $event->title;

				$vevent->DESCRIPTION    = \JFilterInput::getInstance()->clean(preg_replace('/\r\n?/', "\n", $event->description));
				$vevent->{'X-ALT-DESC'} = preg_replace('/\r\n?/', "", $event->description);
				$vevent->{'X-ALT-DESC'}->add('FMTTYPE', 'text/html');

				$startDate = \DPCalendarHelper::getDate($event->start_date, $event->all_day == 1);
				$endDate   = \DPCalendarHelper::getDate($event->end_date, $event->all_day);
				if ($event->all_day == 1) {
					$endDate->modify('+1 day');
					$vevent->DTSTART = $startDate->format('Ymd');
					$vevent->DTSTART->add('VALUE', 'DATE');
					$vevent->DTEND = $endDate->format('Ymd');
					$vevent->DTEND->add('VALUE', 'DATE');
				} else {
					$vevent->DTSTART = $startDate->format('Ymd\THis\Z');
					$vevent->DTSTART->add('VALUE', 'DATE-TIME');
					$vevent->DTEND = $endDate->format('Ymd\THis\Z');
					$vevent->DTEND->add('VALUE', 'DATE-TIME');
				}
				$vevent->{'LAST-MODIFIED'} = \DPCalendarHelper::getDate()->format('Ymd\THis\Z');
				$vevent->{'X-COLOR'}       = $event->color;
				$vevent->{'X-URL'}         = $event->url;

				if (isset($event->locations) && !empty($event->locations)) {
					$vevent->LOCATION = \DPCalendar\Helper\Location::format($event->locations);
					foreach ($event->locations as $loc) {
						if (!empty($loc->latitued) && !empty($loc->longitude)) {
							$vevent->GEO = $loc->latitude . ';' . $loc->longitude;
						}
					}
				}

				if ($event->original_id == '-1') {
					$vevent->RRULE = $event->rrule;
				}

				// Echo '<pre>' . $c->serialize() . '</pre>'; die();
				$this->updateCalDAVEvent($eventId, $c->serialize(), $calendarId);
			}

			$startDate = \DPCalendarHelper::getDate($event->start_date, $event->all_day);

			$id = $eventId . '_' . ($event->all_day ? $startDate->format('Ymd') : $startDate->format('YmdHi'));
			if (!empty($event->rrule)) {
				$id = $eventId . '_0';
			}

			return $this->createEvent($id, $calendarId)->id;
		} catch (\Exception $e) {
			\JError::raiseWarning(0, $e->getMessage());

			return false;
		}
	}
}
