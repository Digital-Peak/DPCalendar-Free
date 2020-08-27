<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
Jloader::import('components.com_categories.models.fields.categoryedit', JPATH_ADMINISTRATOR);

class JFormFieldDPCalendarEdit extends JFormFieldCategoryEdit
{
	public $type = 'DPCalendarEdit';

	protected function getOptions()
	{
		$app = JFactory::getApplication();

		$calendar = null;
		$id       = $app->isClient('administrator') ? 0 : $app->input->get('id');
		if (!empty($id) && $this->value) {
			$calendar = \DPCalendar\Helper\DPCalendarHelper::getCalendar($this->value);
		}

		$options = [];
		if (empty($calendar) || !$calendar->external) {
			$options = parent::getOptions();
		}

		if (empty($calendar) || $calendar->external) {
			JPluginHelper::importPlugin('dpcalendar');
			$tmp = $app->triggerEvent('onCalendarsFetch', [null, !empty($calendar->system) ? $calendar->system : null]);
			if (!empty($tmp)) {
				foreach ($tmp as $calendars) {
					foreach ($calendars as $externalCalendar) {
						if (!$externalCalendar->canCreate && !$externalCalendar->canEdit) {
							continue;
						}
						$options[] = JHtml::_('select.option', $externalCalendar->id, $externalCalendar->title);
					}
				}
			}
		}

		$ids = [];
		if ($app->isClient('site')) {
			$activeMenu = $app->getMenu()->getActive();
			if ($activeMenu && $app->input->get('option') == 'com_dpcalendar') {
				$ids = $activeMenu->getParams()->get('ids', []);
			}
		}

		// Reset keys, so we can reorder properly
		$options = array_values($options);

		$toMove = [];
		for ($i = 0; $i < count($options); $i++) {
			$option = $options[$i];

			if (!in_array($option->value, $ids)) {
				continue;
			}

			$toMove[$i] = $option;

			// Move subitems as well
			for ($j = $i + 1; $j < count($options); $j++) {
				$child = $options[$j];

				if (!isset($child->level) || !isset($option->level) || $child->level <= $option->level) {
					break;
				}
				$toMove[$j] = $child;
				$i          = $j;
			}
		}

		return array_values($toMove + $options);
	}
}
