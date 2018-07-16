<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();


class DPCalendarViewLocations extends \DPCalendar\View\BaseView
{

	public function display($tpl = null)
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel');
		$this->setModel($model, true);

		return parent::display($tpl);
	}

	public function init()
	{
		if ($ids = $this->params->get('ids')) {
			$this->getModel()->setState('filter.search', 'ids:' . implode(',', $ids));
		}
		$this->getModel()->setState('list.limit', 100);
		$this->locations = $this->get('Items');

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
		$model->setState('filter.locations', $this->params->get('ids'));
		$this->events = $model->getItems();

		$this->resources = [];
		foreach ($this->locations as $location) {
			$rooms = array();
			if ($location->rooms) {
				foreach ($location->rooms as $room) {
					$rooms[] = (object)array('id' => $location->id . '-' . $room->id, 'title' => $room->title);
				}
			}

			$this->resources[] = (object)array('id' => $location->id, 'title' => $location->title, 'children' => $rooms);
		}

		$this->return = $this->input->getInt('Itemid', null) ? 'index.php?Itemid=' . $this->input->getInt('Itemid', null) : null;
	}
}
