<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

// Loading the strings for javascript
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_LIST');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_UNTIL');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_PAST');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TODAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TOMORROW');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_WEEK');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_WEEK');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_MONTH');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_MONTH');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_FUTURE');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_WEEK');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_MORE');

$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT');

$this->translator->translateJS('JCANCEL');
$this->translator->translateJS('JLIB_HTML_BEHAVIOR_CLOSE');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY');
$this->translator->translateJS('COM_DPCALENDAR_CONFIRM_DELETE');

$this->dpdocument->addScriptOptions('calendar.names', $this->dateHelper->getNames());
$this->dpdocument->addScriptOptions('timezone', $this->dateHelper->getDate()->getTimezone()->getName());
$this->dpdocument->addScriptOptions('itemid', $this->input->getInt('Itemid', 0));

// Set up the params
$params = $this->params;

// The options which will be passed to the js library
$options                   = [];
$options['requestUrlRoot'] = 'view=events&format=raw&limit=0&my=' . $params->get('show_my_only_calendar', '0') .
	'&Itemid=' . $this->input->getInt('Itemid', 0);
$options['calendarIds']    = $this->selectedCalendars;

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

$options['firstDay']   = (int)$params->get('weekstart', 0);
$options['scrollTime'] = $params->get('first_hour', 6) . ':00:00';
$options['weekNumberCalculation'] = 'ISO';
$options['displayEventEnd'] = true;
$options['navLinks']        = true;

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
$options['displayEventTime'] = (boolean)$params->get('show_event_time', 1);

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
$options['slotLabelFormat']   = $this->dateHelper->convertPHPDateToJS($params->get('axisformat', 'H:i'));

// Set up the header
$options['headerToolbar'] = ['left' => [], 'center' => [], 'right' => []];
if ($params->get('header_show_navigation', 1)) {
	$options['headerToolbar']['left'][] = 'prev';
	$options['headerToolbar']['left'][] = 'next';
}
if ($params->get('header_show_datepicker', 1)) {
	$options['headerToolbar']['left'][] = 'datepicker';
}
if ($params->get('header_show_print', 1)) {
	$options['headerToolbar']['left'][] = 'print';
}
if ($params->get('header_show_create', 1) && \DPCalendar\Helper\DPCalendarHelper::canCreateEvent()) {
	$options['headerToolbar']['left'][] = 'add';
}
if ($params->get('header_show_title', 1)) {
	$options['headerToolbar']['center'][] = 'title';
}
if ($params->get('header_show_month', 1)) {
	$options['headerToolbar']['right'][] = 'month';
}
if ($params->get('header_show_week', 1)) {
	$options['headerToolbar']['right'][] = 'week';
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

if (!\DPCalendar\Helper\DPCalendarHelper::isFree() && $resourceViews && $this->resources) {
	$options['resources']           = $this->resources;
	$options['datesAboveResources'] = true;
}

// Set up the views
$options['views']          = [];
$options['views']['month'] = [
	'titleFormat'            => $this->dateHelper->convertPHPDateToJS($params->get('titleformat_month', 'F Y')),
	'eventTimeFormat'        => $this->dateHelper->convertPHPDateToJS($params->get('timeformat_month', 'H:i')),
	'dayHeaderFormat'        => $this->dateHelper->convertPHPDateToJS($params->get('columnformat_month', 'D')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('month', $resourceViews)
];
$options['views']['week']  = [
	'titleFormat'            => $this->dateHelper->convertPHPDateToJS($params->get('titleformat_week', 'M j Y')),
	'eventTimeFormat'        => $this->dateHelper->convertPHPDateToJS($params->get('timeformat_week', 'H:i')),
	'dayHeaderFormat'        => $this->dateHelper->convertPHPDateToJS($params->get('columnformat_week', 'D n/j')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('week', $resourceViews)
];
$options['views']['day']   = [
	'titleFormat'            => $this->dateHelper->convertPHPDateToJS($params->get('titleformat_day', 'F j Y')),
	'eventTimeFormat'        => $this->dateHelper->convertPHPDateToJS($params->get('timeformat_day', 'H:i')),
	'dayHeaderFormat'        => $this->dateHelper->convertPHPDateToJS($params->get('columnformat_day', 'l')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('day', $resourceViews)
];
$options['views']['list']  = [
	'titleFormat'       => $this->dateHelper->convertPHPDateToJS($params->get('titleformat_list', 'M j Y')),
	'eventTimeFormat'   => $this->dateHelper->convertPHPDateToJS($params->get('timeformat_list', 'H:i')),
	'dayHeaderFormat'   => $this->dateHelper->convertPHPDateToJS($params->get('columnformat_list', 'D')),
	'listDayFormat'     => $this->dateHelper->convertPHPDateToJS($params->get('dayformat_list', 'l')),
	'listDaySideFormat' => $this->dateHelper->convertPHPDateToJS($params->get('dateformat_list', 'F j, Y')),
	'duration'          => ['days' => (int)$params->get('list_range', 30)],
	'noEventsContent'   => $this->translate('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND', true)
];

// Some DPCalendar specific options
$options['show_event_as_popup']   = $params->get('show_event_as_popup');
$options['show_map']              = $params->get('show_map', 1);
$options['event_create_form']     = (int)$params->get('event_create_form', 1);
$options['screen_size_list_view'] = $params->get('screen_size_list_view', 500);
$options['use_hash']              = true;
if (\DPCalendar\Helper\DPCalendarHelper::canCreateEvent()) {
	$options['event_create_url'] = $this->router->getEventFormRoute(0, $this->returnPage, null, false);
}

// Set the actual date
$now              = DPCalendarHelper::getDate();
$options['year']  = $now->format('Y', true);
$options['month'] = $now->format('m', true);
$options['date']  = $now->format('d', true);

$this->dpdocument->addScriptOptions('view.calendar.' . $this->input->getInt('Itemid', 0) . '.options', $options);
