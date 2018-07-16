<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Router\Rules;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\Registry\Registry;

class DPCalendarRules extends \JComponentRouterRulesMenu
{
	public function preprocess(&$query)
	{
		parent::preprocess($query);

		// Special treatment for events and locations
		$this->processForEvent($query);
		$this->processForLocation($query);
	}

	protected function buildLookup($language = '*')
	{
		parent::buildLookup($language);

		// Getting the required variables to match the parent lookup
		$component = \JComponentHelper::getComponent('com_' . $this->router->getName());

		$attributes = array('component_id');
		$values     = array((int)$component->id);

		$attributes[] = 'language';
		$values[]     = array($language, '*');

		$menuItems = $this->router->menu->getItems($attributes, $values);

		// As the ids of the calendar are in the params we need to assign them as calendar ids to the lookup
		foreach ($menuItems as $menuItem) {
			// Get the calendar ids form the menu item
			$ids = $menuItem->getParams()->get('ids');
			if (!$ids) {
				continue;
			}

			// Assign the menu item to the lookup
			foreach ($ids as $id) {
				$this->lookup[$language][$menuItem->query['view']][$id] = $menuItem->id;
			}
		}
	}

	private function processForLocation(&$query)
	{
		// Do nothing when the query is not for a location
		if (empty($query['view']) || $query['view'] != 'location') {
			return;
		}

		// Loop over the menu items
		foreach ($this->lookup as $languageName => $items) {
			// If the location exists in a location menu item, do nothing
			if (!empty($items['location']) && array_key_exists($query['id'], $items['location'])) {
				return;
			}

			// If the location exists in a locations menu item, do nothing
			if (!empty($items['locations']) && array_key_exists($query['id'], $items['locations'])) {
				return;
			}
		}

		// Unset the item id, the router creates then global urls
		unset($query['Itemid']);
	}

	private function processForEvent(&$query)
	{
		// Check if it is an event query and a calendar is available
		if (empty($query['view']) || $query['view'] != 'event' || empty($query['calid'])) {
			return;
		}

		// Check if a direct menu item is available
		foreach ($this->lookup as $languageName => $items) {
			// If there is menu item for the event use it
			if (!empty($items['event']) && array_key_exists($query['id'], $items['event'])) {
				return;
			}
		}

		// Get the calendar
		$cal = DPCalendarHelper::getCalendar($query['calid']);
		if (!$cal) {
			return;
		}

		// Get the items for the calendar default item parameter
		$items = $this->router->menu->getItems('id', $cal->params->get('default_menu_item'));

		// When available set the item id of the default menu item
		if ($items) {
			$query['Itemid'] = reset($items)->id;

			return;
		}

		// The active element
		$active = $this->router->menu->getActive();

		// If we have no default item but the active fits as a parent for the event view use it as id
		// This means we do not have unique ids, but the event is always shown below the actual menu item
		if ($active && $active->component == 'com_dpcalendar'
			&& in_array($active->query['view'], ['calendar', 'list', 'map'])
			&& array_intersect($active->getParams()->get('ids', []), ['-1', $cal->id])) {
			$query['Itemid'] = $active->id;

			return;
		}

		// Search in the lookup for a passable menu item
		foreach ($this->lookup as $languageName => $items) {
			foreach ($items as $viewName => $calIds) {
				foreach ($calIds as $calId => $menuItemId) {
					if ($cal->id == $calId) {
						$query['Itemid'] = $menuItemId;

						return;
					}
				}
			}
		}

		// Unset the item id, the router creates then global urls
		unset($query['Itemid']);
	}
}
