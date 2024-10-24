<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class DpcfieldsField extends ListField
{
	public $type = 'DPCFields';

	public function getFields(): array
	{
		return $this->getOptions();
	}

	protected function getOptions(): array
	{
		$options = [];

		Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');

		$hide = array_filter(explode(',', $this->element['hide'] ?: ''));

		// @phpstan-ignore-next-line
		$form = Form::getInstance('com_dpcalendar.' . $this->element['section'], $this->element['section'], ['control' => 'jform']);
		foreach ($form->getFieldset() as $field) {
			// Ignore spacers
			if ($field->type === 'Spacer') {
				continue;
			}

			$fieldName = $field->fieldname;

			// When no filter form use the compiled field name
			if (!str_contains((string)$this->element['section'], 'filter')) {
				$fieldName = DPCalendarHelper::getFieldName($field);
			}

			// Ignore when hidden
			if ($hide && array_filter($hide, fn ($toHide): bool => fnmatch($toHide, $fieldName))) {
				continue;
			}

			$field->hidden = false;
			$options[]     = HTMLHelper::_('select.option', $fieldName, Text::_($field->getTitle()));
		}

		$fields = FieldsHelper::getFields('com_dpcalendar.' . str_replace('filter_events', 'event', (string)$this->element['section']));
		foreach ($fields as $field) {
			if ((string)$this->element['section'] === 'filter_events' && $field->type !== 'text') {
				continue;
			}

			$options[] = HTMLHelper::_('select.option', $field->name, Text::_($field->label));
		}

		// Merge any additional options in the XML definition.
		return array_merge($options, parent::getOptions());
	}
}
