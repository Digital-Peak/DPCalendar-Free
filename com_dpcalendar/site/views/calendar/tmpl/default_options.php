<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($this->params->get('header_show_datepicker', 1)) {
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DATEPICKER);
}

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

// Set up the params
$params = $this->params;

// The options which will be passed to the js library
$options                 = array();
$options['eventSources'] = array();
foreach ($this->selectedCalendars as $calendar) {
	$options['eventSources'][] = html_entity_decode(
		JRoute::_(
			'index.php?option=com_dpcalendar&view=events&format=raw&limit=0' .
			'&ids=' . $calendar .
			'&my=' . $params->get('show_my_only_calendar', '0') .
			'&Itemid=' . $this->input->getInt('Itemid', 0)
		)
	);
}

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

$options['slotEventOverlap']  = (boolean)$params->get('overlap_events', 1);
$options['slotDuration']      = '00:' . $params->get('agenda_slot_minutes', 30) . ':00';
$options['slotLabelInterval'] = '00:' . $params->get('agenda_slot_minutes', 30) . ':00';
$options['slotLabelFormat']   = $this->dateHelper->convertPHPDateToMoment($params->get('axisformat', 'g:i a'));

// Set up the header
$options['header'] = array('left' => array(), 'center' => array(), 'right' => array());
if ($params->get('header_show_navigation', 1)) {
	$options['header']['left'][] = 'prev';
	$options['header']['left'][] = 'next';
}
if ($params->get('header_show_datepicker', 1)) {
	$options['header']['left'][] = 'datepicker';
}
if ($params->get('header_show_print', 1)) {
	$options['header']['left'][] = 'print';
}
if ($params->get('header_show_create', 1)) {
	$options['header']['left'][] = 'add';
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

if (!\DPCalendar\Helper\DPCalendarHelper::isFree() && $resourceViews && $this->resources) {
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_SCHEDULER);

	$options['resources'] = $this->resources;
}

// Set up the views
$options['views']               = array();
$options['views']['month']      = array(
	'titleFormat'            => $this->dateHelper->convertPHPDateToMoment($params->get('titleformat_month', 'F Y')),
	'timeFormat'             => $this->dateHelper->convertPHPDateToMoment($params->get('timeformat_month', 'g:i a')),
	'columnHeaderFormat'     => $this->dateHelper->convertPHPDateToMoment($params->get('columnformat_month', 'D')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('month', $resourceViews)
);
$options['views']['agendaWeek'] = array(
	'titleFormat'            => $this->dateHelper->convertPHPDateToMoment($params->get('titleformat_week', 'M j Y')),
	'timeFormat'             => $this->dateHelper->convertPHPDateToMoment($params->get('timeformat_week', 'g:i a')),
	'columnHeaderFormat'     => $this->dateHelper->convertPHPDateToMoment($params->get('columnformat_week', 'D n/j')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('week', $resourceViews)
);
$options['views']['agendaDay']  = array(
	'titleFormat'            => $this->dateHelper->convertPHPDateToMoment($params->get('titleformat_day', 'F j Y')),
	'timeFormat'             => $this->dateHelper->convertPHPDateToMoment($params->get('timeformat_day', 'g:i a')),
	'columnHeaderFormat'     => $this->dateHelper->convertPHPDateToMoment($params->get('columnformat_day', 'l')),
	'groupByDateAndResource' => !empty($options['resources']) && in_array('day', $resourceViews)
);
$options['views']['list']       = array(
	'titleFormat'        => $this->dateHelper->convertPHPDateToMoment($params->get('titleformat_list', 'M j Y')),
	'timeFormat'         => $this->dateHelper->convertPHPDateToMoment($params->get('timeformat_list', 'g:i a')),
	'columnHeaderFormat' => $this->dateHelper->convertPHPDateToMoment($params->get('columnformat_list', 'D')),
	'listDayFormat'      => $this->dateHelper->convertPHPDateToMoment($params->get('dayformat_list', 'l')),
	'listDayAltFormat'   => $this->dateHelper->convertPHPDateToMoment($params->get('dateformat_list', 'F j, Y')),
	'duration'           => array('days' => $params->get('list_range', 30)),
	'noEventsMessage'    => $this->translate('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND', true)
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
$options['event_create_form']     = (int)$params->get('event_create_form', 1);
$options['screen_size_list_view'] = $params->get('screen_size_list_view', 500);
$options['use_hash']              = true;
if (\DPCalendar\Helper\DPCalendarHelper::canCreateEvent()) {
	$options['event_create_url'] = $this->router->getEventFormRoute(0, $this->return);
}

// Set the actual date
$now              = DPCalendarHelper::getDate();
$options['year']  = $now->format('Y', true);
$options['month'] = $now->format('m', true);
$options['date']  = $now->format('d', true);

$this->dpdocument->addScriptOptions('view.calendar.options', $options);
