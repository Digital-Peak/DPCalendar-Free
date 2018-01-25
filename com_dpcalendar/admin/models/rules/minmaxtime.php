<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Registry\Registry;

class JFormRuleMinmaxtime extends JFormRule
{
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
	{
		// If the field is empty and not required, the field is valid.
		$required = ((string)$element['required'] == 'true' || (string)$element['required'] == 'required');

		if (!$required && empty($value)) {
			return true;
		}

		// If we don't have a full set up, ignore the rule
		if (!$form || !$input || $input->get('all_day')) {
			return true;
		}

		// Get max date
		$minDate = \DPCalendar\Helper\DPCalendarHelper::getDate($value);
		$minTime = explode(':', $form->getFieldAttribute((string)$element['name'], 'min_time', '00:00'));
		$minDate->setTime($minTime[0], $minTime[1]);

		// Get the min date
		$maxDate = \DPCalendar\Helper\DPCalendarHelper::getDate($value);
		$maxTime = explode(':', $form->getFieldAttribute((string)$element['name'], 'max_time', '24:00'));
		$maxDate->setTime($maxTime[0], $maxTime[1]);

		// The date of the value
		$date = \DPCalendar\Helper\DPCalendarHelper::getDate($value);

		// Check if the date is between
		return $date >= $minDate && $date <= $maxDate;
	}
}
