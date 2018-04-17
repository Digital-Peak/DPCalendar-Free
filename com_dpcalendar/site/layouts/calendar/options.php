<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Component\Icon;
use DPCalendar\Helper\Fullcalendar;

// Loading the strings for javascript
JFactory::getLanguage()->load('com_dpcalendar', JPATH_SITE . '/components/com_dpcalendar');
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_LIST', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_UNTIL', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_PAST', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TODAY', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TOMORROW', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_WEEK', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_WEEK', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_MONTH', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_MONTH', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_FUTURE', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_WEEK', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_MORE', true);

JText::script('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT', true);

JText::script('JCANCEL', true);
JText::script('JLIB_HTML_BEHAVIOR_CLOSE', true);
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY', true);

// Set up the params
$params = $displayData['params'];

// The root element
$root = $displayData['root'];

$horizontalMode = $params->get('header_show_timeline_day') ||
	$params->get('header_show_timeline_week') ||
	$params->get('header_show_timeline_month') ||
	$params->get('header_show_timeline_year');

// The options which will be passed to the js library
$options                 = array();
$options['eventSources'] = array();
foreach ($displayData['selectedCalendars'] as $calendar) {
	$options['eventSources'][] = html_entity_decode(
		JRoute::_(
			'index.php?option=com_dpcalendar&view=events&format=raw&limit=0' .
			'&ids=' . $calendar .
			'&my=' . $params->get('show_my_only_calendar', '0') .
			'&l=' . ($horizontalMode ? 1 : 0) .
			'&Itemid=' . JFactory::getApplication()->input->getInt('Itemid', 0)
		)
	);
}

// Set the default view
$options['defaultView'] = $params->get('default_view', 'month');

// Translate to the fullcalendar view names
$mapping                = array(
	'day'      => 'agendaDay',
	'week'     => 'agendaWeek',
	'month'    => 'month',
	'resday'   => 'timelineDay',
	'resweek'  => 'timelineWeek',
	'resmonth' => 'timelineMonth'
);
$options['defaultView'] = $params->get('default_view', 'month');
if (key_exists($params->get('default_view', 'month'), $mapping)) {
	$options['defaultView'] = $mapping[$params->get('default_view', 'month')];
}

// Some general calendar options
$options['weekNumbers']    = (boolean)$params->get('week_numbers');
$options['weekends']       = (boolean)$params->get('weekend', 1);
$options['fixedWeekCount'] = (boolean)$params->get('fixed_week_count', 1);

$bd = $params->get('business_hours_days', array());
if ($bd && !(count($bd) == 1 && !$bd[0])) {
	$options['businessHours'] = array(
		'start' => $params->get('business_hours_start', ''),
		'end'   => $params->get('business_hours_end', ''),
		'dow'   => $params->get('business_hours_days', array())
	);
}

$options['firstDay']              = $params->get('weekstart', 0);
$options['firstHour']             = $params->get('first_hour', 6);
$options['nextDayThreshold']      = '00:00:00';
$options['weekNumbersWithinDays'] = false;
$options['weekNumberCalculation'] = 'ISO';
$options['displayEventEnd']       = true;
$options['navLinks']              = true;

$max = $params->get('max_time', 24);
if (is_numeric($max)) {
	$max = $max . ':00:00';
}
$options['maxTime'] = $max;

$min = $params->get('min_time', 0);
if (is_numeric($min)) {
	$min = $min . ':00:00';
}
$options['minTime'] = $min;

$options['nowIndicator']     = (boolean)$params->get('current_time_indicator', 1);
$options['displayEventTime'] = (boolean)$params->get('show_event_time', 1);

if ($params->get('event_limit', '') != '-1') {
	$options['eventLimit'] = $params->get('event_limit', '') == '' ? 2 : $params->get('event_limit', '') + 1;
}

// Set the height
if ($params->get('calendar_height', 0) > 0) {
	$options['contentHeight'] = (int)$params->get('calendar_height', 0);
} else {
	$options['height'] = 'auto';
}

$options['slotEventOverlap'] = (boolean)$params->get('overlap_events', 1);
$options['slotMinutes']      = $params->get('agenda_slot_minutes', 30);
$options['slotLabelFormat']  = Fullcalendar::convertFromPHPDate($params->get('axisformat', 'g:i a'));

// Set up the header
$options['header'] = array('left' => array(), 'center' => array(), 'right' => array());
if ($params->get('header_show_navigation', 1)) {
	$options['header']['left'][] = 'prev';
	$options['header']['left'][] = 'next';
}
if ($params->get('header_show_datepicker', 1)) {
	JHtml::_('script', 'com_dpcalendar/pikaday/pikaday.min.js', ['relative' => true], ['defer' => true]);
	JHtml::_('stylesheet', 'com_dpcalendar/pikaday/pikaday.min.css', ['relative' => true]);
	JHtml::_('stylesheet', 'com_dpcalendar/pikaday/custom.css', ['relative' => true]);
	$options['header']['left'][] = 'datepicker';
}
if ($params->get('header_show_print', 1)) {
	$options['header']['left'][] = 'print';
}
if ($params->get('header_show_title', 1)) {
	$options['header']['center'][] = 'title';
}
if ($params->get('header_show_month', 1)) {
	$options['header']['right'][] = 'month';
}
if ($params->get('header_show_week', 1)) {
	$options['header']['right'][] = 'agendaWeek';
}
if ($params->get('header_show_day', 1)) {
	$options['header']['right'][] = 'agendaDay';
} else {
	$options['navLinks'] = false;
}
if ($params->get('header_show_list', 1)) {
	$options['header']['right'][] = 'list';
}
if ($params->get('header_show_timeline_day')) {
	$options['header']['right'][] = 'timelineDay';
}
if ($params->get('header_show_timeline_week')) {
	$options['header']['right'][] = 'timelineWeek';
}
if ($params->get('header_show_timeline_month')) {
	$options['header']['right'][] = 'timelineMonth';
}
if ($params->get('header_show_timeline_year')) {
	$options['header']['right'][] = 'timelineYear';
}

$options['header']['left']   = implode(',', $options['header']['left']);
$options['header']['center'] = implode(',', $options['header']['center']);
$options['header']['right']  = implode(',', $options['header']['right']);

$resourceViews = $params->get('calendar_resource_views');
$resources     = $params->get('calendar_filter_locations');

if (!\DPCalendar\Helper\DPCalendarHelper::isFree() && $resourceViews && $resources) {
	\JHtml::_('script', 'com_dpcalendar/scheduler/scheduler.min.js', ['relative' => true], ['defer' => true]);
	\JHtml::_('stylesheet', 'com_dpcalendar/scheduler/scheduler.min.css', ['relative' => true]);

	$options['slotLabelFormat'] = null;
	$options['slotWidth']       = $params->get('location_column_width');
	$options['smallTimeFormat'] = Fullcalendar::convertFromPHPDate($params->get('timeformat_day', 'g:i a'));

	// Load the model
	JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
	$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
	$model->getState();
	$model->setState('list.limit', 10000);
	$model->setState('filter.search', 'ids:' . implode($resources, ','));

	// The resources data
	$resources = array();

	// Add the locations
	foreach ($model->getItems() as $location) {
		$rooms = array();
		if ($location->rooms) {
			foreach ($location->rooms as $room) {
				$rooms[] = (object)array('id' => $location->id . '-' . $room->id, 'title' => $room->title);
			}
		}

		if ($horizontalMode || !$rooms) {
			$resources[] = (object)array('id' => $location->id, 'title' => $location->title, 'children' => $rooms);
		} else {
			$resources = array_merge($resources, $rooms);
		}
	}
	$options['resources'] = $resources;
}
$options['resourceLabelText'] = JText::_('COM_DPCALENDAR_LAYOUT_CALENDAR_LOCATIONS_AND_ROOMS');

// Set up the views
$options['views']               = array();
$options['views']['month']      = array(
	'titleFormat'            => Fullcalendar::convertFromPHPDate($params->get('titleformat_month', 'F Y')),
	'timeFormat'             => Fullcalendar::convertFromPHPDate($params->get('timeformat_month', 'g:i a')),
	'columnHeaderFormat'     => Fullcalendar::convertFromPHPDate($params->get('columnformat_month', 'D')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('month', $resourceViews)
);
$options['views']['agendaWeek'] = array(
	'titleFormat'            => Fullcalendar::convertFromPHPDate($params->get('titleformat_week', 'M j Y')),
	'timeFormat'             => Fullcalendar::convertFromPHPDate($params->get('timeformat_week', 'g:i a')),
	'columnHeaderFormat'     => Fullcalendar::convertFromPHPDate($params->get('columnformat_week', 'D n/j')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('week', $resourceViews)
);
$options['views']['agendaDay']  = array(
	'titleFormat'            => Fullcalendar::convertFromPHPDate($params->get('titleformat_day', 'F j Y')),
	'timeFormat'             => Fullcalendar::convertFromPHPDate($params->get('timeformat_day', 'g:i a')),
	'columnHeaderFormat'     => Fullcalendar::convertFromPHPDate($params->get('columnformat_day', 'l')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('day', $resourceViews)
);
$options['views']['list']       = array(
	'titleFormat'        => Fullcalendar::convertFromPHPDate($params->get('titleformat_list', 'M j Y')),
	'timeFormat'         => Fullcalendar::convertFromPHPDate($params->get('timeformat_list', 'g:i a')),
	'columnHeaderFormat' => Fullcalendar::convertFromPHPDate($params->get('columnformat_list', 'D')),
	'listDayFormat'      => Fullcalendar::convertFromPHPDate($params->get('dayformat_list', 'l')),
	'listDayAltFormat'   => Fullcalendar::convertFromPHPDate($params->get('dateformat_list', 'F j, Y')),
	'duration'           => array('days' => $params->get('list_range', 30)),
	'noEventsMessage'    => JText::_('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND', true)
);

// Timeline views
$options['views']['timelineYear']  = array(
	'timeFormat' => Fullcalendar::convertFromPHPDate($params->get('timeformat_timeline_year', 'g:i a'))
);
$options['views']['timelineMonth'] = array(
	'timeFormat' => Fullcalendar::convertFromPHPDate($params->get('timeformat_timeline_month', 'g:i a'))
);
$options['views']['timelineWeek']  = array(
	'timeFormat' => Fullcalendar::convertFromPHPDate($params->get('timeformat_timeline_week', 'g:i a'))
);
$options['views']['timelineDay']   = array(
	'timeFormat' => Fullcalendar::convertFromPHPDate($params->get('timeformat_timeline_day', 'g:i a'))
);

// Set up the month and day names
$options['monthNames']      = array();
$options['monthNamesShort'] = array();
$options['dayNames']        = array();
$options['dayNamesShort']   = array();
$options['dayNamesMin']     = array();
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

// Some DPCalendar specific options
$options['show_event_as_popup']   = $params->get('show_event_as_popup');
$options['show_map']              = $params->get('show_map', 1);
$options['use_hash']              = $params->get('use_hash');
$options['event_create_form']     = (int)$params->get('event_create_form', 1);
$options['screen_size_list_view'] = $params->get('screen_size_list_view', 500);
$options['use_hash']              = $params->get('use_hash');

// Workaround to get the icon classes
$pi = new Icon('print', Icon::PRINTING);
DPCalendarHelper::renderElement($pi, $params);
$options['icon_print'] = $pi->getClasses() ? implode(' ', $pi->getClasses()) : 'icon-print';
$ci                    = new Icon('calendar', Icon::CALENDAR);
DPCalendarHelper::renderElement($ci, $params);
$options['icon_calendar'] = $ci->getClasses() ? implode(' ', $ci->getClasses()) : 'icon-calendar';

// Set the actual date
$now              = DPCalendarHelper::getDate();
$options['year']  = $now->format('Y', true);
$options['month'] = $now->format('m', true);
$options['date']  = $now->format('d', true);

$hideCode = '';

if ($params->get('show_selection', 1) == 3) {
	$hideCode = 'document.getElementById("' . $displayData['id'] . '").parentElement.querySelector(".dp-calendar-list").style.display = "block";';
}

$calCode = "document.addEventListener('DOMContentLoaded', function () {
	" . $hideCode . "
	DPCalendar.createCalendar(jQuery('#" . $displayData['id'] . "'), " . json_encode($options, JDEBUG ? JSON_PRETTY_PRINT : 0) . ");
});";

echo $calCode;
