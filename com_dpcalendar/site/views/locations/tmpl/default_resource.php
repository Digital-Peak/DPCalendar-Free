<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('locations_show_resource_view', 1) || \DPCalendar\Helper\DPCalendarHelper::isFree()) {
	return;
}

$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_YEAR');

$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT');

$this->translator->translateJS('JCANCEL');
$this->translator->translateJS('JLIB_HTML_BEHAVIOR_CLOSE');

$this->dpdocument->addScriptOptions('calendar.names', $this->dateHelper->getNames());
$this->dpdocument->addScriptOptions('timezone', $this->dateHelper->getDate()->getTimezone()->getName());
$this->dpdocument->addScriptOptions('itemid', $this->input->getInt('Itemid', 0));

$options                   = [];
$options['requestUrlRoot'] = 'view=events&format=raw&limit=0&l=1&Itemid=' . $this->input->getInt('Itemid', 0);
$options['calendarIds']    = $this->ids;

$options['defaultView'] = $this->params->get('locations_default_view', 'resday');
// Set up the header
$options['header']           = ['left' => [], 'center' => [], 'right' => []];
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
$options['header']['right'][] = 'resday';
$options['header']['right'][] = 'resweek';
$options['header']['right'][] = 'resmonth';
$options['header']['right'][] = 'resyear';

$options['header']['left']   = implode(',', $options['header']['left']);
$options['header']['center'] = implode(',', $options['header']['center']);
$options['header']['right']  = implode(',', $options['header']['right']);

$options['height']          = 'auto';
$options['slotLabelFormat'] = $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_axisformat', 'g:i a'));
$options['slotWidth']       = $this->params->get('locations_column_width');
$options['smallTimeFormat'] = $this->dateHelper->convertPHPDateToMoment($this->params->get('timeformat_day', 'g:i a'));

$options['resources']         = $this->resources;
$options['resourceLabelText'] = $this->translate('COM_DPCALENDAR_VIEW_LOCATIONS_LOCATIONS_AND_ROOMS');

$options['views']             = [];
$options['views']['resyear']  = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_titleformat_year', 'Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_timeformat_year', 'g:i a'))
];
$options['views']['resmonth'] = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_titleformat_month', 'F Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_timeformat_month', 'g:i a'))
];
$options['views']['resweek']  = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_titleformat_week', 'M j Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_timeformat_week', 'g:i a'))
];
$options['views']['resday']   = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_titleformat_day', 'F j Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToMoment($this->params->get('locations_timeformat_day', 'g:i a'))
];

$max = $this->params->get('locations_max_time', 24);
if (is_numeric($max)) {
	$max = $max . ':00:00';
}
$options['maxTime'] = $max;

$min = $this->params->get('locations_min_time', 0);
if (is_numeric($min)) {
	$min = $min . ':00:00';
}
$options['minTime'] = $min;

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

$this->dpdocument->addScriptOptions('view.locations.' . $this->input->getInt('Itemid', 0) . '.options', $options);
?>
<div class="com-dpcalendar-locations__resource dp-calendar"
	 data-options="DPCalendar.view.locations.<?php echo $this->input->getInt('Itemid', 0); ?>.options"></div>
