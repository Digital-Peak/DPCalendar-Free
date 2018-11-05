<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_LIST');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_UNTIL');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_PAST');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TODAY');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TOMORROW');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_WEEK');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_WEEK');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_MONTH');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_MONTH');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_FUTURE');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_WEEK');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_MORE');

$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT');

$translator->translateJS('JCANCEL');
$translator->translateJS('JLIB_HTML_BEHAVIOR_CLOSE');
$translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY');

// The options which will be passed to the js library
$options                   = array();
$options['eventSources']   = array();
$options['eventSources'][] = $url = html_entity_decode(
	JRoute::_(
		'index.php?option=com_dpcalendar&view=events&limit=0&format=raw&' .
		'&compact=' . $params->get('compact_events', 2) . '&ids=' . implode(',', $ids) . '&openview=' . $params->get('open_view', 'agendaDay') .
		'&module-id=' . $module->id
	)
);

// Set the default view
$options['defaultView'] = $params->get('default_view', 'month');

// Translate to the fullcalendar view names
$mapping                = array(
	'day'   => 'agendaDay',
	'week'  => 'agendaWeek',
	'month' => 'month'
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

$options['eventColor']            = '#' . $params->get('event_color', '135CAE');
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
$options['displayEventTime'] = $params->get('compact_events', 2) != 2;

if ($params->get('event_limit', '') != '-1') {
	$options['eventLimit'] = $params->get('event_limit', '') == '' ? 2 : $params->get('event_limit', '') + 1;
}

// Set the height
if ($params->get('calendar_height', 0) > 0) {
	$options['contentHeight'] = (int)$params->get('calendar_height', 0);
} else {
	$options['height'] = 'auto';
}

$options['slotEventOverlap']  = (boolean)$params->get('overlap_events', 1);
$options['slotDuration']      = '00:' . $params->get('agenda_slot_minutes', 30) . ':00';
$options['slotLabelInterval'] = '00:' . $params->get('agenda_slot_minutes', 30) . ':00';
$options['slotLabelFormat']   = $dateHelper->convertPHPDateToMoment($params->get('axisformat', 'g:i a'));

// Set up the header
$options['header'] = array('left' => array(), 'center' => array(), 'right' => array());
if ($params->get('header_show_navigation', 1)) {
	$options['header']['left'][] = 'prev';
	$options['header']['left'][] = 'next';
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

$options['header']['left']   = implode(',', $options['header']['left']);
$options['header']['center'] = implode(',', $options['header']['center']);
$options['header']['right']  = implode(',', $options['header']['right']);

$resourceViews = $params->get('calendar_resource_views');

if (!\DPCalendar\Helper\DPCalendarHelper::isFree() && $resourceViews && $resources) {
	$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_SCHEDULER);

	$options['resources'] = $resources;
}

// Set up the views
$options['views']               = array();
$options['views']['month']      = array(
	'titleFormat'            => $dateHelper->convertPHPDateToMoment($params->get('titleformat_month', 'F Y')),
	'timeFormat'             => $dateHelper->convertPHPDateToMoment($params->get('timeformat_month', 'g:i a')),
	'columnHeaderFormat'     => $dateHelper->convertPHPDateToMoment($params->get('columnformat_month', 'D')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('month', $resourceViews)
);
$options['views']['agendaWeek'] = array(
	'titleFormat'            => $dateHelper->convertPHPDateToMoment($params->get('titleformat_week', 'M j Y')),
	'timeFormat'             => $dateHelper->convertPHPDateToMoment($params->get('timeformat_week', 'g:i a')),
	'columnHeaderFormat'     => $dateHelper->convertPHPDateToMoment($params->get('columnformat_week', 'D n/j')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('week', $resourceViews)
);
$options['views']['agendaDay']  = array(
	'titleFormat'            => $dateHelper->convertPHPDateToMoment($params->get('titleformat_day', 'F j Y')),
	'timeFormat'             => $dateHelper->convertPHPDateToMoment($params->get('timeformat_day', 'g:i a')),
	'columnHeaderFormat'     => $dateHelper->convertPHPDateToMoment($params->get('columnformat_day', 'l')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('day', $resourceViews)
);
$options['views']['list']       = array(
	'titleFormat'        => $dateHelper->convertPHPDateToMoment($params->get('titleformat_list', 'M j Y')),
	'timeFormat'         => $dateHelper->convertPHPDateToMoment($params->get('timeformat_list', 'g:i a')),
	'columnHeaderFormat' => $dateHelper->convertPHPDateToMoment($params->get('columnformat_list', 'D')),
	'listDayFormat'      => $dateHelper->convertPHPDateToMoment($params->get('dayformat_list', 'l')),
	'listDayAltFormat'   => $dateHelper->convertPHPDateToMoment($params->get('dateformat_list', 'F j, Y')),
	'duration'           => array('days' => $params->get('list_range', 30)),
	'noEventsMessage'    => $translator->translate('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND', true)
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
$options['event_create_form']     = 0;
$options['screen_size_list_view'] = 0;
$options['use_hash']              = false;

// Set the actual date
$now              = DPCalendarHelper::getDate();
$options['year']  = $now->format('Y', true);
$options['month'] = $now->format('m', true);
$options['date']  = $now->format('d', true);

$document->addScriptOptions('module.mini.' . $module->id . '.options', $options);
