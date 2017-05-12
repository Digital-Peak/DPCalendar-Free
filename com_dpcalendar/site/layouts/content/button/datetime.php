<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Button;
use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\Form\Input;

// Global variables
$root      = $displayData['root'];
$id        = $displayData['id'];
$name      = $displayData['name'];
$dateValue = $displayData['date'];
$options   = isset($displayData['options']) ? $displayData['options'] : array();

DPCalendarHelper::loadLibrary(array('jquery' => true, 'datepicker' => true));

JHtml::_('script', 'com_dpcalendar/jquery/timepicker/jquery.timepicker.min.js', false, true);
JHtml::_('stylesheet', 'com_dpcalendar/jquery/timepicker/jquery.timepicker.css', array(), true);

$dateFormat = DPCalendarHelper::getComponentParameter('event_date_format', 'm.d.Y');
if (isset($options['dateFormat']) && !empty($options['dateFormat'])) {
	$dateFormat = $options['dateFormat'];
}

$timeFormat = DPCalendarHelper::getComponentParameter('event_time_format', 'g:i a');
if (isset($options['timeFormat']) && !empty($options['timeFormat'])) {
	$timeFormat = $options['timeFormat'];
}

if (!isset($options['allDay'])) {
	$options['allDay'] = false;
}

// Handle the special case for "now".
$date = null;
if (strtoupper($dateValue) == 'NOW') {
	$date = DPCalendarHelper::getDate();
	$date->setTime($date->format('H', true), 0, 0);
} else {
	if (strtoupper($dateValue) == '+1 HOUR' || strtoupper($dateValue) == '+2 MONTH') {
		$date = DPCalendarHelper::getDate();
		$date->setTime($date->format('H', true), 0, 0);
		$date->modify($dateValue);
	} else {
		if ($dateValue && isset($options['formated']) && !empty($options['formated'])) {
			$date = DPCalendarHelper::getDateFromString($dateValue, null, $options['allDay'], $dateFormat, $timeFormat);
		} else {
			if ($dateValue) {
				$date = DPCalendarHelper::getDate($dateValue, $options['allDay']);
			}
		}
	}
}

// Transform the date string.
$dateString = $date ? $date->format($dateFormat, true) : '';
$timeString = $date ? $date->format($timeFormat, true) : '';
if ($date && $options['allDay']) {
	$dateString = $date->format($dateFormat, false);
	$timeString = $date->format($timeFormat, false);
}

$daysLong    = "[";
$daysShort   = "[";
$daysMin     = "[";
$monthsLong  = "[";
$monthsShort = "[";
for ($i = 0; $i < 7; $i++) {
	$daysLong .= "'" . htmlspecialchars(DPCalendarHelper::dayToString($i, false), ENT_QUOTES) . "'";
	$daysShort .= "'" . htmlspecialchars(DPCalendarHelper::dayToString($i, true), ENT_QUOTES) . "'";
	$daysMin .= "'" . htmlspecialchars(mb_substr(DPCalendarHelper::dayToString($i, true), 0, 2), ENT_QUOTES) . "'";
	if ($i < 6) {
		$daysLong .= ",";
		$daysShort .= ",";
		$daysMin .= ",";
	}
}
for ($i = 1; $i <= 12; $i++) {
	$monthsLong .= "'" . htmlspecialchars(DPCalendarHelper::monthToString($i, false), ENT_QUOTES) . "'";
	$monthsShort .= "'" . htmlspecialchars(DPCalendarHelper::monthToString($i, true), ENT_QUOTES) . "'";
	if ($i < 12) {
		$monthsLong .= ",";
		$monthsShort .= ",";
	}
}
$daysLong .= "]";
$daysShort .= "]";
$daysMin .= "]";
$monthsLong .= "]";
$monthsShort .= "]";

$onchange = !empty($options['onchange']) ? ' onchange="' . $options['onchange'] . '"' : '';

$buffer = '';

$type = 'text';
if (isset($options['button']) && $options['button']) {
	$type = 'hidden';
}

$timeName = $name;
if (strpos($timeName, ']') !== false) {
	$timeName = str_replace(']', '_time]', $name);
} else {
	$timeName .= '_time';
}

$i = $root->addChild(
	new Input(
		$id,
		$type,
		$name,
		$dateString,
		array(),
		array('onchange' => !empty($options['onchange']) ? $options['onchange'] : '')
	)
);
$root->addChild(
	new Input(
		$id . '-time',
		'text',
		$timeName,
		$timeString,
		array(),
		array('style' => $options['allDay'] == '1' ? 'display:none' : '')
	)
);

if (isset($options['button']) && $options['button']) {
// Render the basic button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'id'      => $id . '-button',
			'type'    => Icon::CALENDAR,
			'root'    => $root,
			'onclick' => "jQuery('input[name=" . $name . "]').datepicker('show'); return false;"
		)
	);
}

$calCode = "jQuery(document).ready(function(){\n";
$calCode .= "	jQuery('#" . $i->getId() . "').datepicker({\n";
$calCode .= "		dateFormat: '" . DPCalendarHelper::dateStringToDatepickerFormat($dateFormat) . "',\n";
$calCode .= "		changeYear: true, \n";
$calCode .= "		dayNames: " . $daysLong . ",\n";
$calCode .= "		dayNamesShort: " . $daysShort . ",\n";
$calCode .= "		dayNamesMin: " . $daysMin . ",\n";
$calCode .= "		monthNames: " . $monthsLong . ",\n";
$calCode .= "		monthNamesShort: " . $monthsShort . ",\n";
$calCode .= "		firstDay: " . DPCalendarHelper::getComponentParameter('weekstart', 0) . ",\n";
if (isset($options['timepair'])) {
	$calCode .= "		onSelect: function(date, object){
			var diff = jQuery('#" . $i->getId() . "').datepicker('getDate') - jQuery('#" . $i->getId() . "').data('actualDate');
			diff = diff / 1000 / 60 / 60 / 24;
			var date = new Date(jQuery('.end').datepicker('getDate'));
			date.setDate(date.getDate() + diff);
			jQuery('.end').datepicker('setDate', date);
			jQuery('#" . $i->getId() . "').data('actualDate', jQuery('#" . $id . "').datepicker('getDate'));
		}\n";
}
$calCode .= "	});\n";

$calCode .= "	jQuery('#" . $i->getId() . "').data('actualDate', jQuery('#" . $i->getId() . "').datepicker('getDate'));\n";
$calCode .= "	jQuery('#" . $i->getId() . "-time').timepicker({'timeFormat': '" . $timeFormat . "', 'step': " . DPCalendarHelper::getComponentParameter('event_form_time_step',
		30) . "});\n";

if (isset($options['timepair'])) {
	$calCode .= "	jQuery('." . $options['timepair'] . "').datepair({'startClass': 'timestart', 'endClass': 'timeend', 'setMinTime': null});\n";
}
$calCode .= "});\n";
JFactory::getDocument()->addScriptDeclaration($calCode);
