<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
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
JText::script('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY', true);

// Set up the params
$params = $displayData['params'];

// The root element
$root = $displayData['root'];

// The options which will be passed to the js library
$options                 = array();
$options['eventSources'] = array();
foreach ($displayData['selectedCalendars'] as $calendar) {
	$options['eventSources'][] = html_entity_decode(
		JRoute::_(
			'index.php?option=com_dpcalendar&view=events&format=raw&limit=0&ids=' . $calendar . '&my=' .
			$params->get('show_my_only_calendar', '0') . '&Itemid=' . JFactory::getApplication()->input->getInt('Itemid', 0)
		)
	);
}

// Set the default view
$options['defaultView'] = $params->get('default_view', 'month');

// Translate to the fullcalendar view names
if ($params->get('default_view', 'month') == 'week') {
	$options['defaultView'] = 'agendaWeek';
} elseif ($params->get('default_view', 'month') == 'day') {
	$options['defaultView'] = 'agendaDay';
}

// Some general calendar options
$options['weekNumbers']    = (boolean)$params->get('week_numbers');
$options['weekends']       = (boolean)$params->get('weekend', 1);
$options['fixedWeekCount'] = $params->get('week_mode', 'variable') == 'fixed';

if ($params->get('week_mode', 'variable') != 'variable') {
	$options['height'] = 'auto';
}

if ($params->get('business_hours_days', array())) {
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

$options['nowIndicator'] = (boolean)$params->get('current_time_indicator', 1);

if ($params->get('event_limit', '') != '-1') {
	$options['eventLimit'] = $params->get('event_limit', '') == '' ? 'true' : $params->get('event_limit', '') + 1;
}

if ($params->get('calendar_height', 0) > 0) {
	$options['contentHeight'] = (int)$params->get('calendar_height', 0);
}

$options['slotEventOverlap'] = (boolean)$params->get('overlap_events', 1);
$options['slotMinutes']      = $params->get('agenda_slot_minutes', 30);
$options['slotLabelFormat']  = Fullcalendar::convertFromPHPDate($params->get('axisformat', 'h(:mm)a'));

// Set up the header
$options['header'] = array('left' => array(), 'center' => array(), 'right' => array());
if ($params->get('header_show_navigation', 1)) {
	$options['header']['left'][] = 'prev';
	$options['header']['left'][] = 'next';
}
if ($params->get('header_show_datepicker', 1)) {
	DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true, 'fullcalendar' => true, 'datepicker' => true));
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
}
if ($params->get('header_show_list', 1)) {
	$options['header']['right'][] = 'list';
}

$options['header']['left']   = implode(',', $options['header']['left']);
$options['header']['center'] = implode(',', $options['header']['center']);
$options['header']['right']  = implode(',', $options['header']['right']);

// Set up the views
$options['views']               = array();
$options['views']['month']      = array(
	'titleFormat'  => Fullcalendar::convertFromPHPDate($params->get('titleformat_month', 'F Y')),
	'timeFormat'   => Fullcalendar::convertFromPHPDate($params->get('timeformat_month', 'g:i a')),
	'columnFormat' => Fullcalendar::convertFromPHPDate($params->get('columnformat_month', 'D'))
);
$options['views']['agendaWeek'] = array(
	'titleFormat'  => Fullcalendar::convertFromPHPDate($params->get('titleformat_week', 'M j Y')),
	'timeFormat'   => Fullcalendar::convertFromPHPDate($params->get('timeformat_week', 'g:i a')),
	'columnFormat' => Fullcalendar::convertFromPHPDate($params->get('columnformat_week', 'D n/j'))
);
$options['views']['agendaDay']  = array(
	'titleFormat'  => Fullcalendar::convertFromPHPDate($params->get('titleformat_day', 'F j Y')),
	'timeFormat'   => Fullcalendar::convertFromPHPDate($params->get('timeformat_day', 'g:i a')),
	'columnFormat' => Fullcalendar::convertFromPHPDate($params->get('columnformat_day', 'l'))
);
$options['views']['list']       = array(
	'titleFormat'      => Fullcalendar::convertFromPHPDate($params->get('titleformat_list', 'M j Y')),
	'timeFormat'       => Fullcalendar::convertFromPHPDate($params->get('timeformat_list', 'g:i a')),
	'columnFormat'     => Fullcalendar::convertFromPHPDate($params->get('columnformat_list', 'D')),
	'listDayFormat'    => Fullcalendar::convertFromPHPDate($params->get('dayformat_list', 'l')),
	'listDayAltFormat' => Fullcalendar::convertFromPHPDate($params->get('dateformat_list', 'F j, Y')),
	'duration'         => array('days' => $params->get('list_range', 30)),
	'noEventsMessage'  => JText::_('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND', true)
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
$options['show_date_picker']      = $params->get('show_date_picker', 1);
$options['show_print']            = $params->get('show_print', 1);
$options['use_hash']              = $params->get('use_hash');
$options['event_edit_popup']      = $params->get('event_edit_popup', 1);
$options['screen_size_list_view'] = $params->get('screen_size_list_view', 500);

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

$calCode = "jQuery(document).ready(function() {
	var calendar = jQuery('#" . $displayData['id'] . "');
	var options = " . json_encode($options) . ";
";

if ($params->get('use_hash')) {
	$calCode .= "
	// Parsing the hash
	var vars = window.location.hash.replace(/&amp;/gi, '&').split('&');
	for (var i = 0; i < vars.length; i++) {
		if (vars[i].match('^#year'))
			options['year'] = vars[i].substring(6);
		if (vars[i].match('^month'))
			options['month'] = vars[i].substring(6);
		if (vars[i].match('^day'))
			options['date'] = vars[i].substring(4);
		if (vars[i].match('^view'))
			options['defaultView'] = vars[i].substring(5);
	}

	// Listening for hash/url changes
	jQuery(window).bind('hashchange', function() {
		var today = new Date();
		var tmpYear = today.getFullYear();
		var tmpMonth = today.getMonth() + 1;
		var tmpDay = today.getDate();
		var tmpView = options['defaultView'];
		var vars = window.location.hash.replace(/&amp;/gi, '&').split('&');
		for (var i = 0; i < vars.length; i++) {
			if (vars[i].match('^#year'))
				tmpYear = vars[i].substring(6);
			if (vars[i].match('^month'))
				tmpMonth = vars[i].substring(6) - 1;
			if (vars[i].match('^day'))
				tmpDay = vars[i].substring(4);
			if (vars[i].match('^view'))
				tmpView = vars[i].substring(5);
		}
		var date = new Date(tmpYear, tmpMonth, tmpDay, 0, 0, 0);
		var d = calendar.fullCalendar('getDate');
		var view = calendar.fullCalendar('getView');
		if (date.getYear() != d.year() || date.month() != d.month() || date.date() != d.date())
			calendar.fullCalendar('gotoDate', date);
		if (view.name != tmpView)
			calendar.fullCalendar('changeView', tmpView);
	});";
}

$calCode .= "
	options['defaultDate'] = moment(options['year'] + '-' + pad(parseInt(options['month']), 2) + '-' + pad(options['date'], 2));

	createDPCalendar(calendar, options);

	// Toggle the list of calendars
	var root = calendar.parent();
	root.find('.dp-calendar-toggle').bind('click', function(e) {
		root.find('.dp-calendar-list').slideToggle('slow', function() {
			if (!root.find('.dp-calendar-list').is(':visible')) {
				root.find('i[data-direction=\"up\"]').hide();
				root.find('i[data-direction=\"down\"]').show();
			} else {
				root.find('i[data-direction=\"up\"]').show();
				root.find('i[data-direction=\"down\"]').hide();
			}
		});
	});
	" . ($params->get('show_selection', 1) == 1 ? "jQuery('#dp-calendar-list').hide()" : "") . "

	jQuery.each(options['eventSources'], function(index, value) {
		jQuery('#dp-calendar-list input').each(function(indexInput) {
			var input = jQuery(this);
			if (value.url == input.val()) {
				input.attr('checked', true);
			}
		});
	});
});";

echo $calCode;
