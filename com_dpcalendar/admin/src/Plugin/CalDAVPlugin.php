<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Plugin;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Filter\InputFilter;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\NoInstancesException;

abstract class CalDAVPlugin extends SyncPlugin
{
	abstract protected function createCalDAVEvent(string $uid, string $icalData, string  $calendarId): string;

	abstract protected function updateCalDAVEvent(string $uid, string $icalData, string  $calendarId): string;

	abstract protected function deleteCalDAVEvent(string $uid, string $calendarId): void;

	abstract protected function getOriginalData(string $uid, string  $calendarId): string;

	public function deleteEvent(string $eventId, string $calendarId): bool
	{
		$oldEvent = $this->fetchEvent($eventId, $calendarId);
		$eventId  = substr($eventId, 0, strrpos($eventId, '_') ?: 0);

		if (empty($oldEvent->original_id) || $oldEvent->original_id == '-1') {
			$this->deleteCalDAVEvent($eventId, $calendarId);

			return true;
		}

		$this->getDPCalendar()->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($oldEvent->catid);
		$c = Reader::read($this->getOriginalData($eventId, $calendarId));

		$original = null;
		foreach ($c->children() as $index => $tmp) {
			if ($tmp->name != 'VEVENT') {
				continue;
			}
			if ((string)$tmp->{'RECURRENCE-ID'} == $oldEvent->recurrence_id) {
				unset($c->children()[$index]);
			}
			if ($tmp->RRULE !== null) {
				$original = $tmp;
			}
		}

		$exdates = $original->EXDATE;
		if ($exdates === null) {
			$original->add('EXDATE', '');
		}
		$rec = DPCalendarHelper::getDate($oldEvent->start_date, $oldEvent->all_day)->format('Ymd' . ($oldEvent->all_day ? '' : '\THis\Z'));

		try {
			$original->EXDATE = trim($exdates . ',' . $rec, ',');
			// @phpstan-ignore-next-line
			$original->EXDATE->add('VALUE', 'DATE' . ($oldEvent->all_day ? '' : '-TIME'));

			// Echo '<pre>' . $c->serialize() . '</pre>'; die();
			$this->updateCalDAVEvent($eventId, $c->serialize(), $calendarId);
		} catch (NoInstancesException) {
			$this->deleteCalDAVEvent($eventId, $calendarId);
		}

		return true;
	}

	public function saveEvent(string $eventId, string $calendarId, array $data): string
	{
		$calendar = $this->getDPCalendar()->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($data['catid']);
		$event    = null;
		$oldEvent = null;
		if ($eventId !== '' && $eventId !== '0') {
			$event = $this->getDPCalendar()->getMVCFactory()->createTable('Event', 'Administrator');
			$event->bind($this->fetchEvent($eventId, $calendarId) instanceof \stdClass ? $this->fetchEvent($eventId, $calendarId) : []);
			$oldEvent = clone $event;
		} else {
			$event = $this->getDPCalendar()->getMVCFactory()->createTable('Event', 'Administrator');
		}
		$event->bind($data);
		$event->id = $eventId;
		// @phpstan-ignore-next-line
		$event->category_title = $calendar instanceof CalendarInterface ? $calendar->getTitle() : $data['catid'];
		if (isset($data['location_ids'])) {
			// @phpstan-ignore-next-line
			$event->locations = $this->getDPCalendar()->getMVCFactory()
				->createModel('Geo', 'Administrator')->getLocations((array)$data['location_ids']);
		}
		$ical = $this->getDPCalendar()->getMVCFactory()
			->createModel('Ical', 'Administrator')->createIcalFromEvents([$event]);

		$start = strpos((string)$ical, 'UID:') + 4;
		$end   = strpos((string)$ical, PHP_EOL, $start + 1);
		$uid   = substr((string)$ical, $start, $end - $start);
		if ($eventId === '' || $eventId === '0') {
			$eventId = $uid;
			$this->createCalDAVEvent($uid, $ical, $calendarId);
		} else {
			$eventId = substr($eventId, 0, strrpos($eventId, '_') ?: 0);
			$ical    = str_replace($uid, $eventId, (string)$ical);

			$c      = Reader::read($this->getOriginalData($eventId, $calendarId));
			$vevent = null;
			if (is_iterable($c->VEVENT)) {
				foreach ($c->VEVENT as $tmp) {
					if ((string)$tmp->UID !== $eventId) {
						continue;
					}
					if ($event->original_id == '0') {
						$vevent = $tmp;
						break;
					} elseif ((string)$tmp->{'RECURRENCE-ID'} == $event->recurrence_id) {
						$vevent = $tmp;
						break;
					}
				}
			}
			if ($vevent === null) {
				$vevent = $c->createComponent('VEVENT');
				$c->add($vevent);
				$vevent->UID = $eventId;
				if (!empty($event->original_id) && $event->original_id != '-1') {
					if ($oldEvent->all_day) {
						$rec                       = DPCalendarHelper::getDate($oldEvent->start_date, (bool)$oldEvent->all_day)->format('Ymd');
						$vevent->{'RECURRENCE-ID'} = $rec;
						// @phpstan-ignore-next-line
						$vevent->{'RECURRENCE-ID'}->add('VALUE', 'DATE');
					} else {
						$rec                       = DPCalendarHelper::getDate($oldEvent->start_date, (bool)$oldEvent->all_day)->format('Ymd\THis\Z');
						$vevent->{'RECURRENCE-ID'} = $rec;
						// @phpstan-ignore-next-line
						$vevent->{'RECURRENCE-ID'}->add('VALUE', 'DATE-TIME');
					}
				}
			} elseif ($event->original_id == '0' || $event->original_id == '-1') {
				unset($c->VEVENT);
				unset($vevent->EXDATE);
				$c->add($vevent);
			}

			$vevent->SUMMARY = $event->title;

			$vevent->DESCRIPTION    = InputFilter::getInstance()->clean(preg_replace('/\r\n?/', "\n", (string)$event->description));
			$vevent->{'X-ALT-DESC'} = preg_replace('/\r\n?/', "", (string)$event->description);
			// @phpstan-ignore-next-line
			$vevent->{'X-ALT-DESC'}->add('FMTTYPE', 'text/html');

			$startDate = DPCalendarHelper::getDate($event->start_date, $event->all_day == 1);
			$endDate   = DPCalendarHelper::getDate($event->end_date, (bool)$event->all_day);
			if ($event->all_day == 1) {
				$endDate->modify('+1 day');
				$vevent->DTSTART = $startDate->format('Ymd');
				// @phpstan-ignore-next-line
				$vevent->DTSTART->add('VALUE', 'DATE');
				$vevent->DTEND = $endDate->format('Ymd');
				// @phpstan-ignore-next-line
				$vevent->DTEND->add('VALUE', 'DATE');
			} else {
				$vevent->DTSTART = $startDate->format('Ymd\THis\Z');
				// @phpstan-ignore-next-line
				$vevent->DTSTART->add('VALUE', 'DATE-TIME');
				$vevent->DTEND = $endDate->format('Ymd\THis\Z');
				// @phpstan-ignore-next-line
				$vevent->DTEND->add('VALUE', 'DATE-TIME');
			}
			$vevent->{'LAST-MODIFIED'} = DPCalendarHelper::getDate()->format('Ymd\THis\Z');
			$vevent->{'X-COLOR'}       = $event->color;
			$vevent->{'X-URL'}         = $event->url;

			if ($event->locations !== null && !empty($event->locations)) {
				$vevent->LOCATION = $this->getDPCalendar()->getMVCFactory()
					->createModel('Geo', 'Administrator')->format($event->locations);
				foreach ($event->locations as $loc) {
					if (empty($loc->latitude)) {
						continue;
					}
					if (empty($loc->longitude)) {
						continue;
					}
					$vevent->GEO = $loc->latitude . ';' . $loc->longitude;
				}
			}

			if ($event->original_id == '-1') {
				$vevent->RRULE = $event->rrule;
			}

			// Echo '<pre>' . $c->serialize() . '</pre>'; die();
			$this->updateCalDAVEvent($eventId, $c->serialize(), $calendarId);
		}

		$startDate = DPCalendarHelper::getDate($event->start_date, (bool)$event->all_day);

		$id = $eventId . '_' . ($event->all_day ? $startDate->format('Ymd') : $startDate->format('YmdHi'));
		if (!empty($event->rrule)) {
			$id = $eventId . '_0';
		}

		return $this->createEvent($id, $calendarId)->id;
	}
}
