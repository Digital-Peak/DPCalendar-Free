<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Fullcalendar;

class JHtmlDateTime
{

	public static function render($dateValue, $id, $name, $options = array())
	{
		JHtml::_('script', 'com_dpcalendar/moment/moment.min.js', ['relative' => true], ['defer' => true]);
		JHtml::_('script', 'com_dpcalendar/pikaday/pikaday.min.js', ['relative' => true], ['defer' => true]);
		JHtml::_('stylesheet', 'com_dpcalendar/pikaday/pikaday.min.css', ['relative' => true]);
		JHtml::_('stylesheet', 'com_dpcalendar/pikaday/custom.css', ['relative' => true]);

		JHtml::_('script', 'com_dpcalendar/jquery/timepicker/jquery.timepicker.min.js', ['relative' => true], ['defer' => true]);
		JHtml::_('stylesheet', 'com_dpcalendar/jquery/timepicker/jquery.timepicker.css', ['relative' => true]);

		$dateFormat = DPCalendarHelper::getComponentParameter('event_date_format', 'm.d.Y');
		if (isset($options['dateFormat']) && !empty($options['dateFormat'])) {
			$dateFormat = $options['dateFormat'];
		}

		$timeFormat = DPCalendarHelper::getComponentParameter('event_time_format', 'g:i a');
		if (isset($options['timeFormat']) && !empty($options['timeFormat'])) {
			$timeFormat = $options['timeFormat'];
		}

		$timeClass = '';
		if (isset($options['class']) && !empty($options['class'])) {
			$timeClass = $options['class'];
		}
		if (isset($options['timeclass']) && !empty($options['timeclass'])) {
			$timeClass = $options['timeclass'];
		}

		if (!isset($options['allDay'])) {
			$options['allDay'] = false;
		}

		if (!isset($options['datepair'])) {
			$options['datepair'] = '';
		}
		if (!isset($options['firstDay'])) {
			$options['firstDay'] = DPCalendarHelper::getComponentParameter('weekstart', '0');
		}

		// Handle the special case for "now".
		$date = null;
		if (strtoupper($dateValue) == 'NOW') {
			$date = DPCalendarHelper::getDate();
			$date->setTime($date->format('H', true), 0, 0);
		} else if (strtoupper($dateValue) == '+1 HOUR' || strtoupper($dateValue) == '+2 MONTH') {
			$date = DPCalendarHelper::getDate();
			$date->setTime($date->format('H', true), 0, 0);
			$date->modify($dateValue);
		} else if ($dateValue && isset($options['formated']) && !empty($options['formated'])) {
			$date = DPCalendarHelper::getDateFromString($dateValue, null, $options['allDay'], $dateFormat, $timeFormat);
		} else if ($dateValue) {
			$date = DPCalendarHelper::getDate($dateValue, $options['allDay']);
		}

		// Transform the date string.
		$dateString = $date ? $date->format($dateFormat, true) : '';
		$timeString = $date ? $date->format($timeFormat, true) : '';
		if ($date && $options['allDay']) {
			$dateString = $date->format($dateFormat, false);
			$timeString = $date->format($timeFormat, false);
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

		$calCode = "document.addEventListener('DOMContentLoaded', function () {
		var picker = new Pikaday({
			field: document.getElementById('" . $id . "'),
			numberOfMonths: 1,
			firstDay: " . $options['firstDay'] . ",
			format: '" . Fullcalendar::convertFromPHPDate($dateFormat) . "',
			" . ($date ? "defaultDate: new Date('" . $date->format('Y-m-d') . "')," : '') . "
			i18n: {
				months: " . json_encode($datePickerOptions['monthNames']) . ",
				weekdays: " . json_encode($datePickerOptions['dayNames']) . ",
				weekdaysShort: " . json_encode($datePickerOptions['dayNamesShort']) . "
			},
			onSelect: function () {
				var end = document.getElementById('jform_" . $options['datepair'] . "');
				if(!end || !this.actualDate) {
					return;
				}
				var field = document.getElementById('" . $id . "');
				var format = '" . Fullcalendar::convertFromPHPDate($dateFormat) . "';
				var diff = moment.utc(field.value, format).diff(moment.utc(this.actualDate, format));
				var date = moment.utc(end.value, format);
				date.add(diff, 'ms');
				end.value = date.format(format);
				picker.actualDate = field.value;
			}
		});
		picker.actualDate = document.getElementById('" . $id . "').value;
		";

		$timePickerOptions               = array();
		$timePickerOptions['timeFormat'] = $timeFormat;
		$timePickerOptions['step']       = DPCalendarHelper::getComponentParameter('event_form_time_step', 30);

		if (!empty($options['minTime'])) {
			$timePickerOptions['minTime'] = $options['minTime'];

			$minDate = clone $date;
			$minTime = explode(':', $options['minTime']);
			$minDate->setTime($minTime[0], $minTime[1]);

			if ($date < $minDate) {
				$date->setTime($minTime[0], $minTime[1]);
			}
		}
		if (!empty($options['maxTime'])) {
			$timePickerOptions['maxTime'] = $options['maxTime'];

			$maxDate = clone $date;
			$maxTime = explode(':', $options['maxTime']);
			$maxDate->setTime($maxTime[0], $maxTime[1]);

			if ($date > $maxDate) {
				$date->setTime($maxTime[0], $maxTime[1]);
			}
		}

		$calCode .= "	jQuery('#" . $id . "_time').timepicker(" . json_encode($timePickerOptions) . ");\n";

		if (isset($options['timepair'])) {
			$calCode .= "	jQuery('." . $options['timepair'] . "').datepair({'startClass': 'timestart', 'endClass': 'timeend', 'setMinTime': null});\n";
		}
		$calCode .= "});\n";
		JFactory::getDocument()->addScriptDeclaration($calCode);

		$onchange = isset($options['onchange']) && !empty($options['onchange']) ? ' onchange="' . $options['onchange'] . '"' : '';

		if (!isset($options['class']) || empty($options['class'])) {
			$options['class'] = 'input-small';
		}

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
		$buffer .= '<input type="' . $type . '" class="' . $options['class'] . '" value="' . $dateString . '" name="' . $name . '" id="' . $id .
			'" size="15" maxlength="10" ' . $onchange . ' format="' . Fullcalendar::convertFromPHPDate($dateFormat) . '"/>';
		$buffer .= '&nbsp;<input type="text" class="time ' . $timeClass . '" value="' . $timeString . '" size="8" name="' . $timeName . '" id="' . $id .
			'_time" ' . ($options['allDay'] == '1' ? 'style="display:none"' : '') . '/>';
		if (isset($options['button']) && $options['button']) {
			$buffer .= '<button class="btn btn-default" type="button" onclick="jQuery(\'#' . $id . '\').datepicker(\'show\');">';
			$buffer .= '<i class="icon-calendar"></i>';
			$buffer .= '</button>';
		}

		return $buffer;
	}
}
