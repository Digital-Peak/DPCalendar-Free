<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Router\Rules;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Site\Service\Router;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\Rules\MenuRules;

class DPCalendarRules extends MenuRules
{
	public function parse(&$segments, &$vars): void
	{
		parent::parse($segments, $vars);

		// When tickets or bookings should be shown as part of the event details view
		if ($this->router instanceof Router && \count($segments) === 2 && \in_array($segments[1], ['tickets', 'bookings'])) {
			$vars['view'] = $segments[1];
			$vars['e_id'] = $this->router->getEventId($segments[0]);
			unset($segments[0]);
			unset($segments[1]);
		}

		// When tickets or bookings should be shown as part of the event details menu item view
		if (\count($segments) === 1 && \in_array($segments[0], ['tickets', 'bookings'])) {
			$vars['view'] = $segments[0];
			unset($segments[0]);

			$active = $this->router->menu->getActive();
			if ($active && !empty($active->query['id'])) {
				$vars['e_id'] = $active->query['id'];
			}
		}
	}

	public function preprocess(&$query): void
	{
		if (!empty($query['view']) && \in_array($query['view'], ['tickets', 'bookings']) && !empty($query['e_id'])) {
			return;
		}

		parent::preprocess($query);

		// Special treatment for events and locations
		$this->processForEvent($query);
		$this->processForEventForm($query);
		$this->processForLocation($query);
	}

	protected function buildLookup($language = '*')
	{
		parent::buildLookup($language);

		// Getting the required variables to match the parent lookup
		$component = ComponentHelper::getComponent('com_' . $this->router->getName());

		$attributes = ['component_id'];
		$values     = [(int)$component->id];

		$attributes[] = 'language';
		$values[]     = [$language, '*'];

		$menuItems = $this->router->menu->getItems($attributes, $values);
		if (!\is_array($menuItems)) {
			$menuItems = [$menuItems];
		}

		$model = $this->router->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator');

		// As the calendar ids are in the params we need to assign them as calendar ids to the lookup
		foreach ($menuItems as $menuItem) {
			// Get the calendar ids form the menu item
			$ids = $menuItem->getParams()->get('ids');
			if (!$ids) {
				continue;
			}

			// Assign the menu item to the lookup
			foreach ($ids as $id) {
				$this->lookup[$language][$menuItem->query['view']][$id] = $menuItem->id;

				if (!\in_array($menuItem->query['view'], ['calendar', 'list', 'map'])) {
					continue;
				}

				$cal = $model->getCalendar($id);
				if (!$cal instanceof CalendarInterface) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$this->lookup[$language][$menuItem->query['view']][$child->getId()] = $menuItem->id;
				}
			}
		}

		// As the calendar ids are in the params we need to assign them as calendar ids to the lookup for the event forms
		foreach ($menuItems as $menuItem) {
			if ($menuItem->query['view'] !== 'form') {
				continue;
			}

			// Get the calendar ids form the menu item
			$ids = $menuItem->getParams()->get('event_form_calendars');
			if (!$ids) {
				$this->lookup[$language][$menuItem->query['view']][-1] = $menuItem->id;
				continue;
			}

			if (!\is_array($this->lookup[$language][$menuItem->query['view']])) {
				$this->lookup[$language][$menuItem->query['view']] = (array)$this->lookup[$language][$menuItem->query['view']];
			}

			// Assign the menu item to the lookup
			foreach ($ids as $id) {
				$this->lookup[$language][$menuItem->query['view']][$id] = $menuItem->id;

				$cal = $model->getCalendar($id);
				if (!$cal instanceof CalendarInterface) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$this->lookup[$language][$menuItem->query['view']][$child->getId()] = $menuItem->id;
				}
			}
		}
	}

	private function processForLocation(array &$query): void
	{
		// Do nothing when the query is not for a location
		if (empty($query['view']) || ($query['view'] != 'location' && $query['view'] != 'locationform')) {
			return;
		}

		// Loop over the menu items
		foreach ($this->lookup as $items) {
			$id = empty($query['id']) ? (empty($query['l_id']) ? 0 : $query['l_id']) : ($query['id']);

			// If the location exists in a location menu item, do nothing
			if (!empty($items['location']) && \array_key_exists($id, $items['location'])) {
				$query['Itemid'] = $items['location'][$id];

				return;
			}

			// If the location exists in a locations menu item, do nothing
			if (!empty($items['locations']) && \array_key_exists($id, $items['locations'])) {
				$query['Itemid'] = $items['locations'][$id];

				return;
			}

			// Search in the lookup for a passable menu item
			if (!empty($items['locations']) && \array_key_exists(-1, $items['locations'])) {
				$query['Itemid'] = $items['locations'][-1];

				return;
			}
		}

		// Unset the item id, the router creates then global urls
		unset($query['Itemid']);
	}

	private function processForEvent(array &$query): void
	{
		// If menu item is forced
		if (!empty($query['force_item_id'])) {
			$query['Itemid'] = $query['force_item_id'];
			unset($query['force_item_id']);

			return;
		}

		// Check if it is an event query and a calendar is available
		if (empty($query['view']) || $query['view'] != 'event' || empty($query['calid'])) {
			return;
		}

		// Check if a direct menu item is available
		foreach ($this->lookup as $items) {
			// If there is menu item for the event use it
			if (!empty($items['event']) && \array_key_exists($query['id'], $items['event'])) {
				// Ensure the correct item ID is set, can happen when a single event menu item exists and a calendar menu item
				// which has a different calendar selected
				$query['Itemid'] = $items['event'][$query['id']];
				return;
			}
		}

		// Get the calendar
		$calendar = $this->router->app->bootComponent('dpcalendar')
			->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($query['calid']);
		if (!$calendar instanceof CalendarInterface) {
			return;
		}

		// Get the items for the calendar default item parameter
		$items = $this->router->menu->getItems('id', $calendar->getParams()->get('default_menu_item', 0));

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
			&& \in_array($active->query['view'], ['calendar', 'list', 'map'])) {
			$selectedCalendars = [];
			foreach ($active->getParams()->get('ids', []) as $selectedCalendar) {
				$selectedCalendars[] = $selectedCalendar;

				$cal = $this->router->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($selectedCalendar);
				if (!$cal instanceof CalendarInterface) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$selectedCalendars[] = $child->getId();
				}
			}
			if (array_intersect($selectedCalendars, ['-1', $calendar->getId()]) !== []) {
				$query['Itemid'] = $active->id;

				return;
			}
		}

		// Search in the lookup for a passable menu item
		foreach ($this->lookup as $items) {
			foreach ($items as $calIds) {
				foreach ((array)$calIds as $calId => $menuItemId) {
					if ($calId && ($calendar->getId() == $calId || $calId == -1)) {
						$query['Itemid'] = $menuItemId;

						return;
					}
				}
			}
		}

		// Unset the item id, the router creates then global urls
		unset($query['Itemid']);
	}

	private function processForEventForm(array &$query): void
	{
		// Do nothing when the query is not for an event form
		if (empty($query['view']) || $query['view'] !== 'form') {
			return;
		}

		// Loop over the menu items
		foreach ($this->lookup as $items) {
			$id = empty($query['calid']) ? 0 : $query['calid'];

			// If the location exists in a location menu item, do nothing
			if (!empty($items['form']) && \array_key_exists($id, $items['form'])) {
				$query['Itemid'] = $items['form'][$id];

				return;
			}

			// Search in the lookup for a passable menu item
			if (!empty($items['form']) && \array_key_exists(-1, $items['form'])) {
				$query['Itemid'] = $items['form'][-1];

				return;
			}
		}
	}
}
