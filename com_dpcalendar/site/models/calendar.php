<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Registry\Registry;

class DPCalendarModelCalendar extends JModelLegacy
{

	private $items = null;
	private $allItems = null;

	protected function populateState()
	{
		$app = JFactory::getApplication();
		$this->setState('filter.extension', 'com_dpcalendar');

		if (JFactory::getApplication()->input->getVar('ids', null) == null) {
			$this->setState('filter.parentIds', $this->state->get('parameters.menu', new Registry())->get('ids'));
			$this->setState('filter.categories', array());
		} else {
			$this->setState('filter.categories', explode(',', JFactory::getApplication()->input->getVar('ids', null)));
			$this->setState('filter.parentIds', $this->setState('filter.categories'));
		}

		$params = $app->getParams();
		$this->setState('params', $params);

		$this->setState('filter.published', 1);
		$this->setState('filter.access', true);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.extension');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.access');

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		if (!$this->items) {
			$app    = JFactory::getApplication();
			$menu   = $app->getMenu();
			$active = $menu->getActive();
			$params = new Registry();
			if ($active) {
				$params->loadString($active->params);
			}
			$this->items    = array();
			$this->allItems = array();

			foreach ($this->getState('filter.parentIds', array('root')) as $calendar) {
				if ($calendar == '-1') {
					$calendar = 'root';
				}

				$parent = DPCalendarHelper::getCalendar($calendar);
				if ($parent == null) {
					continue;
				}

				if ($parent->id != 'root') {
					$this->items[$parent->id]    = $parent;
					$this->allItems[$parent->id] = $parent;
				}

				if (!$parent->external) {
					$tmp     = $parent->getChildren(true);
					$filters = $this->getState('filter.categories');
					foreach ($tmp as $child) {
						$item                      = DPCalendarHelper::getCalendar($child->id);
						$this->allItems[$item->id] = $item;

						if (!empty($filters) && !in_array($item->id, $filters)) {
							continue;
						}
						$this->items[$item->id] = $item;
					}
				}
			}

			// Add caldav calendars when available
			$tmp = JFactory::getApplication()->triggerEvent('onCalendarsFetch', array(null, 'cd'));
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$this->items[$calendar->id]    = $calendar;
						$this->allItems[$calendar->id] = $calendar;
					}
				}
			}
		}

		return $this->items;
	}

	public function getAllItems()
	{
		if (!is_array($this->allItems)) {
			$this->getItems();
		}

		return $this->allItems;
	}
}
