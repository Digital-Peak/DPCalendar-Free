<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\Utilities\ArrayHelper;

// Loading the strings for javascript
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY');

$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_NEXT');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PREVIOUS');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ADD');

$this->translator->translateJS('JCANCEL');
$this->translator->translateJS('COM_DPCALENDAR_CLOSE');
$this->translator->translateJS('COM_DPCALENDAR_PREVIOUS');
$this->translator->translateJS('COM_DPCALENDAR_NEXT');
$this->translator->translateJS('COM_DPCALENDAR_CONFIRM_DELETE');
$this->translator->translateJS('COM_DPCALENDAR_FIELD_CAPACITY_UNLIMITED');

$this->dpdocument->addScriptOptions('calendar.names', $this->dateHelper->getNames());
$this->dpdocument->addScriptOptions('timezone', $this->dateHelper->getDate()->getTimezone()->getName());
$this->dpdocument->addScriptOptions('itemid', $this->input->getInt('Itemid', 0));

// Set up the params
$params = $this->params;

// The options which will be passed to the js library
$options                   = [];
$options['requestUrlRoot'] = 'view=events&format=raw&limit=0&Itemid=' . $this->input->getInt('Itemid', 0);
$options['calendarIds'] = $this->selectedCalendars;

// Set the default view
$options['initialView'] = 'res' . $params->get('default_view', 'month');

// Some general calendar options
$options['weekNumbers']    = (bool)$params->get('week_numbers');
$options['weekends']       = (bool)$params->get('weekend', 1);
$options['fixedWeekCount'] = (bool)$params->get('fixed_week_count', 1);

$bd = $params->get('business_hours_days', []);
if ($bd && !((is_countable($bd) ? count($bd) : 0) == 1 && !$bd[0])) {
	$options['businessHours'] = [
		'startTime'  => $params->get('business_hours_start', ''),
		'endTime'    => $params->get('business_hours_end', ''),
		'daysOfWeek' => $params->get('business_hours_days', [])
	];
}

$options['firstDay']              = (int)$params->get('weekstart', 1);
$options['hiddenDays']            = ArrayHelper::toInteger($this->params->get('hidden_days', []));
$options['scrollTime']            = $params->get('first_hour', 6) . ':00:00';
$options['weekNumberCalculation'] = 'ISO';
$options['displayEventEnd']       = true;
$options['navLinks']              = true;

$max = $params->get('max_time', 24);
if (is_numeric($max)) {
	$max .= ':00:00';
}
$options['slotMaxTime'] = $max;

$min = $params->get('min_time', 0);
if (is_numeric($min)) {
	$min .= ':00:00';
}
$options['slotMinTime'] = $min;

$options['nowIndicator']     = (bool)$params->get('current_time_indicator', 1);
$options['displayEventTime'] = (bool)$params->get('show_event_time', 1);

if ($params->get('event_limit', '') != '-1') {
	$options['dayMaxEventRows'] = $params->get('event_limit', '') == '' ? 2 : $params->get('event_limit', '') + 1;
}

// Set the height
if ($params->get('calendar_height', 0) > 0) {
	$options['contentHeight'] = (int)$params->get('calendar_height', 0);
} else {
	$options['height'] = 'auto';
}
$options['resourceAreaWidth'] = $params->get('calendar_timeline_resource_column_width', '30%');

$options['slotEventOverlap'] = (bool)$params->get('overlap_events', 1);

// Set up the header
$options['headerToolbar'] = ['left' => [], 'center' => [], 'right' => []];
if ($params->get('header_show_navigation', 1)) {
	$options['headerToolbar']['left'][] = 'prev';
	$options['headerToolbar']['left'][] = 'next';
}
if ($params->get('header_show_today', 1)) {
	$options['headerToolbar']['left'][] = 'today';
}
if ($params->get('header_show_datepicker', 1)) {
	$options['headerToolbar']['left'][] = 'datepicker';
}
if ($params->get('header_show_print', 1)) {
	$options['headerToolbar']['left'][] = 'print';
}
if ($params->get('header_show_create', 1) && DPCalendarHelper::canCreateEvent()) {
	$options['headerToolbar']['left'][] = 'add';
}
if ($params->get('header_show_title', 1)) {
	$options['headerToolbar']['center'][] = 'title';
}
if ($params->get('header_show_month', 1)) {
	$options['headerToolbar']['right'][] = 'resmonth';
}
if ($params->get('header_show_week', 1)) {
	$options['headerToolbar']['right'][] = 'resweek';
}
if ($params->get('header_show_day', 1)) {
	$options['headerToolbar']['right'][] = 'resday';
} else {
	$options['navLinks'] = false;
}

$options['headerToolbar']['left']   = implode(',', $options['headerToolbar']['left']);
$options['headerToolbar']['center'] = implode(',', $options['headerToolbar']['center']);
$options['headerToolbar']['right']  = implode(',', $options['headerToolbar']['right']);

$options['resources']                 = $this->resources;
$options['resourceAreaHeaderContent'] = $this->translate('COM_DPCALENDAR_CALENDARS');
$options['resourceOrder']             = 'title,id';

// Set up the views
$options['views']             = [];
$options['views']['resmonth'] = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToJS($params->get('titleformat_month', 'F Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToJS($params->get('timeformat_month', 'H:i')),
	'dayHeaderFormat' => $this->dateHelper->convertPHPDateToJS($params->get('columnformat_month', 'D')),
	'slotLabelFormat' => $this->dateHelper->convertPHPDateToJS($params->get('axisformat_month', 'l j'))
];
$options['views']['resweek'] = [
	'titleFormat'       => $this->dateHelper->convertPHPDateToJS($params->get('titleformat_week', 'M j Y')),
	'eventTimeFormat'   => $this->dateHelper->convertPHPDateToJS($params->get('timeformat_week', 'H:i')),
	'dayHeaderFormat'   => $this->dateHelper->convertPHPDateToJS($params->get('columnformat_week', 'D n/j')),
	'slotDuration'      => '{"hours": 1}',
	'slotLabelInterval' => '{"hours": 1}',
	'slotLabelFormat'   => $this->dateHelper->convertPHPDateToJS($params->get('axisformat_week', 'D j H:i'))
];
$options['views']['resday'] = [
	'titleFormat'       => $this->dateHelper->convertPHPDateToJS($params->get('titleformat_day', 'F j Y')),
	'eventTimeFormat'   => $this->dateHelper->convertPHPDateToJS($params->get('timeformat_day', 'H:i')),
	'dayHeaderFormat'   => $this->dateHelper->convertPHPDateToJS($params->get('columnformat_day', 'l')),
	'slotDuration'      => $this->dateHelper->minutesToDuration((int)$params->get('agenda_slot_minutes', 30)),
	'slotLabelInterval' => $this->dateHelper->minutesToDuration((int)$params->get('agenda_slot_minutes', 30)),
	'slotLabelFormat'   => $this->dateHelper->convertPHPDateToJS($params->get('axisformat_day', 'H:i'))
];

// Some DPCalendar specific options
$options['show_event_as_popup'] = $params->get('show_event_as_popup');
$options['popupWidth']          = $params->get('popup_width');
$options['popupHeight']         = $params->get('popup_height');
$options['show_map']            = $params->get('show_map', 1);
$options['event_create_form']   = (int)$params->get('event_create_form', 1);
$options['use_hash']            = true;
if (DPCalendarHelper::canCreateEvent()) {
	$options['event_create_url'] = $this->router->getEventFormRoute(0, $this->returnPage, null, false);
}

// Set the actual date
$now              = DPCalendarHelper::getDate($params->get('calendar_start_date'));
$options['year']  = $now->format('Y', true);
$options['month'] = $now->format('m', true);
$options['date']  = $now->format('d', true);
if (DPCalendarHelper::canCreateEvent()) {
	$options['event_create_url'] = $this->router->getEventFormRoute(0, $this->returnPage);
}

$this->dpdocument->addScriptOptions('view.calendar.' . $this->input->getInt('Itemid', 0) . '.options', $options);
