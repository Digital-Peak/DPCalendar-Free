<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class JHtmlDateTime
{

	public static function render($dateValue, $id, $name, $options = array())
	{
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

		$daysLong    = "[";
		$daysShort   = "[";
		$daysMin     = "[";
		$monthsLong  = "[";
		$monthsShort = "[";
		for ($i = 0; $i < 7; $i++) {
			$daysLong  .= "'" . htmlspecialchars(DPCalendarHelper::dayToString($i, false), ENT_QUOTES) . "'";
			$daysShort .= "'" . htmlspecialchars(DPCalendarHelper::dayToString($i, true), ENT_QUOTES) . "'";
			$daysMin   .= "'" . htmlspecialchars(mb_substr(DPCalendarHelper::dayToString($i, true), 0, 2), ENT_QUOTES) . "'";
			if ($i < 6) {
				$daysLong  .= ",";
				$daysShort .= ",";
				$daysMin   .= ",";
			}
		}
		for ($i = 1; $i <= 12; $i++) {
			$monthsLong  .= "'" . htmlspecialchars(DPCalendarHelper::monthToString($i, false), ENT_QUOTES) . "'";
			$monthsShort .= "'" . htmlspecialchars(DPCalendarHelper::monthToString($i, true), ENT_QUOTES) . "'";
			if ($i < 12) {
				$monthsLong  .= ",";
				$monthsShort .= ",";
			}
		}
		$daysLong    .= "]";
		$daysShort   .= "]";
		$daysMin     .= "]";
		$monthsLong  .= "]";
		$monthsShort .= "]";

		$calCode = "jQuery(document).ready(function(){\n";
		$calCode .= "	jQuery('#" . $id . "').datepicker({\n";
		$calCode .= "		dateFormat: '" . self::dateStringToDatepickerFormat($dateFormat) . "',\n";
		$calCode .= "		changeYear: true, \n";
		$calCode .= "		dayNames: " . $daysLong . ",\n";
		$calCode .= "		dayNamesShort: " . $daysShort . ",\n";
		$calCode .= "		dayNamesMin: " . $daysMin . ",\n";
		$calCode .= "		monthNames: " . $monthsLong . ",\n";
		$calCode .= "		monthNamesShort: " . $monthsShort . ",\n";
		$calCode .= "		firstDay: " . DPCalendarHelper::getComponentParameter('weekstart', 0) . ",\n";
		if (isset($options['datepair'])) {
			$calCode .= "		onSelect: function(date, object){
					var diff = jQuery('#" . $id . "').datepicker('getDate') - jQuery('#" . $id . "').data('actualDate');
					diff = diff / 1000 / 60 / 60 / 24;
					var date = new Date(jQuery('.end').datepicker('getDate'));
					date.setDate(date.getDate() + diff);
					jQuery('.end').datepicker('setDate', date);
					jQuery('#" . $id . "').data('actualDate', jQuery('#" . $id . "').datepicker('getDate'));
		}\n";
		}
		$calCode .= "	});\n";

		$calCode .= "	jQuery('#" . $id . "').data('actualDate', jQuery('#" . $id . "').datepicker('getDate'));\n";

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

		// Transform the date string.
		$dateString = $date ? $date->format($dateFormat, true) : '';
		$timeString = $date ? $date->format($timeFormat, true) : '';
		if ($date && $options['allDay']) {
			$dateString = $date->format($dateFormat, false);
			$timeString = $date->format($timeFormat, false);
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
			'" size="15" maxlength="10" ' . $onchange . '/>';
		$buffer .= '&nbsp;<input type="text" class="time ' . $timeClass . '" value="' . $timeString . '" size="8" name="' . $timeName . '" id="' . $id .
			'_time" ' . ($options['allDay'] == '1' ? 'style="display:none"' : '') . '/>';
		if (isset($options['button']) && $options['button']) {
			$buffer .= '<button class="btn btn-default" type="button" onclick="jQuery(\'#' . $id . '\').datepicker(\'show\');">';
			$buffer .= '<i class="icon-calendar"></i>';
			$buffer .= '</button>';
		}

		return $buffer;
	}

	public static function dateStringToDatepickerFormat($dateString)
	{
		$pattern = array(
			'd',
			'j',
			'l',
			'z',
			'F',
			'M',
			'n',
			'm',
			'Y',
			'y'
		);
		$replace = array(
			'dd',
			'd',
			'DD',
			'o',
			'MM',
			'M',
			'm',
			'mm',
			'yy',
			'y'
		);
		foreach ($pattern as &$p) {
			$p = '/' . $p . '/';
		}

		return preg_replace($pattern, $replace, $dateString);
	}
}
