<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
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

JLoader::import('joomla.application.component.model');
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

$texts = [];
foreach ($value as $eventId) {
	if (!$eventId) {
		continue;
	}

	// Getting the event
	$model = JModelLegacy::getInstance('Event', 'DPCalendarModel', ['ignore_request' => true]);
	$event = $model->getItem($eventId);
	if (!$event) {
		continue;
	}

	$texts[] = '<a href="' . DPCalendarHelperRoute::getEventRoute($event->id, $event->catid) . '">' . htmlentities($event->title) . '</a>';
}
echo implode(', ', $texts);
