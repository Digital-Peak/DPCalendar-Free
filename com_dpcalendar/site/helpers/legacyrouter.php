<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\Rules\RulesInterface;
use Joomla\CMS\Factory;

class DPCalendarRouterLegacy implements RulesInterface
{
	public function preprocess(&$query)
	{
	}

	public function build(&$query, &$segments)
	{
		// Get a menu item based on Itemid or currently active
		$app      = Factory::getApplication();
		$menu     = $app->getMenu();
		$params   = ComponentHelper::getParams('com_dpcalendar');
		$advanced = $params->get('sef_advanced_link', 0);

		// We need a menu item. Either the one specified in the query, or the
		// current active one if none specified
		if (empty($query['Itemid'])) {
			$menuItem = $menu->getActive();
		} else {
			$menuItem = $menu->getItem($query['Itemid']);
		}

		$mView  = (empty($menuItem->query['view'])) ? null : $menuItem->query['view'];
		$mCatid = (empty($menuItem->query['calid'])) ? null : $menuItem->query['calid'];
		$mId    = (empty($menuItem->query['id'])) ? null : $menuItem->query['id'];

		if (isset($query['view'])) {
			$view = $query['view'];

			if (empty($query['Itemid']) || $view == 'events') {
				$segments[] = $query['view'];
			}

			// We need to keep the view for forms since they never have their
			// own menu item
			if ($view != 'form' && $view != 'davcalendar' && $view != 'booking' && $view != 'bookingform' && $view != 'bookings' && $view != 'ticket' &&
				$view != 'tickets' && $view != 'pay' && $view != 'message' && $view != 'callback' && $view != 'locationform' &&
				$view != 'ticketform' && $view != 'location') {
				unset($query['view']);
			}
		}

		// Are we dealing with an event that is attached to a menu item?
		if (isset($query['view']) && ($mView == $query['view']) and (isset($query['id'])) and ($mId == intval($query['id']))) {
			unset($query['view']);
			unset($query['calid']);
			unset($query['id']);

			return $segments;
		}

		if (isset($view) and ($view == 'calendar' or $view == 'event')) {
			if (isset($query['id']) && $mId != intval($query['id']) || $mView != $view) {
				$calid = null;
				if ($view == 'event' && isset($query['calid'])) {
					$calid = $query['calid'];
				} elseif (isset($query['id'])) {
					$calid = $query['id'];
				}

				JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

				$menuCatid = $mId;
				$category  = DPCalendarHelper::getCalendar($calid);

				if ($category && ! $category->external) {
					// TODO Throw error that the category either not exists or
					// is unpublished
					$path = $category->getPath();
					$path = array_reverse($path);

					$array = [];
					foreach ($path as $id) {
						if ((int) $id == (int) $menuCatid) {
							break;
						}

						if ($advanced) {
							list($tmp, $id) = explode(':', $id, 2);
						}

						$array[] = $id;
					}
					$segments = array_merge($segments, array_reverse($array));
				}

				if ($view == 'event') {
					if ($advanced) {
						list($tmp, $id) = explode(':', $query['id'], 2);
					} else {
						$id = $query['id'];
					}

					$segments[] = $id;
				}
			}

			unset($query['id']);
			unset($query['calid']);
		}

		if (isset($query['layout'])) {
			if (! empty($query['Itemid']) && isset($menuItem->query['layout'])) {
				if ($query['layout'] == $menuItem->query['layout']) {
					unset($query['layout']);
				}
			} else {
				if ($query['layout'] == 'default') {
					unset($query['layout']);
				}
			}
		}

		return $segments;
	}

	public function parse(&$segments, &$vars)
	{
		JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

		// Get the active menu item.
		$app      = Factory::getApplication();
		$menu     = $app->getMenu();
		$item     = $menu->getActive();
		$params   = ComponentHelper::getParams('com_dpcalendar');
		$advanced = $params->get('sef_advanced_link', 0);

		// Count route segments
		$count = count($segments);

		if (! empty($segments) && $segments[0] == 'events') {
			$vars['view']   = $segments[0];
			$vars['format'] = 'raw';
			return $vars;
		}

		// Standard routing for events.
		if (! isset($item)) {
			$vars['view'] = $segments[0];
			$vars['id']   = $segments[$count - 1];
			return $vars;
		}

		// From the categories view, we can only jump to a category.
		$id = (isset($item->query['id']) && $item->query['id'] > 1) ? $item->query['id'] : 'root';

		$category = DPCalendarHelper::getCalendar($id);

		$categories = [];

		if (method_exists($category, 'getChildren')) {
			$categories = $category->getChildren();
		}
		$found = 0;

		foreach ($segments as $index => $segment) {
			foreach ($categories as $category) {
				if (($category->slug == $segment) || ($advanced && $category->alias == str_replace(':', '-', $segment))) {
					$vars['id']   = $category->id;
					$vars['view'] = 'calendar';
					$categories   = $category->getChildren();
					$found        = 1;
					break;
				}
			}

			if ($found == 0) {
				if ($advanced) {
					$db    = Factory::getDBO();
					$query = 'SELECT id FROM #__dpcalendar_events WHERE catid = ' . $vars['id'] . ' AND alias = ' .
						$db->quote(str_replace(':', '-', $segment));
					$db->setQuery($query);
					$id = $db->loadResult();
				} else {
					$id = $segment;
					unset($segments[$index]);
				}

				$vars['id']   = $id;
				$vars['view'] = 'event';

				break;
			}

			$found = 0;
		}

		return $vars;
	}
}
