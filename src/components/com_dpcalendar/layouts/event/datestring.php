<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$event = $displayData['event'];
if (!$event) {
	return;
}

$dateFormat = null;
if (key_exists('dateFormat', $displayData)) {
	$dateFormat = $displayData['dateFormat'];
}
if (!$dateFormat) {
	$dateFormat = DPCalendarHelper::getComponentParameter('event_date_format', 'm.d.Y');
}

$timeFormat = null;
if (key_exists('timeFormat', $displayData)) {
	$timeFormat = $displayData['timeFormat'];
}
if (!$timeFormat) {
	$timeFormat = DPCalendarHelper::getComponentParameter('event_time_format', 'g:i a');
}

// These are the dates to display
$startDate     = DPCalendarHelper::getDate($event->start_date, $event->all_day)->format($dateFormat, true);
$startTime     = DPCalendarHelper::getDate($event->start_date, $event->all_day)->format($timeFormat, true);
$startTime     = trim($startTime);
$endDate       = DPCalendarHelper::getDate($event->end_date, $event->all_day)->format($dateFormat, true);
$endTime       = DPCalendarHelper::getDate($event->end_date, $event->all_day)->format($timeFormat, true);
$endTime       = trim($endTime);
$dateSeparator = '-';

$timeString = $startTime . ' ' . $startDate . ' ' . $dateSeparator . ' ' . $endTime . ' ' . $endDate;

if ($event->all_day) {
	if ($startDate == $endDate) {
		$timeString    = $startDate;
		$dateSeparator = '';
		$endDate       = '';
	} else {
		$timeString = $startDate . ' ' . $dateSeparator . ' ' . $endDate;
	}
} else {
	if ($startDate == $endDate) {
		if (empty($startTime) && empty($endTime)) {
			$timeString = $startDate;
		} else {
			$timeString = $startDate . ' ' . $startTime . ' ' . $dateSeparator . ' ' . $endTime;
		}
	}
}

echo $timeString;
