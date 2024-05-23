<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

defined('_JEXEC') or die();

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class DpradioField extends FormField
{
	protected $type = 'DPRadio';

	protected function getInput(): string
	{
		// Initialize variables.
		$html = [];

		// Initialize some field attributes.
		$class = $this->element['class'] !== null ? ' class="radio ' . $this->element['class'] . '"' : ' class="radio"';

		// Start the radio field output.
		$html[] = '<fieldset id="' . $this->id . '"' . $class . '>';

		// Get the field options.
		$options = $this->getOptions();

		// Build the radio field output.
		foreach ($options as $i => $option) {
			// Initialize some option attributes.
			$checked  = ((string)$option->value === (string)$this->value) ? ' checked="checked"' : '';
			$class    = empty($option->class) ? '' : ' class="' . $option->class . '"';
			$disabled = !empty($option->disable) || !empty($this->element['disabled']) ? ' disabled="disabled"' : '';

			// Initialize some JavaScript option attributes.
			$onclick = empty($option->onclick) ? '' : ' onclick="' . $option->onclick . '"';

			$html[] = '<input type="radio" id="' . $this->id . $i . '" name="' . $this->name . '"' . ' value="' .
				htmlspecialchars((string)$option->value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $onclick . $disabled . '/>';

			$name   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
			$html[] = '<label for="' . $this->id . $i . '"' . $class . '>' .
				Text::alt($option->text, $name !== '' && $name !== '0' && $name !== [] && $name !== null ? $name : '') . '</label>';
		}

		// End the radio field output.
		$html[] = '</fieldset>';

		return implode(PHP_EOL, $html);
	}

	/**
	 * @return mixed[]
	 */
	protected function getOptions(): array
	{
		// Initialize variables.
		$options = [];

		foreach ($this->element->children() as $option) {
			// Only add <option /> elements.
			if ($option->getName() != 'option') {
				continue;
			}

			// Create a new option object based on the <option /> element.
			$tmp = HTMLHelper::_(
				'select.option',
				(string)$option['value'],
				trim((string)$option),
				'value',
				'text',
				((string)$option['disabled'] === 'true')
			);

			// Set some option attributes.
			$tmp->class = (string)$option['class'];

			// Set some JavaScript option attributes.
			$tmp->onclick = (string)$option['onclick'];

			// Add the option object to the result set.
			$options[] = $tmp;
		}

		reset($options);

		return $options;
	}
}
