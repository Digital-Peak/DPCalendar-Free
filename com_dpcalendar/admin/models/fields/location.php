<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JFormHelper::loadFieldClass('list');

class JFormFieldLocation extends JFormFieldList
{
	public $type = 'Location';

	protected function getOptions()
	{
		$options = parent::getOptions();

		JLoader::import('joomla.application.component.model');

		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.limit', 0);
		foreach ($model->getItems() as $location) {
			$options[] = JHtml::_('select.option', $location->id, $location->title);
		}

		return $options;
	}
}
