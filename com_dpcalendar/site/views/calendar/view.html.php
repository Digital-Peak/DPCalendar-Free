<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('libraries.dpcalendar.fullcalendar', JPATH_COMPONENT);

class DPCalendarViewCalendar extends \DPCalendar\View\BaseView
{

	public function init()
	{
		$items = $this->get('AllItems');

		if ($items === false) {
			return $this->setError(JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
		}

		$this->items = $items;

		$selectedCalendars = array();
		foreach ($items as $calendar) {
			$selectedCalendars[] = $calendar->id;
		}
		$this->selectedCalendars = $selectedCalendars;

		$doNotListCalendars = array();
		foreach ($this->params->get('idsdnl', array()) as $id) {
			$parent = DPCalendarHelper::getCalendar($id);
			if ($parent == null) {
				continue;
			}

			if ($parent->id != 'root') {
				$doNotListCalendars[$parent->id] = $parent;
			}

			if (!$parent->external) {
				foreach ($parent->getChildren(true) as $child) {
					$doNotListCalendars[$child->id] = DPCalendarHelper::getCalendar($child->id);
				}
			}
		}
		// if none are selected, use selected calendars
		$this->doNotListCalendars = empty($doNotListCalendars) ? $this->items : $doNotListCalendars;

		$this->quickaddForm = $this->getModel()->getQuickAddForm($this->params);

		$this->resources = [];
		if ($this->params->get('calendar_filter_locations') && $this->params->get('calendar_resource_views') && !\DPCalendar\Helper\DPCalendarHelper::isFree()) {
			// Load the model
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
			$model->getState();
			$model->setState('list.limit', 10000);
			$model->setState('filter.search', 'ids:' . implode($this->params->get('calendar_filter_locations'), ','));

			// Add the locations
			foreach ($model->getItems() as $location) {
				$rooms = array();
				if ($location->rooms) {
					foreach ($location->rooms as $room) {
						$rooms[] = (object)array('id' => $location->id . '-' . $room->id, 'title' => $room->title);
					}
				}

				$resource = (object)array('id' => $location->id, 'title' => $location->title);

				if ($rooms) {
					$resource->children = $rooms;
				}
				$this->resources[] = $resource;
			}
		}

		$this->return = $this->input->getInt('Itemid', null) ? 'index.php?Itemid=' . $this->input->getInt('Itemid', null) : null;

		return parent::init();
	}
}
