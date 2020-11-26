<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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

$document->addScriptOptions('calendar.names', $dateHelper->getNames());
$document->addScriptOptions('timezone', $dateHelper->getDate()->getTimezone()->getName());
$document->addScriptOptions('itemid', $app->input->getInt('Itemid', 0));

// The options which will be passed to the js library
$options                   = [];
$options['requestUrlRoot'] = 'view=events&limit=0&format=raw&compact=' . $params->get('compact_events', 2) .
	'&openview=' . $params->get('open_view', 'agendaDay') . '&module-id=' . $module->id . '&Itemid=' . $app->input->getInt('Itemid', 0);
$options['calendarIds']    = [implode(',', $ids)];

// Set the default view
$options['initialView'] = $params->get('default_view', 'month');

// Some general calendar options
$options['weekNumbers']    = (boolean)$params->get('week_numbers');
$options['weekends']       = (boolean)$params->get('weekend', 1);
$options['fixedWeekCount'] = (boolean)$params->get('fixed_week_count', 1);

$bd = $params->get('business_hours_days', []);
if ($bd && !(count($bd) == 1 && !$bd[0])) {
	$options['businessHours'] = [
		'startTime'  => $params->get('business_hours_start', ''),
		'endTime'    => $params->get('business_hours_end', ''),
		'daysOfWeek' => $params->get('business_hours_days', [])
	];
}

$options['firstDay']              = (int)$params->get('weekstart', 0);
$options['scrollTime']            = $params->get('first_hour', 6) . ':00:00';
$options['weekNumberCalculation'] = 'ISO';
$options['displayEventEnd']       = true;
$options['navLinks']              = true;

$max = $params->get('max_time', 24);
if (is_numeric($max)) {
	$max = $max . ':00:00';
}
$options['slotMaxTime'] = $max;

$min = $params->get('min_time', 0);
if (is_numeric($min)) {
	$min = $min . ':00:00';
}
$options['slotMinTime'] = $min;

$options['nowIndicator']     = (boolean)$params->get('current_time_indicator', 1);
$options['displayEventTime'] = $params->get('compact_events', 2) != 2;

if ($params->get('event_limit', '') != '-1') {
	$options['dayMaxEventRows'] = $params->get('event_limit', '') == '' ? 2 : $params->get('event_limit', '') + 1;
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
$options['slotLabelFormat']   = $dateHelper->convertPHPDateToJS($params->get('axisformat', 'H:i'));

// Set up the header
$options['headerToolbar'] = ['left' => [], 'center' => [], 'right' => []];
if ($params->get('header_show_navigation', 1)) {
	$options['headerToolbar']['left'][] = 'prev';
	$options['headerToolbar']['left'][] = 'next';
}
if ($params->get('header_show_title', 1)) {
	$options['headerToolbar']['center'][] = 'title';
}
if ($params->get('header_show_month', 1)) {
	$options['headerToolbar']['right'][] = 'month';
}
if ($params->get('header_show_week', 1)) {
	$options['headerToolbar']['right'][] = 'agendaWeek';
}
if ($params->get('header_show_day', 1)) {
	$options['headerToolbar']['right'][] = 'day';
} else {
	$options['navLinks'] = false;
}
if ($params->get('header_show_list', 1)) {
	$options['headerToolbar']['right'][] = 'list';
}

$options['headerToolbar']['left']   = implode(',', $options['headerToolbar']['left']);
$options['headerToolbar']['center'] = implode(',', $options['headerToolbar']['center']);
$options['headerToolbar']['right']  = implode(',', $options['headerToolbar']['right']);

$resourceViews = $params->get('calendar_resource_views');

if (!\DPCalendar\Helper\DPCalendarHelper::isFree() && $resourceViews && $resources) {
	$options['resources'] = $resources;
}

// Set up the views
$options['views']               = [];
$options['views']['month']      = [
	'titleFormat'            => $dateHelper->convertPHPDateToJS($params->get('titleformat_month', 'F Y')),
	'eventTimeFormat'        => $dateHelper->convertPHPDateToJS($params->get('timeformat_month', 'H:i')),
	'dayHeaderFormat'        => $dateHelper->convertPHPDateToJS($params->get('columnformat_month', 'D')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('month', $resourceViews)
];
$options['views']['agendaWeek'] = [
	'titleFormat'            => $dateHelper->convertPHPDateToJS($params->get('titleformat_week', 'M j Y')),
	'eventTimeFormat'        => $dateHelper->convertPHPDateToJS($params->get('timeformat_week', 'H:i')),
	'dayHeaderFormat'        => $dateHelper->convertPHPDateToJS($params->get('columnformat_week', 'D n/j')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('week', $resourceViews)
];
$options['views']['agendaDay']  = [
	'titleFormat'            => $dateHelper->convertPHPDateToJS($params->get('titleformat_day', 'F j Y')),
	'eventTimeFormat'        => $dateHelper->convertPHPDateToJS($params->get('timeformat_day', 'H:i')),
	'dayHeaderFormat'        => $dateHelper->convertPHPDateToJS($params->get('columnformat_day', 'l')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('day', $resourceViews)
];
$options['views']['list']       = [
	'titleFormat'       => $dateHelper->convertPHPDateToJS($params->get('titleformat_list', 'M j Y')),
	'eventTimeFormat'   => $dateHelper->convertPHPDateToJS($params->get('timeformat_list', 'H:i')),
	'dayHeaderFormat'   => $dateHelper->convertPHPDateToJS($params->get('columnformat_list', 'D')),
	'listDayFormat'     => $dateHelper->convertPHPDateToJS($params->get('dayformat_list', 'l')),
	'listDaySideFormat' => $dateHelper->convertPHPDateToJS($params->get('dateformat_list', 'F j, Y')),
	'duration'          => ['days' => (int)$params->get('list_range', 30)],
	'noEventsContent'   => $translator->translate('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND', true)
];

// Some DPCalendar specific options
$options['show_event_as_popup']   = $params->get('show_event_as_popup');
$options['show_map']              = $params->get('show_map', 1);
$options['event_create_form']     = 0;
$options['screen_size_list_view'] = 0;
$options['use_hash']              = false;

// Set the actual date
$now              = DPCalendarHelper::getDate($params->get('start_date'));
$options['year']  = $now->format('Y', true);
$options['month'] = $now->format('m', true);
$options['date']  = $now->format('d', true);

$document->addScriptOptions('module.mini.' . $module->id . '.options', $options);
