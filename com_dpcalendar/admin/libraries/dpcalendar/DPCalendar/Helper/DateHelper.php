<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
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

	public function convertPHPDateToMoment($format)
	{
		// Php date to fullcalendar date conversion
		$dateFormat = [
			'd' => 'DD',
			'D' => 'ddd',
			'j' => 'D',
			'l' => 'dddd',
			'N' => 'E',
			'S' => 'o',
			'w' => 'e',
			'z' => 'DDD',
			'W' => 'W',
			'F' => 'MMMM',
			'm' => 'MM',
			'M' => 'MMM',
			'n' => 'M',
			't' => '', // no equivalent
			'L' => '', // no equivalent
			'o' => 'YYYY',
			'Y' => 'YYYY',
			'y' => 'YY',
			'a' => 'a',
			'A' => 'A',
			'B' => '', // no equivalent
			'g' => 'h',
			'G' => 'H',
			'h' => 'hh',
			'H' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'u' => 'SSS',
			'e' => 'zz', // deprecated since version 1.6.0 of moment.js
			'I' => '', // no equivalent
			'O' => '', // no equivalent
			'P' => '', // no equivalent
			'T' => '', // no equivalent
			'Z' => '', // no equivalent
			'c' => '', // no equivalent
			'r' => '', // no equivalent
			'U' => 'X',
			'{' => '(',
			'}' => ')'
		];

		$formatArray = str_split($format);

		$newFormat = "";
		$isText    = false;
		$i         = 0;
		while ($i < count($formatArray)) {
			$chr = $formatArray[$i];
			if ($chr == '"' || $chr == "'") {
				$isText = !$isText;
			}
			$replaced = false;
			if ($isText == false) {
				foreach ($dateFormat as $zl => $jql) {
					if (substr($format, $i, strlen($zl)) == $zl) {
						$chr      = $jql;
						$i        += strlen($zl);
						$replaced = true;
						break;
					}
				}
			}
			if ($replaced == false) {
				$i++;
			}
			$newFormat .= $chr;
		}

		return \JText::_($newFormat);
	}
}
