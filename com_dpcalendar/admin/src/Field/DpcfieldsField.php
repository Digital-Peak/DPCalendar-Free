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

		$component = 'com_dpcalendar';
		$section   = (string)$this->element['section'];

		if (str_starts_with($section, 'com_')) {
			[$component, $section] = explode('.', $section, 2);
		}

		try {
			// @phpstan-ignore-next-line
			$form = Form::getInstance($component . '.' . $section, $section, ['control' => 'jform']);
			foreach ($form->getFieldset() as $field) {
				// Ignore spacers
				if ($field->type === 'Spacer') {
					continue;
				}

				$fieldName = $field->fieldname;

				// When no filter form use the compiled field name
				if (!str_contains($section, 'filter')) {
					$fieldName = DPCalendarHelper::getFieldName($field);
				}

				// Ignore when hidden
				if ($hide && array_filter($hide, fn (string $toHide): bool => fnmatch($toHide, $fieldName))) {
					continue;
				}

				$field->hidden = false;
				$options[]     = HTMLHelper::_('select.option', $fieldName, Text::_($field->getTitle()));
			}
		} catch (\Exception) {
			// Ignore form loading errors
		}

		$fields = FieldsHelper::getFields($component . '.' . str_replace('filter_events', 'event', $section));
		foreach ($fields as $field) {
			if ($section === 'filter_events' && $field->type !== 'text') {
				continue;
			}

			$options[] = HTMLHelper::_('select.option', $field->name, Text::_($field->label));
		}

		// Merge any additional options in the XML definition.
		return array_merge($options, parent::getOptions());
	}
}
