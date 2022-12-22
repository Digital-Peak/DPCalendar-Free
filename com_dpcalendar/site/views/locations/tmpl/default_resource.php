<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;

if (!$this->params->get('locations_show_resource_view', 1) || DPCalendarHelper::isFree()) {
	return;
}

$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_YEAR');

$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ADD');

$this->translator->translateJS('JCANCEL');
$this->translator->translateJS('COM_DPCALENDAR_CLOSE');
$this->translator->translateJS('COM_DPCALENDAR_FIELD_CAPACITY_UNLIMITED');

$this->dpdocument->addScriptOptions('calendar.names', $this->dateHelper->getNames());
$this->dpdocument->addScriptOptions('timezone', $this->dateHelper->getDate()->getTimezone()->getName());
$this->dpdocument->addScriptOptions('itemid', $this->input->getInt('Itemid', 0));

$options                   = [];
$options['requestUrlRoot'] = 'view=events&format=raw&limit=0&l=1&Itemid=' . $this->input->getInt('Itemid', 0);
$options['calendarIds']    = $this->ids;

$options['initialView'] = $this->params->get('locations_default_view', 'resday');

// Set up the header
$options['headerToolbar']           = ['left' => [], 'center' => [], 'right' => []];
$options['headerToolbar']['left'][] = 'prev';
$options['headerToolbar']['left'][] = 'next';
if ($this->params->get('locations_header_show_today', 1)) {
	$options['headerToolbar']['left'][] = 'today';
}
if ($this->params->get('locations_header_show_datepicker', 1)) {
	$options['headerToolbar']['left'][] = 'datepicker';
}
if ($this->params->get('locations_header_show_print', 1)) {
	$options['headerToolbar']['left'][] = 'print';
}
if ($this->params->get('locations_header_show_create', 1) && DPCalendarHelper::canCreateEvent()) {
	$options['headerToolbar']['left'][] = 'add';
}
if ($this->params->get('locations_header_show_title', 1)) {
	$options['headerToolbar']['center'][] = 'title';
}
$options['headerToolbar']['right'][] = 'resday';
$options['headerToolbar']['right'][] = 'resweek';
$options['headerToolbar']['right'][] = 'resmonth';
$options['headerToolbar']['right'][] = 'resyear';

$options['headerToolbar']['left']   = implode(',', $options['headerToolbar']['left']);
$options['headerToolbar']['center'] = implode(',', $options['headerToolbar']['center']);
$options['headerToolbar']['right']  = implode(',', $options['headerToolbar']['right']);

$options['height']          = 'auto';
$options['slotLabelFormat'] = $this->dateHelper->convertPHPDateToJS($this->params->get('locations_axisformat', 'H:i'));
$options['slotMinWidth']    = $this->params->get('locations_column_width');
$options['smallTimeFormat'] = $this->dateHelper->convertPHPDateToJS($this->params->get('timeformat_day', 'H:i'));

$options['resources']                 = $this->resources;
$options['resourceAreaHeaderContent'] = $this->translate('COM_DPCALENDAR_VIEW_LOCATIONS_LOCATIONS_AND_ROOMS');
$options['resourceOrder']             = 'title,id';

$options['views']            = [];
$options['views']['resyear'] = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_titleformat_year', 'Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_timeformat_year', 'H:i')),
	'slotLabelFormat' => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_axisformat_year', 'M j'))
];
$options['views']['resmonth'] = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_titleformat_month', 'F Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_timeformat_month', 'H:i')),
	'slotLabelFormat' => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_axisformat_month', 'l j'))
];
$options['views']['resweek'] = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_titleformat_week', 'M j Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_timeformat_week', 'H:i')),
	'slotLabelFormat' => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_axisformat_week', 'D j H:i'))
];
$options['views']['resday'] = [
	'titleFormat'     => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_titleformat_day', 'F j Y')),
	'eventTimeFormat' => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_timeformat_day', 'H:i')),
	'slotLabelFormat' => $this->dateHelper->convertPHPDateToJS($this->params->get('locations_axisformat_day', 'H:i'))
];

$max = $this->params->get('locations_max_time', 24);
if (is_numeric($max)) {
	$max = $max . ':00:00';
}
$options['slotMaxTime'] = $max;

$min = $this->params->get('locations_min_time', 0);
if (is_numeric($min)) {
	$min = $min . ':00:00';
}
$options['slotMinTime'] = $min;

$options['use_hash']          = true;
$options['event_create_form'] = 2;

// Set the actual date
$now              = $this->dateHelper->getDate();
$options['year']  = $now->format('Y', true);
$options['month'] = $now->format('m', true);
$options['date']  = $now->format('d', true);
if (DPCalendarHelper::canCreateEvent()) {
	$options['event_create_url'] = $this->router->getEventFormRoute(0, $this->returnPage);
}

$this->dpdocument->addScriptOptions('view.locations.' . $this->input->getInt('Itemid', 0) . '.options', $options);
?>
<div class="com-dpcalendar-locations__resource dp-calendar"
	data-options="DPCalendar.view.locations.<?php echo $this->input->getInt('Itemid', 0); ?>.options"></div>
