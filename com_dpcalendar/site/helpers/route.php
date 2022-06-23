<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class DPCalendarHelperRoute
{
	private static $lookup;

	public static function getEventRoute($id, $calId, $full = false, $autoRoute = true, $defaultItemId = 0)
	{
		// Check if we come from com_tags where the link is generated id:alias
		$parts = $id ? explode(':', $id) : [];
		if (count($parts) == 2 && is_numeric($parts[0])) {
			$id = (int)$id;
		}

		// Create the link
		$link = 'index.php?option=com_dpcalendar&view=event&id=' . $id;
		if ($tmpl = Factory::getApplication()->input->getWord('tmpl')) {
			$link .= '&tmpl=' . $tmpl;
		}

		JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

		if (DPCalendarHelper::getComponentParameter('sef_advanced', 1)) {
			$link .= '&calid=' . $calId;
			if ($defaultItemId) {
				$link .= '&force_item_id=' . $defaultItemId;
			}
		} else {
			$needles = ['event' => [(int)$id]];
			if ($calId > 0 || (!is_numeric($calId) && $calId != 'root')) {
				$needles['calendar'] = [$calId];
				$needles['list']     = [$calId];
				$needles['map']      = [$calId];
			}

			if ($defaultItemId) {
				$link .= '&Itemid=' . $defaultItemId;
			} elseif ($item = self::findItem($needles)) {
				$link .= '&Itemid=' . $item;
			} elseif ($item = self::findItem()) {
				$link .= '&Itemid=' . $item;
			}
		}

		if (!$autoRoute) {
			return ($full ? Uri::root() : '') . $link;
		}

		return Route::link('site', $link, false, Route::TLS_IGNORE, $full);
	}

	public static function getFormRoute($id, $return = null, $append = null)
	{
		if ($id) {
			$link = 'index.php?option=com_dpcalendar&task=event.edit&e_id=' . $id;
		} else {
			if (Factory::getApplication()->isClient('administrator')) {
				$link = 'index.php?option=com_dpcalendar&task=event.add&e_id=0';
			} else {
				$link = 'index.php?option=com_dpcalendar&view=form&e_id=0';
			}
		}

		$itemId = Factory::getApplication()->input->get('Itemid', null);
		if (!empty($itemId)) {
			$link .= '&Itemid=' . $itemId;
		}

		if (!empty($append)) {
			$link .= '&' . $append;
		}
		if (Factory::getApplication()->input->getWord('tmpl')) {
			$link .= '&tmpl=' . Factory::getApplication()->input->getWord('tmpl');
		}
		if ($return) {
			$link .= '&return=' . base64_encode($return);
		}

		return $link;
	}

	public static function getLocationRoute($location, $full = false)
	{
		// Create the link
		$link = ($full ? Uri::root() : '') . 'index.php?option=com_dpcalendar&view=location&id=' . $location->id;

		if ($tmpl = Factory::getApplication()->input->getWord('tmpl')) {
			$link .= '&tmpl=' . $tmpl;
		}

		if (!DPCalendarHelper::getComponentParameter('sef_advanced', 1)) {
			$needles = ['location' => [(int)$location->id], 'locations' => [(int)$location->id]];
			if ($item = self::findItem($needles)) {
				$link .= '&Itemid=' . $item;
			} elseif ($item = self::findItem()) {
				$link .= '&Itemid=' . $item;
			}
		}

		return Route::_($link, false);
	}

	public static function getLocationFormRoute($id, $return = null)
	{
		if ($id) {
			$link = 'index.php?option=com_dpcalendar&task=locationform.edit&l_id=' . $id;
		} else {
			$link = 'index.php?option=com_dpcalendar&view=locationform&l_id=0';
		}

		if (!DPCalendarHelper::getComponentParameter('sef_advanced', 1)) {
			$itemId = Factory::getApplication()->input->get('Itemid', null);
			if (!empty($itemId)) {
				$link .= '&Itemid=' . $itemId;
			}
		}

		if (Factory::getApplication()->input->getWord('tmpl')) {
			$link .= '&tmpl=' . Factory::getApplication()->input->getWord('tmpl');
		}
		if ($return) {
			$link .= '&return=' . base64_encode($return);
		}

		return $link;
	}

	public static function getBookingRoute($booking, $full = false)
	{
		$args         = [];
		$args['view'] = 'booking';
		$args['uid']  = $booking->uid;

		if ($booking->token) {
			$args['token'] = $booking->token;
		}

		$uri = self::getUrl($args, false);

		$url = Route::_($uri->toString(['path', 'query', 'fragment']), false);
		if ($full) {
			$url = ($full ? Uri::getInstance()->toString(['host', 'port', 'scheme']) : '') .
				Route::_('index.php' . $uri->toString(['query', 'fragment']), false);
		}

		// When a booking is created on the back end it contains the administrator part
		return str_replace('/administrator/', '/', $url);
	}

	public static function getBookingsRoute($eventId)
	{
		$url  = 'index.php?option=com_dpcalendar&view=bookings';
		$tmpl = Factory::getApplication()->input->getWord('tmpl');
		if ($tmpl) {
			$url .= '&tmpl=' . $tmpl;
		}
		if ($eventId) {
			$url .= '&e_id=' . $eventId;
		}

		return Route::_($url);
	}

	public static function getInviteRoute($event, $return = null)
	{
		$args         = [];
		$args['view'] = 'invite';
		$args['id']   = $event->id;
		if (empty($return)) {
			$return = Uri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getInviteChangeRoute($booking, $accept, $full)
	{
		$args           = [];
		$args['task']   = 'booking.invite';
		$args['uid']    = $booking->uid;
		$args['accept'] = $accept ? '1' : '0';

		if ($booking->token) {
			$args['token'] = $booking->token;
		}

		$uri = self::getUrl($args, false);

		$url = Route::_($uri->toString(['path', 'query', 'fragment']), false);
		if ($full) {
			$url = ($full ? Uri::getInstance()->toString(['host', 'port', 'scheme']) : '') .
				Route::_('index.php' . $uri->toString(['query', 'fragment']), false);
		}

		// When a booking is created on the back end it contains the administrator part
		return str_replace('/administrator/', '/', $url);
	}

	public static function getBookingFormRoute($bookingId, $return = null)
	{
		$args         = [];
		$args['task'] = 'bookingform.edit';
		$args['b_id'] = is_object($bookingId) ? $bookingId->id : $bookingId;

		if (is_object($bookingId) && $bookingId->token) {
			$args['token'] = $bookingId->token;
		}

		if (empty($return)) {
			$return = Uri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getBookingFormRouteFromEvent($event, $return = null, $autoRoute = true)
	{
		$args         = [];
		$args['task'] = 'bookingform.add';
		$args['e_id'] = $event->id;
		if (empty($return)) {
			$return = self::getEventRoute($event->id, $event->catid);
		}
		$args['return'] = base64_encode($return);

		$needles             = ['event' => [$event->id]];
		$needles['calendar'] = [$event->catid];
		$needles['list']     = [$event->catid];
		$needles['map']      = [$event->catid];

		return self::getUrl($args, $autoRoute, $needles);
	}

	public static function getTicketRoute($ticket, $full = false)
	{
		$args         = [];
		$args['view'] = 'ticket';
		$args['uid']  = $ticket->uid;

		$uri = self::getUrl($args, false);
		$uri = $full ? $uri->toString() : Route::_($uri->toString(['path', 'query', 'fragment']));
		$uri = str_replace('/administrator/', '/', $uri);

		return $uri;
	}

	public static function getTicketCheckinRoute($ticket, $full = false)
	{
		$args         = [];
		$args['uid']  = $ticket->uid;
		$args['task'] = 'ticket.checkin';

		$uri = self::getUrl($args, false);

		$url = Route::_($uri->toString(['path', 'query', 'fragment']), false);
		if ($full) {
			$url = ($full ? Uri::getInstance()->toString(['host', 'port', 'scheme']) : '')
				. Route::_('index.php' . $uri->toString(['query', 'fragment']), false);
		}

		// When a Ticket urls is created on the back end it contains the administrator part
		return str_replace('/administrator/', '/', $url);
	}

	public static function getTicketsRoute($bookingId = null, $eventId = null, $my = false)
	{
		$args         = [];
		$args['view'] = 'tickets';

		if ($bookingId) {
			$args['b_id'] = $bookingId;
		}
		if ($eventId) {
			$args['e_id'] = $eventId;
		}
		if ($my) {
			$args['filter[my]'] = 1;
		}

		return self::getUrl($args, true);
	}

	public static function getTicketFormRoute($ticketId, $return = null)
	{
		$args         = [];
		$args['task'] = 'ticketform.edit';
		$args['t_id'] = $ticketId;

		if (empty($return)) {
			$return = Uri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getTicketDeleteRoute($ticketId, $return = null)
	{
		$args         = [];
		$args['task'] = 'ticketform.delete';
		$args['t_id'] = $ticketId;

		if (empty($return)) {
			$return = Uri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getCalendarIcalRoute($calId, $token = '')
	{
		$url = Uri::base();
		$url .= 'index.php?option=com_dpcalendar&task=ical.download&id=' . $calId;

		if ($token) {
			$url .= '&token=' . $token;
		}

		return $url;
	}

	public static function getCalendarRoute($calId)
	{
		if ($calId instanceof CategoryNode) {
			$id       = $calId->id;
			$calendar = $calId;
		} else {
			$id       = $calId;
			$calendar = DPCalendarHelper::getCalendar($id);
		}

		if ($id == '0') {
			$link = '';
		} else {
			$needles = [
				'calendar' => [
					$id
				],
				'list' => [
					$id
				],
				'map' => [
					$id
				]
			];

			if ($item = self::findItem($needles)) {
				$link = 'index.php?Itemid=' . $item;
			} else {
				// Create the link
				$link = 'index.php?option=com_dpcalendar&view=calendar&id=' . $id;

				if ($calendar) {
					$calIds = [];
					if ($calId instanceof CategoryNode) {
						$calIds = array_reverse($calendar->getPath());
					} else {
						$calIds[] = $calendar->id;
					}

					$needles = [
						'calendar' => $calIds,
						'map'      => $calIds,
						'list'     => $calIds
					];

					if ($item = self::findItem($needles)) {
						$link .= '&Itemid=' . $item;
					} elseif ($item = self::findItem()) {
						$link .= '&Itemid=' . $item;
					}
				}
			}
		}

		return $link;
	}

	public static function getCategoryRoute($catid, $language)
	{
		// Is needed for smart search categories indexing
		JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

		return self::getCalendarRoute($catid);
	}

	public static function findItem($needles = null)
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu('site');

		// Prepare the reverse lookup array.
		if (self::$lookup === null) {
			self::$lookup = [];

			$component = ComponentHelper::getComponent('com_dpcalendar');
			$items     = $menus->getItems('component_id', $component->id);

			if ($items) {
				// The active item should be moved to the last position
				// that it doesn't get overwritten.
				$active = $menus->getActive();
				if ($active && $active->component == 'com_dpcalendar') {
					$items[] = $active;
				}

				foreach ($items as $item) {
					if (isset($item->query) && isset($item->query['view'])) {
						$view = $item->query['view'];

						if (!isset(self::$lookup[$view])) {
							self::$lookup[$view] = [];
						}

						$ids = $item->getParams()->get('ids');
						if (!is_array($ids) && $ids) {
							$ids = [
								$ids
							];
						}
						if (!$ids && isset($item->query['id'])) {
							$ids = [
								$item->query['id']
							];
						}

						if ($ids === null) {
							$ids = [];
						}

						foreach ($ids as $id) {
							$root = DPCalendarHelper::getCalendar($id);
							if ($root == null && $view != 'location') {
								continue;
							}
							self::$lookup[$view][$id] = $item->id;
							if ($root && !$root->external) {
								foreach ($root->getChildren(true) as $child) {
									self::$lookup[$view][$child->id] = $item->id;
								}
							}
						}
					}
				}
			}
		}
		if ($needles) {
			$active = $menus->getActive();
			if ($active
				&& $active->component == 'com_dpcalendar'
				&& isset($active->query)
				&& isset($active->query['view'])
				&& isset($needles[$active->query['view']])) {
				// Move the actual item to the first position
				$tmp = [$active->query['view'] => $needles[$active->query['view']]];
				unset($needles[$active->query['view']]);
				$needles = array_merge($tmp, $needles);
			}

			foreach ($needles as $view => $ids) {
				if (isset(self::$lookup[$view])) {
					foreach ($ids as $id) {
						if (isset(self::$lookup[$view][$id])) {
							return self::$lookup[$view][$id];
						}
					}
				}
			}
		} else {
			$active = $menus->getActive();
			if ($active && $active->component == 'com_dpcalendar') {
				return $active->id;
			}
		}

		return null;
	}

	private static function getUrl($arguments = [], $route = true, $needles = [])
	{
		$uri = clone Uri::getInstance();
		if (Factory::getDocument()->getType() != 'html' || Factory::getApplication()->isClient('site')) {
			$uri = Uri::getInstance('index.php');
		}
		$uri->setQuery('');
		$input = Factory::getApplication()->input;

		if ($input->get('option') != 'com_dpcalendar' || strpos($uri->getPath(), 'index.php') !== false) {
			$arguments['option'] = 'com_dpcalendar';

			if ($itemId = self::findItem($needles)) {
				$arguments['Itemid'] = $itemId;
			}
		}

		$tmpl = $input->getWord('tmpl');
		if ($tmpl) {
			$arguments['tmpl'] = $tmpl;
		}

		foreach ($arguments as $key => $value) {
			$uri->setVar($key, $value);
		}

		if ($route) {
			return Route::_($uri->toString(['path', 'query', 'fragment']));
		}

		return $uri;
	}
}
