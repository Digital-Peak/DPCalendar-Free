<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.schema', JPATH_ADMINISTRATOR);

class DPCalendarViewLocation extends \DPCalendar\View\BaseView
{

	public function display($tpl = null)
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		$this->setModel(JModelLegacy::getInstance('Location', 'DPCalendarModel'), true);

		return parent::display($tpl);
	}

	public function init()
	{
		$this->item = $this->getModel()->getItem($this->input->getInt('id'));

		if ($this->item->id == null) {
			$this->set('Errors', JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		JLoader::import('joomla.application.component.model');
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$model = JModelLegacy::getInstance('Calendar', 'DPCalendarModel');
		$model->getState();
		$model->setState('filter.parentIds', array('root'));

		$this->ids = array();
		foreach ($model->getItems() as $calendar) {
			$this->ids[] = $calendar->id;
		}

		$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
		$model->setState('list.limit', 25);
		$model->setState('list.start-date', DPCalendarHelper::getDate());
		$model->setState('list.ordering', 'start_date');
		$model->setState('filter.expand', true);
		$model->setState('filter.ongoing', true);
		$model->setState('filter.state', 1);
		$model->setState('filter.language', JFactory::getLanguage());
		$model->setState('filter.locations', array($this->item->id));
		$this->events = $model->getItems();
	}
}
