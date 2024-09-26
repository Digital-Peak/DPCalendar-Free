<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CategoryField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

class DPCalendarField extends CategoryField
{
	public $type = 'DPCalendar';

	protected function getOptions()
	{
		// @phpstan-ignore-next-line
		$this->element['extension'] = 'com_dpcalendar';

		$doc = new HtmlDocument();
		$doc->loadScriptFile('dpcalendar/fields/dpcalendar.js');
		$doc->loadStyleFile('dpcalendar/fields/dpcalendar.css');

		$options = parent::getOptions();

		if ((bool)$this->element->attributes()->internal) {
			return $options;
		}

		PluginHelper::importPlugin('dpcalendar');
		$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
		if (!empty($tmp)) {
			foreach ($tmp as $calendars) {
				foreach ($calendars as $calendar) {
					// Don't show caldav calendars
					if (str_starts_with((string)$calendar->getId(), 'cd-')) {
						continue;
					}
					$options[] = HTMLHelper::_('select.option', $calendar->getId(), $calendar->getTitle());
				}
			}
		}

		$ids = (string)$this->element['ids'];
		if ($ids !== '' && $ids !== '0') {
			$ids = explode(',', $ids);

			// Allow options which are empty for placeholder, in defined array or when all are selected
			return array_values(
				array_filter(
					$options,
					static fn ($o): bool => $o->value === '' || \in_array($o->value, $ids) || \in_array('-1', $ids)
				)
			);
		}

		return $options;
	}
}
