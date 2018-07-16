<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Helper;

defined('_JEXEC') or die();

class DateHelper
{
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
			array('event' => $event, 'dateFormat' => $dateFormat, 'timeFormat' => $timeFormat),
			null,
			array('component' => 'com_dpcalendar', 'client' => 0)
		);
	}

	public function getNames()
	{
		$options                  = array();
		$options['monthNames']    = array();
		$options['dayNames']      = array();
		$options['dayNamesShort'] = array();
		for ($i = 0; $i < 7; $i++) {
			$options['dayNames'][]      = DPCalendarHelper::dayToString($i, false);
			$options['dayNamesShort'][] = DPCalendarHelper::dayToString($i, true);
		}
		for ($i = 1; $i <= 12; $i++) {
			$options['monthNames'][] = DPCalendarHelper::monthToString($i, false);
		}

		return $options;
	}

	public function convertPHPDateToMoment($format)
	{
		// Php date to fullcalendar date conversion
		$dateFormat = array(
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
		);

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

		return $newFormat;
	}
}
