<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

class JFormFieldBooking extends FormField
{
	public $id;
	public $class;
	public $size;
	public $required;
	public $value;
	public $type     = 'Booking';
	public $readonly = false;

	protected function getInput()
	{
		if (DPCalendarHelper::isJoomlaVersion('4', '>=')) {
			return false;
		}

		$html     = [];
		$groups   = $this->getGroups();
		$excluded = $this->getExcluded();
		$link     = 'index.php?option=com_dpcalendar&amp;view=bookings&amp;layout=modal&amp;tmpl=component&amp;field=' . $this->id .
			(isset($groups) ? ('&amp;groups=' . base64_encode(json_encode($groups))) : '') .
			(isset($excluded) ? ('&amp;excluded=' . base64_encode(json_encode($excluded))) : '');

		// Initialize some field attributes.
		$attr = empty($this->class) ? '' : ' class="' . $this->class . '"';
		$attr .= empty($this->size) ? '' : ' size="' . $this->size . '"';
		$attr .= $this->required ? ' required' : '';

		// Load the modal behavior script.
		HTMLHelper::_('behavior.modal', 'a.modal_' . $this->id);

		// Build the script.
		$script   = [];
		$script[] = '	function jSelectUser_' . $this->id . '(id, title, event_id) {';
		$script[] = '		var old_id = document.getElementById("' . $this->id . '_id").value;';
		$script[] = '		if (old_id != id) {';
		$script[] = '			document.getElementById("' . $this->id . '_id").value = id;';
		$script[] = '			document.getElementById("form_event_id_id").value = event_id;';
		$script[] = '			document.getElementById("' . $this->id . '").value = title;';
		$script[] = '			document.getElementById("' . $this->id . '").className = document.getElementById("' . $this->id .
			'").className.replace(" invalid" , "");';
		$script[] = '			' . $this->onchange;
		$script[] = '		}';
		$script[] = '		SqueezeBox.close();';
		$script[] = '	}';

		// Add the script to the document head.
		Factory::getDocument()->addScriptDeclaration(implode("\n", $script));

		// Load the current Booking if available.
		$table = Table::getInstance('Booking', 'DPCalendarTable');

		if (is_numeric($this->value)) {
			$table->load($this->value);
		} else {
			$table->username = Text::_('JLIB_FORM_SELECT_USER');
		}

		// Create a dummy text field with the booking name.
		$html[] = '<div class="input-append">';
		$html[] = '	<input type="text" id="' . $this->id . '" value="' . htmlspecialchars($table->name, ENT_COMPAT, 'UTF-8') . '"' . ' readonly' .
			$attr . ' />';

		// Create the user select button.
		if ($this->readonly === false) {
			$html[] = '		<a class="btn btn-primary modal_' . $this->id . '" title="' . Text::_('JLIB_FORM_CHANGE_USER') . '" href="' . $link . '"' .
				' rel="{handler: \'iframe\', size: {x: 800, y: 500}}">';
			$html[] = '<i class="icon-user"></i></a>';
		}

		$html[] = '</div>';

		// Create the real field, hidden, that stored the booking id.
		$html[] = '<input type="hidden" id="' . $this->id . '_id" name="' . $this->name . '" value="' . (int)$this->value . '" />';

		// Create the real field, hidden, that stored the event id.
		$html[] = '<input type="hidden" id="form_event_id_id" name="jform[event_id]" value="' . (int)$this->value . '" />';

		return implode("\n", $html);
	}

	protected function getGroups()
	{
		return null;
	}

	protected function getExcluded()
	{
		return null;
	}
}
