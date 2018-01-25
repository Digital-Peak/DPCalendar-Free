<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('list');

class JFormFieldLocation extends JFormFieldList
{
	public $type = 'Location';

	protected function getOptions()
	{
		$options = parent::getOptions();

		JLoader::import('joomla.application.component.model');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
		$model->getState();
		$model->setState('list.limit', 0);
		foreach ($model->getItems() as $location) {
			$options[] = JHtml::_('select.option', $location->id, $location->title);
		}

		return $options;
	}
}
