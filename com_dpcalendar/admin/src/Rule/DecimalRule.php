<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Rule;

defined('_JEXEC') or die();

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;

class DecimalRule extends FormRule
{
	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null): bool
	{
		// If the field is empty and not required, the field is valid.
		$required = ((string)$element['required'] === 'true' || (string)$element['required'] === 'required');

		if (!$required && empty($value)) {
			return true;
		}

		return is_numeric($value);
	}
}
