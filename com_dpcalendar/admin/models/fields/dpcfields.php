<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('list');

class JFormFieldDPCFields extends JFormFieldList
{
	public $type = 'DPCFields';

	protected function getOptions()
	{
		$options = array();
		\JLoader::import('components.com_fields.helpers.fields', JPATH_ADMINISTRATOR);

		$fields = FieldsHelper::getFields('com_dpcalendar.' . $this->element['section']);
		foreach ($fields as $field) {
			$options[] = JHtml::_('select.option', $field->name, JText::_($field->label));
		}

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}
}
