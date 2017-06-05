<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.Calendar
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$value = $field->value;

if ($value == '') {
	return;
}

if (!is_array($value)) {
	$value = array(
		$value
	);
}

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

$texts = array();
foreach ($value as $calendarId) {
	if (!$calendarId) {
		continue;
	}

	// Getting the calendar to add the title to display
	$calendar = DPCalendarHelper::getCalendar($calendarId);
	if (!$calendar) {
		continue;
	}
	$texts[] = '<a href="' . DPCalendarHelperRoute::getCalendarRoute($calendarId) . '">' . htmlentities($calendar->title) . '</a>';
}

echo implode(', ', $texts);
