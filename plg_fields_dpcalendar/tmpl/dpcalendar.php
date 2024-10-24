<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;

/** @var \stdClass $field */

$value = $field->value;
if ($value == '') {
	return;
}

if (!is_array($value)) {
	$value = [$value];
}

$texts = [];
foreach ($value as $calendarId) {
	if (!$calendarId) {
		continue;
	}

	// Getting the calendar to add the title to display
	$calendar = \Joomla\CMS\Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($calendarId);
	if (!$calendar instanceof \DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface) {
		continue;
	}

	$texts[] = '<a href="' . RouteHelper::getCalendarRoute($calendarId) . '">' . htmlentities($calendar->getTitle(), ENT_COMPAT, 'UTF-8') . '</a>';
}

echo implode(', ', $texts);
