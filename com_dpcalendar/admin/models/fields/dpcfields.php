<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
FormHelper::loadFieldClass('list');

class JFormFieldDPCFields extends JFormFieldList
{
	public $type = 'DPCFields';

	protected function getOptions()
	{
		$options = [];

		Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/fields');

		$hide = explode(',', $this->element['hide']);
		$form = Form::getInstance('com_dpcalendar.' . $this->element['section'], $this->element['section'], ['control' => 'jform']);
		foreach ($form->getFieldset() as $field) {
			$isHidden = false;
			foreach ($hide as $toHide) {
				if (!fnmatch($toHide, DPCalendarHelper::getFieldName($field)) && $field->type != 'Spacer') {
					continue;
				}

				$isHidden = true;
				break;
			}

			if ($isHidden) {
				continue;
			}

			$options[] = HTMLHelper::_('select.option', DPCalendarHelper::getFieldName($field), Text::_($field->getTitle()));
		}

		$fields = FieldsHelper::getFields('com_dpcalendar.' . $this->element['section']);
		foreach ($fields as $field) {
			$options[] = HTMLHelper::_('select.option', $field->name, Text::_($field->label));
		}

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}
}
