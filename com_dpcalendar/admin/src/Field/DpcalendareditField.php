<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendarInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CategoryField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

class DPCalendareditField extends CategoryField
{
	public $type = 'DPCalendarEdit';

	protected function getOptions(): array
	{
		// @phpstan-ignore-next-line
		$this->element['extension'] = 'com_dpcalendar';

		$app = Factory::getApplication();

		$calendar = null;
		$id       = $app->isClient('administrator') ? 0 : $app->getInput()->get('id');
		if (!empty($id) && $this->value) {
			$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($this->value);
		}

		$options = [];
		if (!$calendar instanceof CalendarInterface || !$calendar instanceof ExternalCalendarInterface) {
			$options = parent::getOptions();
		}

		if (!$calendar instanceof CalendarInterface || $calendar instanceof ExternalCalendarInterface) {
			PluginHelper::importPlugin('dpcalendar');
			$tmp = $app->triggerEvent('onCalendarsFetch', [null, empty($calendar->system) ? null : $calendar->system]);
			if (!empty($tmp)) {
				foreach ($tmp as $calendars) {
					foreach ($calendars as $externalCalendar) {
						if (!$externalCalendar->canCreate() && !$externalCalendar->canEdit) {
							continue;
						}
						$options[] = HTMLHelper::_('select.option', $externalCalendar->id, $externalCalendar->title);
					}
				}
			}
		}

		$ids = $calendar instanceof CalendarInterface && !$calendar instanceof ExternalCalendarInterface ? [$calendar->getId()] : [];
		if ($app instanceof SiteApplication) {
			$activeMenu = $app->getMenu()->getActive();
			if ($activeMenu && $app->getInput()->get('option') == 'com_dpcalendar') {
				$ids = $activeMenu->getParams()->get('ids', []);
			}
		}

		// Reset keys, so we can reorder properly
		$options = array_values($options);

		$filter = '' . $this->element['calendar_filter'];
		if ($filter !== '') {
			$filter = explode(',', $filter);
			foreach ($options as $key => $cal) {
				if (in_array($cal->value, $filter)) {
					continue;
				}
				unset($options[$key]);
			}

			$options = array_values($options);
		}

		$toMove  = [];
		$counter = count($options);
		for ($i = 0; $i < $counter; $i++) {
			$option = $options[$i];

			if (!in_array($option->value, $ids)) {
				continue;
			}

			$toMove[$i] = $option;
			// Move subitems as well
			$counter = count($options);

			// Move subitems as well
			for ($j = $i + 1; $j < $counter; $j++) {
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
