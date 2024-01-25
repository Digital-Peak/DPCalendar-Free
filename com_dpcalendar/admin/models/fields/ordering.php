<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace Joomla\CMS\Form\Field;

defined('_JEXEC') or die();

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

class OrderingField extends FormField
{
	public $element;
	public $form;
	protected $type = 'Ordering';

	protected function getInput()
	{
		$html = [];
		$attr = '';

		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="' . $this->element['class'] . '"' : '';
		$attr .= ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . $this->element['onchange'] . '"' : '';

		// Get some field values from the form.
		$locationId = (int)$this->form->getValue('id');

		// Build the query for the ordering list.
		$query = 'SELECT ordering AS value, title AS text FROM #__dpcalendar_locations ORDER BY ordering';

		// Create a read-only list (no name) with a hidden input to store the
		// value.
		if ((string)$this->element['readonly'] == 'true') {
			$html[] = HTMLHelper::_('list.ordering', '', $query, \trim($attr), $this->value, $locationId !== 0 ? 0 : 1);
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
		} else {
			// Create a regular list.
			$html[] = HTMLHelper::_('list.ordering', $this->name, $query, \trim($attr), $this->value, $locationId !== 0 ? 0 : 1);
		}

		return \implode(\PHP_EOL, $html);
	}
}
