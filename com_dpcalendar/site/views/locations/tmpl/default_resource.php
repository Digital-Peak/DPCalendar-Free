<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('locations_show_resource_view', 1) || \DPCalendar\Helper\DPCalendarHelper::isFree()) {
	return;
}

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FULLCALENDAR);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_SCHEDULER);
if ($this->params->get('locations_header_show_datepicker', 1)) {
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DATEPICKER);
}

$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_YEAR');

$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT');

$this->translator->translateJS('JCANCEL');
$this->translator->translateJS('JLIB_HTML_BEHAVIOR_CLOSE');

$options                 = array();
$options['eventSources'] = array();
foreach ($this->ids as $calendar) {
	$options['eventSources'][] = html_entity_decode(
		JRoute::_(
			'index.php?option=com_dpcalendar&view=events&format=raw&limit=0&l=1' .
			'&ids=' . $calendar .
			'&Itemid=' . $this->input->getInt('Itemid', 0)
		)
	);
}

// Translate to the fullcalendar view names
$mapping                = [
	'resday'   => 'timelineDay',
	'resweek'  => 'timelineWeek',
	'resmonth' => 'timelineMonth',
	'resyear'  => 'timelineYear'
];
$options['defaultView'] = $this->params->get('locations_default_view', 'resday');
if (key_exists($this->params->get('locations_default_view', 'resday'), $mapping)) {
	$options['defaultView'] = $mapping[$this->params->get('locations_default_view', 'resday')];
}
// Set up the header
$options['header']           = array('left' => array(), 'center' => array(), 'right' => array());
$options['header']['left'][] = 'prev';
$options['header']['left'][] = 'next';
if ($this->params->get('locations_header_show_datepicker', 1)) {
	$options['header']['left'][] = 'datepicker';
}
if ($this->params->get('locations_header_show_print', 1)) {
	$options['header']['left'][] = 'print';
}
if ($this->params->get('locations_header_show_create', 1)) {
	$options['header']['left'][] = 'add';
}
if ($this->params->get('locations_header_show_title', 1)) {
	$options['header']['center'][] = 'title';
}
$options['header']['right'][] = 'timelineDay';
$options['header']['right'][] = 'timelineWeek';
$options['header']['right'][] = 'timelineMonth';
$options['header']['right'][] = 'timelineYear';

$options['header']['left']   = implode(',', $options['header']['left']);
$options['header']['center'] = implode(',', $options['header']['center']);
$options['header']['right']  = implode(',', $options['header']['right']);

$options['height']          = 'auto';
$options['slotLabelFormat'] = null;
$options['slotWidth']       = $this->params->get('locations_column_width');
$options['smallTimeFormat'] = $this->dateHelper->convertPHPDateToMoment($this->params->get('timeformat_day', 'g:i a'));

$options['resources']         = $this->resources;
$options['resourceLabelText'] = $this->translate('COM_DPCALENDAR_LAYOUT_CALENDAR_LOCATIONS_AND_ROOMS');

$options['views']                  = array();
$options['views']['timelineYear']  = array(
	'timeFormat' => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_timeformat_year', 'g:i a'))
);
$options['views']['timelineMonth'] = array(
	'timeFormat' => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_timeformat_month', 'g:i a'))
);
$options['views']['timelineWeek']  = array(
	'timeFormat' => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_timeformat_week', 'g:i a'))
);
$options['views']['timelineDay']   = array(
	'timeFormat' => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_timeformat_day', 'g:i a'))
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

$options['use_hash']          = true;
$options['event_create_form'] = 2;

// Set the actual date
$now              = $this->dateHelper->getDate();
$options['year']  = $now->format('Y', true);
$options['month'] = $now->format('m', true);
$options['date']  = $now->format('d', true);
if (\DPCalendar\Helper\DPCalendarHelper::canCreateEvent()) {
	$options['event_create_url'] = $this->router->getEventFormRoute(0, $this->return);
}

$this->dpdocument->addScriptOptions('view.locations.options', $options);
?>
<div class="com-dpcalendar-locations__resource">
	<div class="dp-calendar" data-options="DPCalendar.view.locations.options"></div>
</div>
