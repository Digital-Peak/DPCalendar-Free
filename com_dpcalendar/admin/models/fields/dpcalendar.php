<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JFormHelper::loadFieldClass('category');

class JFormFieldDPCalendar extends JFormFieldCategory
{
	public $type = 'DPCalendar';

	protected function getOptions()
	{
		$options = parent::getOptions();

		if ((boolean)$this->element->attributes()->internal) {
			return $options;
		}

		JPluginHelper::importPlugin('dpcalendar');
		$tmp = JFactory::getApplication()->triggerEvent('onCalendarsFetch');
		if (empty($tmp)) {
			return $options;
		}

		foreach ($tmp as $calendars) {
			foreach ($calendars as $calendar) {
				// Don't show caldav calendars
				if (strpos($calendar->id, 'cd-') === 0) {
					continue;
				}
				$options[] = JHtml::_('select.option', $calendar->id, $calendar->title);
			}
		}

		return $options;
	}
}
