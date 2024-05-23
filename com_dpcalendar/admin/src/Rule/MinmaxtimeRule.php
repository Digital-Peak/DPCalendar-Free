<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Rule;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;

class MinmaxtimeRule extends FormRule
{
	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null): bool
	{
		// If the field is empty and not required, the field is valid.
		$required = ((string)$element['required'] === 'true' || (string)$element['required'] === 'required');

		if (!$required && empty($value)) {
			return true;
		}

		// If we don't have a full set up, ignore the rule
		if (!$form instanceof Form || !$input instanceof Registry || $input->get('all_day')) {
			return true;
		}

		// Get max date
		$minDate = DPCalendarHelper::getDate($value);
		$minTime = explode(':', (string)$form->getFieldAttribute((string)$element['name'], 'min_time', '00:00'));
		$minDate->setTime((int)$minTime[0], (int)$minTime[1]);

		// Get the min date
		$maxDate = DPCalendarHelper::getDate($value);
		$maxTime = explode(':', (string)$form->getFieldAttribute((string)$element['name'], 'max_time', '24:00'));
		$maxDate->setTime((int)$maxTime[0], (int)$maxTime[1]);

		// The date of the value
		$date = DPCalendarHelper::getDate($value);

		// Check if the date is between
		return $date >= $minDate && $date <= $maxDate;
	}
}
