<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\HTML\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
FormHelper::loadFieldClass('category');

class JFormFieldDPCalendar extends JFormFieldCategory
{
	public $type = 'DPCalendar';

	protected function getOptions()
	{
		$doc = new HtmlDocument();
		$doc->loadScriptFile('dpcalendar/fields/dpcalendar.js');
		$doc->loadStyleFile('dpcalendar/fields/dpcalendar.css');

		$options = parent::getOptions();

		if ((bool)$this->element->attributes()->internal) {
			return $options;
		}

		PluginHelper::importPlugin('dpcalendar');
		$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
		if (empty($tmp)) {
			return $options;
		}

		foreach ($tmp as $calendars) {
			foreach ($calendars as $calendar) {
				// Don't show caldav calendars
				if (strpos($calendar->id, 'cd-') === 0) {
					continue;
				}
				$options[] = HTMLHelper::_('select.option', $calendar->id, $calendar->title);
			}
		}

		return $options;
	}
}
