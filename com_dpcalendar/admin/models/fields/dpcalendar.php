<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\HTML\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CategoryField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
if (version_compare(JVERSION, 4, '<') && !class_exists('\\Joomla\\CMS\\Form\\Field\\CategoryField', false)) {
	FormHelper::loadFieldClass('category');
	class_alias('JFormFieldCategory', '\\Joomla\\CMS\\Form\\Field\\CategoryField');
}

class JFormFieldDPCalendar extends CategoryField
{
	public $type = 'DPCalendar';

	protected function getOptions()
	{
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
