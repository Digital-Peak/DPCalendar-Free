<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JFormHelper::loadFieldClass('list');

class JFormFieldDpcountries extends JFormFieldList
{
	protected $type = 'Dpcountries';

	public function getOptions()
	{
		$lang = JFactory::getApplication()->getLanguage();
		$lang->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = JModelLegacy::getInstance('Countries', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('filter.state', 1);

		$options = [];
		foreach ($model->getItems() as $country) {
			$options[] = JHtml::_('select.option', $country->id, $lang->_('COM_DPCALENDAR_COUNTRY_' . $country->short_code));
		}

		return $options;
	}
}
