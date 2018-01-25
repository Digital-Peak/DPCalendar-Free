<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Helper;

class Ical
{

	public static function createIcalFromCalendar($calendarId, $asDownload = false)
	{
		\JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');
		$model = \JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
		$model->setState('category.id', $calendarId);
		$model->setState('category.recursive', false);
		$model->setState('list.limit', 100000);
		$model->setState('filter.ongoing', true);
		$model->setState('filter.state', 1);
		$model->setState('filter.language', \JFactory::getLanguage());
		$model->setState('filter.publish_date', true);
		$model->setState('list.start-date', '0');
		$model->setState('list.ordering', 'start_date');
		$model->setState('list.direction', 'asc');

		$model->setState('filter.expand', false);

		// In some cases we need to increase the memory limit as we try to fetch
		// all events, then uncomment the following line.
		// ini_set('memory_limit', '512M');

		$items = $model->getItems();

		if (!is_array($items)) {
			$items = array();
		}

		return self::createIcalFromEvents($items, $asDownload);
	}

	public static function createIcalFromEvents($events, $asDownload = false)
	{
		\JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);

		$text   = array();
		$text[] = 'BEGIN:VCALENDAR';
		$text[] = 'VERSION:2.0';
		$text[] = 'PRODID:DPCALENDAR';
		$text[] = 'CALSCALE:GREGORIAN';

		$userTz = \DPCalendarHelper::getDate()->getTimezone()->getName();
		if (empty($userTz)) {
			$userTz = 'UTC';
		}
		$tz = self::generateVtimezone($userTz);
		if ($tz) {
			$text = array_merge($text, explode(PHP_EOL, trim($tz->serialize())));
		}
		$text[] = 'X-WR-TIMEZONE:' . $userTz;

		$calendars = array();
		foreach ($events as $key => $event) {
			// Strip out series events as we collect them later
			if ($event->original_id > 0) {
				unset($events[$key]);
			}
			if (key_exists($event->catid, $calendars)) {
				continue;
			}
			if (!empty($event->category_title)) {
				$calendars[$event->catid] = $event->category_title;
			} else {
				$calendars[$event->catid] = \DPCalendarHelper::getCalendar($event->catid)->title;
			}
		}
		// $text[] = 'X-WR-CALNAME:'.implode('; ', $calendars);

		$now = \DPCalendarHelper::getDate()->format('Ymd\THis\Z');
		foreach ($events as $key => $event) {
			$text = array_merge($text, self::addEventData($event));
		}
		$text[] = 'END:VCALENDAR';

		if ($asDownload) {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . \JPath::clean(implode(',', $calendars)) . '.ics"');

			echo implode(PHP_EOL, $text);
			\JFactory::getApplication()->close();
		} else {
			return implode(PHP_EOL, $text);
		}
	}

	public static function icalDecode($text)
	{
		$newText = str_replace('\n', '<br/>', $text);
		$newText = str_replace('\N', '<br/>', $newText);
		$newText = str_replace('\,', ',', $newText);
		$newText = str_replace('\;', ';', $newText);

		return $newText;
	}

	public static function icalEncode($text)
	{
		$newText = str_replace(',', '\,', $text);
		$newText = str_replace(';', '\;', $newText);

		return $newText;
	}

	private static function addEventData($event)
	{
		$childsToAdd = array();
		$text        = array();
		$text[]      = 'BEGIN:VEVENT';

		// Creating date objects of the dates
		$start = \DPCalendarHelper::getDate($event->start_date, $event->all_day);
		$end   = \DPCalendarHelper::getDate($event->end_date, $event->all_day);

		// Defining the user tz
		$userTz = $start->getTimezone()->getName();
		if (empty($userTz)) {
			$userTz = 'UTC';
		}

		if ($event->all_day == 1) {
			$text[] = 'DTSTART;VALUE=DATE:' . $start->format('Ymd', true);
			$tmp    = \DPCalendarHelper::getDate($event->end_date, $event->all_day);
			$tmp->modify('+1 day');
			$text[] = 'DTEND;VALUE=DATE:' . $tmp->format('Ymd', true);
		} else {
			$text[] = 'DTSTART;TZID=' . $userTz . ':' . $start->format('Ymd\THis', true);
			$text[] = 'DTEND;TZID=' . $userTz . ':' . $end->format('Ymd\THis', true);
		}

		if (!empty($event->rrule)) {
			$text[] = 'RRULE:' . $event->rrule;

			if ($event->id && is_numeric($event->catid)) {
				// Finde deleted events and add them as EXDATE
				\JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');
				$model = \JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
				$model->setState('category.id', $event->catid);
				$model->setState('category.recursive', false);
				$model->setState('list.limit', 100000);
				$model->setState('filter.ongoing', true);
				$model->setState('filter.state', 1);
				$model->setState('filter.language', \JFactory::getLanguage());
				$model->setState('filter.publish_date', true);
				$model->setState('list.start-date', $start);
				$model->setState('list.ordering', 'start_date');
				$model->setState('list.direction', 'asc');
				$model->setState('filter.expand', true);
				$model->setState('filter.children', $event->id);

				$instances = $model->getItems();

				// Chech for modified events
				foreach ($instances as $key => $e) {
					// If for some reasons the event doesn't belong to the
					// series, ignore it
					if ($event->uid != $e->uid) {
						unset($instances[$key]);
					}

					// Add modified events for later
					if ($e->modified > $event->modified) {
						$childsToAdd[$e->id] = $e;
					}
				}

				// Creating the rrule event text to parse
				$rruleText   = array();
				$rruleText[] = 'BEGIN:VCALENDAR';
				$rruleText[] = 'BEGIN:VEVENT';
				$rruleText[] = 'UID:' . time();

				if ($event->all_day == 1) {
					$rruleText[] = 'DTSTART;VALUE=DATE:' . $start->format('Ymd', true);
					$rruleText[] = 'DTEND;VALUE=DATE:' . $end->format('Ymd', true);
				} else {
					$rruleText[] = 'DTSTART;TZID=' . $userTz . ':' . $start->format('Ymd\THis', true);
					$rruleText[] = 'DTEND;TZID=' . $userTz . ':' . $end->format('Ymd\THis', true);
				}

				$rruleText[] = 'RRULE:' . $event->rrule;
				$rruleText[] = 'END:VEVENT';
				$rruleText[] = 'END:VCALENDAR';

				// Getting the instances from the rrule
				$cal = \Sabre\VObject\Reader::read(implode(PHP_EOL, $rruleText));
				$cal = $cal->expand($start, new \DateTime('2038-01-01'));

				// Find deleted events
				$exdates = array();
				foreach ($cal->VEVENT as $vevent) {
					$found = false;
					$date  = \DPCalendarHelper::getDate($vevent->DTSTART->getDateTime()->format('U'), $event->all_day);

					// Ignore starting event
					if ($date->toSql() == $event->start_date) {
						continue;
					}

					$dateFormatted = $event->all_day ? $date->format('Ymd') : $date->format('Ymd\THis\Z');

					// Trying to find the instance for the actual recurrence id
					// date
					foreach ($instances as $e) {
						if ($dateFormatted == $e->recurrence_id) {
							$found = true;
							break;
						}
					}

					if (!$found) {
						// No instance was found, adding it to the exdates
						$exdates[] = $dateFormatted;
					}
				}
				if ($exdates) {
					$text[] = 'EXDATE:' . implode(',', $exdates);
				}

				// Cleanup memory
				$cal = null;
				unset($cal);
			}
		}

		if (isset($event->uid)) {
			$text[] = 'UID:' . str_replace('.ics', '', $event->uid);
		} else if (!empty($event->original_id) && $event->original_id != -1) {
			$text[] = 'UID:' . md5($event->original_id . '_DPCalendar');
		} else if (!empty($event->id)) {
			$text[] = 'UID:' . md5($event->id . '_DPCalendar');
		} else {
			$text[] = 'UID:' . md5(uniqid() . '_DPCalendar');
		}

		if (!empty($event->original_id) && $event->original_id != -1) {
			if (strlen($event->recurrence_id) <= 8) {
				$text[] = 'RECURRENCE-ID;VALUE=DATE:' . $event->recurrence_id;
			} else {
				$text[] = 'RECURRENCE-ID:' . $event->recurrence_id;
			}
		}

		$text[] = 'SUMMARY:' . $event->title;
		$text[] = 'DTSTAMP:' . \DPCalendarHelper::getDate($event->created)->format('Ymd\THis\Z');
		$text[] = 'DESCRIPTION:' . \JFilterInput::getInstance()->clean(preg_replace('/\r\n|\r|\n/', "\N", $event->description));
		$text[] = 'X-ALT-DESC;FMTTYPE=text/html:' . preg_replace('/\r\n|\r|\n/', "", $event->description);

		if (isset($event->locations) && !empty($event->locations)) {
			$text[] = 'LOCATION:' . self::icalEncode(\DPCalendar\Helper\Location::format($event->locations));
			if (!empty($event->locations[0]->latitude) && !empty($event->locations[0]->longitude)) {
				$text[] = 'GEO:' . $event->locations[0]->latitude . ';' . $event->locations[0]->longitude;
			}
		}

		if ($event->modified && $event->modified != \JFactory::getDbo()->getNullDate()) {
			$text[] = 'LAST-MODIFIED:' . \DPCalendarHelper::getDate($event->modified)->format('Ymd\THis\Z');
		}
		$text[] = 'X-ACCESS:' . $event->access;
		$text[] = 'X-HITS:' . $event->hits;
		$text[] = 'X-URL:' . $event->url;
		$text[] = 'X-COLOR:' . $event->color;

		$text[] = 'END:VEVENT';

		foreach ($childsToAdd as $child) {
			$text = array_merge($text, self::addEventData($child));
		}

		return $text;
	}

	/**
	 * Returns a VTIMEZONE component for a Olson timezone identifier
	 * with daylight transitions covering the given date range.
	 *
	 * @param
	 *            string Timezone ID as used in PHP's Date functions
	 * @param
	 *            integer Unix timestamp with first date/time in this timezone
	 * @param
	 *            integer Unix timestap with last date/time in this timezone
	 *
	 * @return mixed A Sabre\VObject\Component object representing a VTIMEZONE
	 *         definition
	 *         or false if no timezone information is available
	 */
	private static function generateVtimezone($tzid, $from = 0, $to = 0)
	{
		if (!$from) {
			$from = time();
		}
		if (!$to) {
			$to = $from;
		}

		try {
			$tz = new \DateTimeZone($tzid);
		} catch (\Exception $e) {
			return false;
		}

		// get all transitions for one year back/ahead
		$year        = 86400 * 360;
		$transitions = $tz->getTransitions($from - $year, $to + $year);

		$vcalendar = new \Sabre\VObject\Component\VCalendar();
		$vt        = $vcalendar->createComponent('VTIMEZONE');
		$vt->TZID  = $tz->getName();

		$std = null;
		$dst = null;
		foreach ($transitions as $i => $trans) {
			$cmp = null;

			// skip the first entry...
			if ($i == 0) {
				// ... but remember the offset for the next TZOFFSETFROM value
				$tzfrom = $trans['offset'] / 3600;
				continue;
			}

			// daylight saving time definition
			if ($trans['isdst']) {
				$t_dst = $trans['ts'];
				$dst   = $vcalendar->createComponent('DAYLIGHT');
				$cmp   = $dst;
			} // standard time definition
			else {
				$t_std = $trans['ts'];
				$std   = $vcalendar->createComponent('STANDARD');
				$cmp   = $std;
			}

			if ($cmp) {
				$dt     = new \DateTime($trans['time']);
				$offset = $trans['offset'] / 3600;

				$cmp->DTSTART      = $dt->format('Ymd\THis');
				$cmp->TZOFFSETFROM = sprintf('%+03d%02d', ($tzfrom >= 0 ? '+' : '') . floor($tzfrom), ($tzfrom - floor($tzfrom)) * 60);
				$cmp->TZOFFSETTO   = sprintf('%+03d%02d', ($offset >= 0 ? '+' : '') . floor($offset), ($offset - floor($offset)) * 60);

				// add abbreviated timezone name if available
				if (!empty($trans['abbr'])) {
					$cmp->TZNAME = $trans['abbr'];
				}

				$tzfrom = $offset;
				$vt->add($cmp);
			}

			// we covered the entire date range
			if ($std && $dst && min($t_std, $t_dst) < $from && max($t_std, $t_dst) > $to) {
				break;
			}
		}

		// add X-MICROSOFT-CDO-TZID if available
		$microsoftExchangeMap = array_flip(\Sabre\VObject\TimeZoneUtil::$microsoftExchangeMap);
		if (array_key_exists($tz->getName(), $microsoftExchangeMap)) {
			$vt->add('X-MICROSOFT-CDO-TZID', $microsoftExchangeMap[$tz->getName()]);
		}

		return $vt;
	}
}
