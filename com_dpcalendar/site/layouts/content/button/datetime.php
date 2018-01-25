<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\Form\Input;
use DPCalendar\Helper\Fullcalendar;

// Global variables
$root = $displayData['root'];
$id   = $displayData['id'];
$name = $displayData['name'];
$date = isset($displayData['date']) ? $displayData['date'] : null;

JHtml::_('script', 'com_dpcalendar/moment/moment.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('script', 'com_dpcalendar/pikaday/pikaday.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('stylesheet', 'com_dpcalendar/pikaday/pikaday.min.css', ['relative' => true]);
JHtml::_('stylesheet', 'com_dpcalendar/pikaday/custom.css', ['relative' => true]);

$dateFormat = DPCalendarHelper::getComponentParameter('event_date_format', 'm.d.Y');
if (isset($displayData['dateFormat']) && !empty($displayData['dateFormat'])) {
	$dateFormat = $displayData['dateFormat'];
}

// Set up the month and day names
$datePickerOptions                  = array();
$datePickerOptions['monthNames']    = array();
$datePickerOptions['dayNames']      = array();
$datePickerOptions['dayNamesShort'] = array();
for ($i = 0; $i < 7; $i++) {
	$datePickerOptions['dayNames'][]      = DPCalendarHelper::dayToString($i, false);
	$datePickerOptions['dayNamesShort'][] = DPCalendarHelper::dayToString($i, true);
}
for ($i = 1; $i <= 12; $i++) {
	$datePickerOptions['monthNames'][] = DPCalendarHelper::monthToString($i, false);
}

$type = 'text';
if (isset($displayData['button']) && $displayData['button']) {
	$type = 'hidden';
}

$i = $root->addChild(
	new Input(
		$id,
		$type,
		$name,
		$date ? $date->format($dateFormat, true) : '',
		array(),
		array(
			'onchange'    => !empty($displayData['onchange']) ? $displayData['onchange'] : '',
			'placeholder' => !empty($displayData['placeholder']) ? $displayData['placeholder'] : '',
			'title'       => !empty($displayData['title']) ? $displayData['title'] : ''
		)
	)
);

if (isset($displayData['button']) && $displayData['button']) {
	// Render the basic button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'id'   => $id . '-button',
			'type' => Icon::CALENDAR,
			'root' => $root
		)
	);
}

$calCode = "document.addEventListener('DOMContentLoaded', function () {
	new Pikaday({
		format: '" . Fullcalendar::convertFromPHPDate($dateFormat) . "',
		field: document.getElementById('" . $i->getId() . "'),
		trigger: document.getElementById('" . $i->getId() . "-button'),
		" . ($date ? "defaultDate: new Date('" . $date->format('Y-m-d', false) . "')," : '') . "
		i18n: {
			months: " . json_encode($datePickerOptions['monthNames']) . ",
			weekdays: " . json_encode($datePickerOptions['dayNames']) . ",
			weekdaysShort: " . json_encode($datePickerOptions['dayNamesShort']) . "
		}
	});
})";

JFactory::getDocument()->addScriptDeclaration($calCode);
