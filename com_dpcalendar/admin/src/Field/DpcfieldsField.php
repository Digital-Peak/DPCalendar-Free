<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class DpcfieldsField extends ListField
{
	public $element;
	public $type = 'DPCFields';

	protected function getOptions(): array
	{
		$options = [];

		Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');

		$hide = explode(',', $this->element['hide'] ?: '');

		// @phpstan-ignore-next-line
		$form = Form::getInstance('com_dpcalendar.' . $this->element['section'], $this->element['section'], ['control' => 'jform']);
		foreach ($form->getFieldset() as $field) {
			$fieldName = $field->fieldname;

			// When no filter form use the compiled field name
			if (!str_contains((string)$this->element['section'], 'filter')) {
				$fieldName = DPCalendarHelper::getFieldName($field);
			}

			$isHidden = false;
			foreach ($hide as $toHide) {
				if (!fnmatch($toHide, $fieldName) && $field->type != 'Spacer') {
					continue;
				}

				$isHidden = true;
				break;
			}

			if ($isHidden) {
				continue;
			}

			$options[] = HTMLHelper::_('select.option', $fieldName, Text::_($field->getTitle()));
		}

		$fields = FieldsHelper::getFields('com_dpcalendar.' . $this->element['section']);
		foreach ($fields as $field) {
			$options[] = HTMLHelper::_('select.option', $field->name, Text::_($field->label));
		}

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}
}
