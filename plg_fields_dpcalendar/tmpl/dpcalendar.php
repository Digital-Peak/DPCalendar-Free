<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$value = $field->value;
if ($value == '') {
	return;
}

if (!is_array($value)) {
	$value = [$value];
}

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

$texts = [];
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
