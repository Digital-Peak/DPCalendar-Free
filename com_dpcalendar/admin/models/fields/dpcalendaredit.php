<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

class JFormFieldDPCalendarEdit extends JFormFieldCategory
{
	public $type = 'DPCalendarEdit';

	protected function getOptions()
	{
		$this->element['extension'] = 'com_dpcalendar';

		$app = Factory::getApplication();

		$calendar = null;
		$id       = $app->isClient('administrator') ? 0 : $app->input->get('id');
		if (!empty($id) && $this->value) {
			$calendar = DPCalendarHelper::getCalendar($this->value);
		}

		$options = [];
		if (empty($calendar) || !$calendar->external) {
			$options = parent::getOptions();
		}

		if (empty($calendar) || $calendar->external) {
			PluginHelper::importPlugin('dpcalendar');
			$tmp = $app->triggerEvent('onCalendarsFetch', [null, !empty($calendar->system) ? $calendar->system : null]);
			if (!empty($tmp)) {
				foreach ($tmp as $calendars) {
					foreach ($calendars as $externalCalendar) {
						if (!$externalCalendar->canCreate && !$externalCalendar->canEdit) {
							continue;
						}
						$options[] = HTMLHelper::_('select.option', $externalCalendar->id, $externalCalendar->title);
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
