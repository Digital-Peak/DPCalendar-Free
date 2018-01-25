<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.form.formfield');
JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

class JFormFieldEvent extends JFormField
{

	protected $type = 'Event';

	protected function getInput ()
	{
		// Load modal behavior
		JHtml::_('behavior.modal', 'a.modal');

		// Build the script
		$script = array();
		$script[] = '    function jSelectEvent_' . $this->id . '(id, title, object) {';
		$script[] = '        document.id("' . $this->id . '_id").value = id;';
		$script[] = '        document.id("' . $this->id . '_name").value = title;';
		$script[] = '        jQuery("#' . $this->id . '_id").trigger("change");';
		$script[] = '        SqueezeBox.close();';
		$script[] = '    }';

		// Add to document head
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		// Setup variables for display
		$html = array();
		$link = 'index.php?option=com_dpcalendar&amp;view=events&amp;layout=modal' . '&amp;tmpl=component&amp;function=jSelectEvent_' . $this->id;

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('title');
		$query->from('#__dpcalendar_events');
		$query->where('id=' . (int) $this->value);
		$db->setQuery($query);
		if (! $title = $db->loadResult())
		{
			// JError::raiseWarning(500, $db->getErrorMsg());
		}
		if (empty($title))
		{
			$title = JText::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT');
		}
		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The current event input field

		$html[] = '  <div class="blank input-append">';
		$html[] = '  <input type="text" id="' . $this->id . '_name" value="' . $title . '" disabled="disabled" size="35" />';
		$html[] = '    <a class="modal btn btn-primary" title="' . JText::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT') . '" href="' . $link .
				 '" rel="{handler: \'iframe\', size: {x:800, y:450}}">' . JText::_('COM_DPCALENDAR_VIEW_EVENT_FIELD_ID_SELECT_EVENT_BUTTON') . '</a>';
		$html[] = '  </div>';

		// The active event id field
		if (0 == (int) $this->value)
		{
			$value = '';
		}
		else
		{
			$value = (int) $this->value;
		}

		$class = '';
		if ($this->required)
		{
			$class = ' class="required modal-value"';
		}

		$onchange = $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

		$html[] = '<input type="hidden" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" ' . $onchange . '/>';

		return implode("\n", $html);
	}
}
