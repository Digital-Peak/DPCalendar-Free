<?php

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Registry\Registry;

class JFormRuleMinmaxtime extends FormRule
{
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
	{
		// If the field is empty and not required, the field is valid.
		$required = ((string)$element['required'] == 'true' || (string)$element['required'] == 'required');

		if (!$required && empty($value)) {
			return true;
		}

		// If we don't have a full set up, ignore the rule
		if (!$form instanceof Form || !$input instanceof Registry || $input->get('all_day')) {
			return true;
		}

		// Get max date
		$minDate = DPCalendarHelper::getDate($value);
		$minTime = explode(':', $form->getFieldAttribute((string)$element['name'], 'min_time', '00:00'));
		$minDate->setTime($minTime[0], $minTime[1]);

		// Get the min date
		$maxDate = DPCalendarHelper::getDate($value);
		$maxTime = explode(':', $form->getFieldAttribute((string)$element['name'], 'max_time', '24:00'));
		$maxDate->setTime($maxTime[0], $maxTime[1]);

		// The date of the value
		$date = DPCalendarHelper::getDate($value);

		// Check if the date is between
		return $date >= $minDate && $date <= $maxDate;
	}
}
