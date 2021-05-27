<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Helper;

defined('_JEXEC') or die();

class DateHelper
{
	public function transformRRuleToString($rrule, $startDate)
	{
		if (!$rrule) {
			return '';
		}

		$start = $this->getDate($startDate);
		$rule  = new \Recurr\Rule('', $start, null, $start->getTimezone()->getName());
		$parts = $rule->parseString($rrule);

		// Parser can't handle both
		if (isset($parts['UNTIL']) && isset($parts['COUNT'])) {
			unset($parts['UNTIL']);
		}

		//Only add the date so we have no tz issues
		if (isset($parts['UNTIL'])) {
			$parts['UNTIL'] = substr($parts['UNTIL'], 0, 8);
		}

		$rule->loadFromArray($parts);

		$translator = new \Recurr\Transformer\Translator();
		try {
			$translator->loadLocale(substr(DPCalendarHelper::getFrLanguage(), 0, 2));
		} catch (\InvalidArgumentException $e) {
			//Translation doesn't exist, ignore it
		}
		$textTransformer = new \Recurr\Transformer\TextTransformer($translator);

		$string = ucfirst($textTransformer->transform($rule));

		return $string;
	}

	public function getDate($date = null, $allDay = null, $tz = null)
	{
		if ($date instanceof \JDate) {
			$dateObj = clone $date;
		} else {
			$dateObj = \JFactory::getDate($date, $tz);
		}

		$timezone = \JFactory::getApplication()->getCfg('offset');
		$user     = \JFactory::getUser();
		if ($user->get('id')) {
			$userTimezone = $user->getParam('timezone');
			if (!empty($userTimezone)) {
				$timezone = $userTimezone;
			}
		}

		$timezone = \JFactory::getSession()->get('user-timezone', $timezone, 'DPCalendar');

		if (!$allDay) {
			$dateObj->setTimezone(new \DateTimeZone($timezone));
		}

		return $dateObj;
	}

	public function getDateStringFromEvent($event, $dateFormat = null, $timeFormat = null)
	{
		return \JLayoutHelper::render(
			'event.datestring',
			['event' => $event, 'dateFormat' => $dateFormat, 'timeFormat' => $timeFormat],
			null,
			['component' => 'com_dpcalendar', 'client' => 0]
		);
	}

	public function getNames()
	{
		$options                    = [];
		$options['monthNames']      = [];
		$options['monthNamesShort'] = [];
		$options['dayNames']        = [];
		$options['dayNamesShort']   = [];
		$options['dayNamesMin']     = [];
		for ($i = 0; $i < 7; $i++) {
			$options['dayNames'][]      = DPCalendarHelper::dayToString($i, false);
			$options['dayNamesShort'][] = DPCalendarHelper::dayToString($i, true);

			if (function_exists('mb_substr')) {
				$options['dayNamesMin'][] = mb_substr(DPCalendarHelper::dayToString($i, true), 0, 2);
			} else {
				$options['dayNamesMin'][] = substr(DPCalendarHelper::dayToString($i, true), 0, 2);
			}
		}
		for ($i = 1; $i <= 12; $i++) {
			$options['monthNames'][]      = DPCalendarHelper::monthToString($i, false);
			$options['monthNamesShort'][] = DPCalendarHelper::monthToString($i, true);
		}

		return $options;
	}

	public function convertPHPDateToJS($format)
	{
		$replacements = [
			'A' => 'A',      // for the sake of escaping below
			'a' => 'a',      // for the sake of escaping below
			'B' => '',       // Swatch internet time (.beats), no equivalent
			'c' => 'YYYY-MM-DD[T]HH:mm:ssZ', // ISO 8601
			'D' => 'ddd',
			'd' => 'DD',
			'e' => 'zz',     // deprecated since version 1.6.0 of dayjs.js
			'F' => 'MMMM',
			'G' => 'H',
			'g' => 'h',
			'H' => 'HH',
			'h' => 'hh',
			'I' => '',       // Daylight Saving Time? => dayjs().isDST();
			'i' => 'mm',
			'j' => 'D',
			'L' => '',       // Leap year? => dayjs().isLeapYear();
			'l' => 'dddd',
			'M' => 'MMM',
			'm' => 'MM',
			'N' => 'E',
			'n' => 'M',
			'O' => 'ZZ',
			'o' => 'YYYY',
			'P' => 'Z',
			'r' => 'ddd, DD MMM YYYY HH:mm:ss ZZ', // RFC 2822
			'S' => 'o',
			's' => 'ss',
			'T' => 'z',      // deprecated since version 1.6.0 of dayjs.js
			't' => '',       // days in the month => dayjs().daysInMonth();
			'U' => 'X',
			'u' => 'SSSSSS', // microseconds
			'v' => 'SSS',    // milliseconds (from PHP 7.0.0)
			'W' => 'W',      // for the sake of escaping below
			'w' => 'e',
			'Y' => 'YYYY',
			'y' => 'YY',
			'Z' => '',       // time zone offset in minutes => dayjs().zone();
			'z' => 'DDD',
		];

		// Converts escaped characters
		foreach ($replacements as $from => $to) {
			$replacements['\\' . $from] = '[' . $from . ']';
		}

		return \JText::_(strtr($format, $replacements));
	}

	public static function minutesToDuration($minutes)
	{
		if (!$minutes) {
			return '00:00:00';
		}

		$buffer = str_pad(floor($minutes / 60), 2, '0', STR_PAD_LEFT);
		$buffer .= ':';
		$buffer .= str_pad($minutes % 60, 2, STR_PAD_LEFT);

		return $buffer . ':00';
	}
}
