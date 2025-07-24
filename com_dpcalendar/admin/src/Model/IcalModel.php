<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use DigitalPeak\Component\DPCalendar\Site\Model\EventsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Path;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\Reader;
use Sabre\VObject\TimeZoneUtil;

class IcalModel extends BaseDatabaseModel
{
	public function createIcalFromCalendar(array $calendarIds, bool $asDownload = false): string
	{
		$eventsModel = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
		$eventsModel->setState('category.id', $calendarIds);
		$eventsModel->setState('category.recursive', false);
		$eventsModel->setState('list.limit', 100000);
		$eventsModel->setState('filter.ongoing', true);
		$eventsModel->setState('filter.state', 1);
		$eventsModel->setState('filter.language', Factory::getApplication()->getLanguage()->getTag());
		$eventsModel->setState('filter.publish_date', true);
		$eventsModel->setState('list.start-date', '0');
		$eventsModel->setState('list.ordering', 'start_date');
		$eventsModel->setState('list.direction', 'asc');

		$eventsModel->setState('filter.expand', false);

		// In some cases we need to increase the memory limit as we try to fetch
		// all events, then uncomment the following line.
		// ini_set('memory_limit', '512M');

		$items = $eventsModel->getItems();
		if (!\is_array($items)) {
			$items = [];
		}

		return $this->createIcalFromEvents($items, $asDownload);
	}

	/**
	 * Creates an ical from the given events. If needed it can force the current browser request to delete the file.
	 *
	 * The instances of a recurring series are stripped out and it is expected that the original event is included
	 * in the events data. If this is not the case the force flag will generate ical content of the available event
	 * data, even when they are instances of a series.
	 */
	public function createIcalFromEvents(array $events, bool $asDownload = false, bool $forceEvents = false, ?EventsModel $eventsModel = null): string
	{
		$text   = [];
		$text[] = 'BEGIN:VCALENDAR';
		$text[] = 'VERSION:2.0';
		$text[] = 'PRODID:DPCALENDAR';
		$text[] = 'CALSCALE:GREGORIAN';

		$userTz = DPCalendarHelper::getDate()->getTimezone()->getName();
		if (empty($userTz)) {
			$userTz = 'UTC';
		}
		$tz = self::generateVtimezone($userTz);
		if ($tz instanceof VTimeZone) {
			$text = array_merge($text, explode(PHP_EOL, trim($tz->serialize())));
		}
		$text[] = 'X-WR-TIMEZONE:' . $userTz;

		$calendars = [];
		foreach ($events as $key => $event) {
			// Strip out series events as we collect them later
			if ($event->original_id > 0 && !$forceEvents) {
				unset($events[$key]);
			}

			if (\array_key_exists($event->catid, $calendars) || empty($event->catid)) {
				continue;
			}

			if (!empty($event->category_title)) {
				$calendars[$event->catid] = $event->category_title;
			} else {
				$calendar                 = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);
				$calendars[$event->catid] = $calendar instanceof CalendarInterface ? $calendar->getTitle() : $event->catid;
			}
		}
		// $text[] = 'X-WR-CALNAME:'.implode('; ', $calendars);

		foreach ($events as $event) {
			$text = array_merge($text, self::addEventData($event, $eventsModel));
		}

		$text[] = 'END:VCALENDAR';

		if ($asDownload) {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . Path::clean(implode(',', $calendars)) . '.ics"');

			echo implode(PHP_EOL, $text);
			Factory::getApplication()->close();
		} else {
			return implode(PHP_EOL, $text);
		}
	}

	public function icalDecode(string $text): string
	{
		$newText = str_replace('\n', '<br/>', $text);
		$newText = str_replace('\N', '<br/>', $newText);
		$newText = str_replace('\,', ',', $newText);

		return str_replace('\;', ';', $newText);
	}

	public function icalEncode(string $text): string
	{
		$newText = str_replace(',', '\,', $text);

		return str_replace(';', '\;', $newText);
	}

	private function addEventData(\stdClass $event, ?EventsModel $eventsModel = null): array
	{
		$childsToAdd = [];
		$text        = [];
		$text[]      = 'BEGIN:VEVENT';

		// Creating date objects of the dates
		$start = DPCalendarHelper::getDate($event->start_date, $event->all_day);
		$end   = DPCalendarHelper::getDate($event->end_date, $event->all_day);

		// Defining the user tz
		$userTz = $start->getTimezone()->getName();
		if (empty($userTz)) {
			$userTz = 'UTC';
		}

		if ($event->all_day == 1) {
			$text[] = 'DTSTART;VALUE=DATE:' . $start->format('Ymd', true);
			$tmp    = DPCalendarHelper::getDate($event->end_date, $event->all_day);
			$tmp->modify('+1 day');
			$text[] = 'DTEND;VALUE=DATE:' . $tmp->format('Ymd', true);
		} else {
			$text[] = 'DTSTART;TZID=' . $userTz . ':' . $start->format('Ymd\THis', true);
			$text[] = 'DTEND;TZID=' . $userTz . ':' . $end->format('Ymd\THis', true);
		}

		if (!empty($event->rrule)) {
			$text[] = 'RRULE:' . $event->rrule;

			if ($event->id && is_numeric($event->catid)) {
				// Find deleted events and add them as EXDATE
				if ($eventsModel == null) {
					$eventsModel = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
				}
				$eventsModel->setState('category.id', $event->catid);
				$eventsModel->setState('category.recursive', false);
				$eventsModel->setState('list.limit', 100000);
				$eventsModel->setState('filter.ongoing', true);
				$eventsModel->setState('filter.state', 1);
				$eventsModel->setState('filter.language', Factory::getApplication()->getLanguage()->getTag());
				$eventsModel->setState('filter.publish_date', true);
				$eventsModel->setState('list.start-date', $start);
				$eventsModel->setState('list.ordering', 'start_date');
				$eventsModel->setState('list.direction', 'asc');
				$eventsModel->setState('filter.expand', true);
				$eventsModel->setState('filter.children', $event->id);

				$instances = $eventsModel->getItems();

				// Check for modified events
				foreach ($instances as $key => $e) {
					// If for some reasons the event doesn't belong to the series, ignore it
					if ($event->uid != $e->uid) {
						unset($instances[$key]);
					}

					// Add modified events for later
					if ($e->modified !== $event->modified) {
						$childsToAdd[$e->id] = $e;
					}
				}

				// Creating the rrule event text to parse
				$rruleText   = [];
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
				/** @var VCalendar $cal */
				$cal = Reader::read(implode(PHP_EOL, $rruleText));
				$cal = $cal->expand($start, new \DateTime('2038-01-01'));

				// Find deleted events
				$exdates = [];
				if ($cal->VEVENT) {
					foreach ($cal->VEVENT as $vevent) {
						$found = false;
						$date  = DPCalendarHelper::getDate($vevent->DTSTART->getDateTime()->format('U'), $event->all_day);

						$dateFormatted = $event->all_day ? $date->format('Ymd') : $date->format('Ymd\THis\Z');

						// Trying to find the instance for the actual recurrence id date
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
				}
				if ($exdates !== []) {
					$text[] = 'EXDATE:' . implode(',', $exdates);
				}

				// Cleanup memory
				$cal = null;
				unset($cal);
			}
		}

		if (isset($event->uid)) {
			$text[] = 'UID:' . str_replace('.ics', '', (string)$event->uid);
		} elseif (!empty($event->original_id) && $event->original_id != -1) {
			$text[] = 'UID:' . md5($event->original_id . '_DPCalendar');
		} elseif (!empty($event->id)) {
			$text[] = 'UID:' . md5($event->id . '_DPCalendar');
		} else {
			$text[] = 'UID:' . md5(uniqid() . '_DPCalendar');
		}

		if (!empty($event->original_id) && $event->original_id != -1) {
			if (\strlen((string)$event->recurrence_id) <= 8) {
				$text[] = 'RECURRENCE-ID;VALUE=DATE:' . $event->recurrence_id;
			} else {
				$text[] = 'RECURRENCE-ID:' . $event->recurrence_id;
			}
		}

		$created = DPCalendarHelper::getDate($event->created);
		$text[]  = 'SUMMARY:' . $event->title;
		$text[]  = 'CREATED:' . $created->format('Ymd\THis\Z');
		$text[]  = 'DTSTAMP:' . $created->format('Ymd\THis\Z');
		$text[]  = 'URL:' . ($event->url ?: RouteHelper::getEventRoute($event->id, $event->catid, true, true));

		if ($event->description) {
			$description = (empty($event->introText) ? '' : $event->introText . '<hr id="system-readmore">') . $event->description;
			$text[]      = 'DESCRIPTION:' . InputFilter::getInstance()->clean(preg_replace('/\r\n|\r|\n/', "\N", $description));
			$text[]      = 'X-ALT-DESC;FMTTYPE=text/html:' . preg_replace('/\r\n|\r|\n/', '', $description);
		}

		if ($event->modified) {
			$modified = DPCalendarHelper::getDate($event->modified);
			$text[]   = 'LAST-MODIFIED:' . $modified->format('Ymd\THis\Z');
			$text[]   = 'SEQUENCE:' . ($modified->format('U') - $created->format('U'));
		}

		$customLocation = '';
		if (isset($event->locations) && !empty($event->locations)) {
			$customLocation = implode(', ', array_map(static fn ($l) => $l->title, $event->locations));
			$text[]         = 'LOCATION:' . self::icalEncode($this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format($event->locations));
			if (!empty($event->locations[0]->latitude) && !empty($event->locations[0]->longitude)) {
				$text[] = 'GEO:' . $event->locations[0]->latitude . ';' . $event->locations[0]->longitude;
			}
		}

		if (!empty($event->roomTitles)) {
			$customLocation .= ' [' . implode(', ', array_merge(...array_values($event->roomTitles))) . ']';
			$text[] = 'X-ROOMS:' . implode(', ', array_merge(...array_values($event->roomTitles)));
		}

		if ($customLocation !== '' && $customLocation !== '0') {
			$text[] = 'X-LOCATION-DISPLAYNAME:' . $customLocation;
		}

		$text[] = 'X-ACCESS:' . $event->access;
		$text[] = 'X-HITS:' . $event->hits;
		$text[] = 'X-COLOR:' . $event->color;

		if ($event->images && !empty($event->images->image_full)) {
			$image  = $event->images->image_full;
			$text[] = 'X-IMAGE-FULL:' . (str_starts_with((string)$image, 'http') ? '' : Uri::base()) . $image;
		}
		if ($event->images && !empty($event->images->image_full_alt)) {
			$text[] = 'X-IMAGE-FULL-ALT:' . $event->images->image_full_alt;
		}
		if ($event->images && !empty($event->images->image_full_caption)) {
			$text[] = 'X-IMAGE-FULL-CAPTION:' . $event->images->image_full_caption;
		}
		if ($event->images && !empty($event->images->image_intro)) {
			$image  = $event->images->image_intro;
			$text[] = 'X-IMAGE-INTRO:' . (str_starts_with((string)$image, 'http') ? '' : Uri::base()) . $image;
		}
		if ($event->images && !empty($event->images->image_intro_alt)) {
			$text[] = 'X-IMAGE-INTRO-ALT:' . $event->images->image_intro_alt;
		}
		if ($event->images && !empty($event->images->image_intro_caption)) {
			$text[] = 'X-IMAGE-INTRO-CAPTION:' . $event->images->image_intro_caption;
		}
		$text[] = 'X-SHOW-END-TIME:' . $event->show_end_time;

		if ($event->publish_up) {
			$text[] = 'X-PUBLISH-UP:' . $event->publish_up;
		}

		if ($event->publish_down) {
			$text[] = 'X-PUBLISH-DOWN:' . $event->publish_down;
		}

		$text[] = 'END:VEVENT';

		foreach ($childsToAdd as $child) {
			$text = array_merge($text, self::addEventData($child, $eventsModel));
		}

		return $text;
	}

	/**
	 * Returns a VTIMEZONE component for a Olson timezone identifier
	 * with daylight transitions covering the given date range.
	 */
	private function generateVtimezone(string $tzid, int $from = 0, int $to = 0): ?VTimeZone
	{
		if ($from === 0) {
			$from = time();
		}
		if ($to === 0) {
			$to = $from;
		}

		try {
			$tz = new \DateTimeZone($tzid);
		} catch (\Exception) {
			return null;
		}

		// get all transitions for one year back/ahead
		$year        = 86400 * 360;
		$transitions = $tz->getTransitions($from - $year, $to + $year);

		// Some parsers do need components like standard or daylight
		if (\count($transitions) <= 1) {
			return null;
		}

		$vcalendar = new VCalendar();
		$vt        = $vcalendar->createComponent('VTIMEZONE');
		$vt->TZID  = $tz->getName();

		$std    = null;
		$dst    = null;
		$tzfrom = 0;
		foreach ($transitions as $i => $trans) {
			$cmp = null;

			// Skip the first entry...
			if ($i == 0) {
				// ... but remember the offset for the next TZOFFSETFROM value
				$tzfrom = $trans['offset'] / 3600;
				continue;
			}

			// Daylight saving time definition
			$t_dst = 0;
			$t_std = 0;
			if ($trans['isdst']) {
				$t_dst = $trans['ts'];
				$dst   = $vcalendar->createComponent('DAYLIGHT');
				$cmp   = $dst;
			} else {
				// Standard time definition
				$t_std = $trans['ts'];
				$std   = $vcalendar->createComponent('STANDARD');
				$cmp   = $std;
			}

			$dt     = new \DateTime($trans['time']);
			$offset = $trans['offset'] / 3600;

			$cmp->DTSTART      = $dt->format('Ymd\THis');
			$cmp->TZOFFSETFROM = \sprintf('%+03d%02d', ($tzfrom >= 0 ? '+' : '') . floor($tzfrom), ($tzfrom - floor($tzfrom)) * 60);
			$cmp->TZOFFSETTO   = \sprintf('%+03d%02d', ($offset >= 0 ? '+' : '') . floor($offset), ($offset - floor($offset)) * 60);

			// Add abbreviated timezone name if available
			$cmp->TZNAME = $trans['abbr'];

			$tzfrom = $offset;
			$vt->add($cmp);

			// We covered the entire date range
			if ($std === null || $dst === null) {
				continue;
			}

			if (min($t_std, $t_dst) >= $from || max($t_std, $t_dst) <= $to) {
				continue;
			}

			break;
		}

		// add X-MICROSOFT-CDO-TZID if available
		// @phpstan-ignore-next-line
		$microsoftExchangeMap = array_flip(TimeZoneUtil::$microsoftExchangeMap);
		if (\array_key_exists($tz->getName(), $microsoftExchangeMap)) {
			$vt->add('X-MICROSOFT-CDO-TZID', $microsoftExchangeMap[$tz->getName()]);
		}

		return $vt instanceof VTimeZone ? $vt : null;
	}
}
