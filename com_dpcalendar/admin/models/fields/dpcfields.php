<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
JFormHelper::loadFieldClass('list');

class JFormFieldDPCFields extends JFormFieldList
{
	public $type = 'DPCFields';

	protected function getOptions()
	{
		$options = [];

		JLoader::import('joomla.form.form');

		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		JForm::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/fields');

		$hide = explode(',', $this->element['hide']);
		$form = JForm::getInstance('com_dpcalendar.' . $this->element['section'], $this->element['section'], ['control' => 'jform']);
		foreach ($form->getFieldset() as $field) {
			$isHidden = false;
			foreach ($hide as $toHide) {
				if (!fnmatch($toHide, $field->fieldname) && $field->type != 'Spacer') {
					continue;
				}

				$isHidden = true;
				break;
			}

			if ($isHidden) {
				continue;
			}

			$options[] = JHtml::_('select.option', $field->fieldname, JText::_($field->getTitle()));
		}

		$fields = FieldsHelper::getFields('com_dpcalendar.' . $this->element['section']);
		foreach ($fields as $field) {
			$options[] = JHtml::_('select.option', $field->name, JText::_($field->label));
		}

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}
}
