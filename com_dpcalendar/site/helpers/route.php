<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarHelperRoute
{

	private static $lookup;

	public static function getEventRoute($id, $calId, $full = false, $autoRoute = true, $defaultItemId = 0)
	{
		$needles = array(
			'event' => array(
				(int)$id
			)
		);
		$tmpl    = '';
		if (JFactory::getApplication()->input->getWord('tmpl')) {
			$tmpl = '&tmpl=' . JFactory::getApplication()->input->getWord('tmpl');
		}

		// Check if we come from com_tags where the link is generated id:alias
		$parts = explode(':', $id);
		if (count($parts) == 2 && is_numeric($parts[0])) {
			$id = (int)$id;
		}

		// Create the link
		$link = ($full ? JUri::root() : '') . 'index.php?option=com_dpcalendar&view=event&id=' . $id . $tmpl;
		if ($calId > 0 || (!is_numeric($calId) && $calId != 'root')) {
			$needles['calendar'] = array(
				$calId
			);
			$needles['list']     = array(
				$calId
			);
			$needles['map']      = array(
				$calId
			);
		}

		if ($defaultItemId) {
			$link .= '&Itemid=' . $defaultItemId;
		} else if ($item = self::findItem($needles)) {
			$link .= '&Itemid=' . $item;
		} else if ($item = self::findItem()) {
			$link .= '&Itemid=' . $item;
		}

		if (!$autoRoute) {
			return $link;
		}

		return JRoute::_($link, false);
	}

	public static function getFormRoute($id, $return = null, $append = null)
	{
		if ($id) {
			$link = 'index.php?option=com_dpcalendar&task=event.edit&layout=edit&e_id=' . $id;
		} else {
			if (JFactory::getApplication()->isAdmin()) {
				$link = 'index.php?option=com_dpcalendar&task=event.add&e_id=0';
			} else {
				$link = 'index.php?option=com_dpcalendar&view=form&layout=edit&e_id=0';
			}
		}

		$itemId = JFactory::getApplication()->input->get('Itemid', null);
		if (!empty($itemId)) {
			$link .= '&Itemid=' . $itemId;
		}

		if (!empty($append)) {
			$link .= '&' . $append;
		}
		if (JFactory::getApplication()->input->getWord('tmpl')) {
			$link .= '&tmpl=' . JFactory::getApplication()->input->getWord('tmpl');
		}
		if ($return) {
			$link .= '&return=' . base64_encode($return);
		}

		return $link;
	}

	public static function getLocationRoute($location)
	{
		$needles = array(
			'location'  => array(
				(int)$location->id
			),
			'locations' => array(
				(int)$location->id
			)
		);
		$tmpl    = '';
		if (JFactory::getApplication()->input->getWord('tmpl')) {
			$tmpl = '&tmpl=' . JFactory::getApplication()->input->getWord('tmpl');
		}

		// Create the link
		$link = 'index.php?option=com_dpcalendar&view=location&id=' . $location->id . $tmpl;

		if ($item = self::findItem($needles)) {
			$link .= '&Itemid=' . $item;
		} else if ($item = self::findItem()) {
			$link .= '&Itemid=' . $item;
		}

		return JRoute::_($link, false);
	}

	public static function getLocationFormRoute($id, $return = null)
	{
		if ($id) {
			$link = 'index.php?option=com_dpcalendar&task=locationform.edit&layout=edit&l_id=' . $id;
		} else {
			$link = 'index.php?option=com_dpcalendar&view=locationform&layout=edit&l_id=0';
		}

		$itemId = JFactory::getApplication()->input->get('Itemid', null);
		if (!empty($itemId)) {
			$link .= '&Itemid=' . $itemId;
		}

		if (JFactory::getApplication()->input->getWord('tmpl')) {
			$link .= '&tmpl=' . JFactory::getApplication()->input->getWord('tmpl');
		}
		if ($return) {
			$link .= '&return=' . base64_encode($return);
		}

		return $link;
	}

	public static function getBookingRoute($booking, $full = false)
	{
		$args         = array();
		$args['view'] = 'booking';
		$args['uid']  = $booking->uid;

		$uri = self::getUrl($args, false);

		$uri = $full ? $uri->toString() : JRoute::_($uri->toString(array('path', 'query', 'fragment')));

		// When a booking is created on the back end it contains the
		// administrator part
		$uri = str_replace('/administrator/', '/', $uri);

		return $uri;
	}

	public static function getBookingsRoute($eventId)
	{
		$url  = 'index.php?option=com_dpcalendar&view=bookings';
		$tmpl = JFactory::getApplication()->input->getWord('tmpl');
		if ($tmpl) {
			$url .= '&tmpl=' . $tmpl;
		}
		if ($eventId) {
			$url .= '&e_id=' . $eventId;
		}

		return JRoute::_($url);
	}

	public static function getInviteRoute($event, $return = null)
	{
		$args         = array();
		$args['view'] = 'invite';
		$args['id']   = $event->id;
		if (empty($return)) {
			$return = JUri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getInviteChangeRoute($booking, $accept, $full)
	{
		$args           = array();
		$args['task']   = 'booking.invite';
		$args['uid']    = $booking->uid;
		$args['accept'] = $accept ? '1' : '0';

		$uri = self::getUrl($args, false);

		return $full ? $uri->toString() : JRoute::_($uri->toString(array('path', 'query', 'fragment')));
	}

	public static function getBookingFormRoute($bookingId, $return = null)
	{
		$args         = array();
		$args['task'] = 'bookingform.edit';
		$args['b_id'] = $bookingId;
		if (empty($return)) {
			$return = JUri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getBookingFormRouteFromEvent($event, $return = null)
	{
		$args         = array();
		$args['task'] = 'bookingform.add';
		$args['e_id'] = $event->id;
		if (empty($return)) {
			$return = self::getEventRoute($event->id, $event->catid);
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getTicketRoute($ticket, $full = false)
	{
		$args         = array();
		$args['view'] = 'ticket';
		$args['uid']  = $ticket->uid;

		$uri = self::getUrl($args, false);
		$uri = $full ? $uri->toString() : JRoute::_($uri->toString(array('path', 'query', 'fragment')));
		$uri = str_replace('/administrator/', '/', $uri);

		return $uri;
	}

	public static function getTicketCheckinRoute($ticket, $full = false)
	{
		$args         = array();
		$args['uid']  = $ticket->uid;
		$args['task'] = 'ticket.checkin';

		$uri = self::getUrl($args, false);

		return $full ? $uri->toString() : JRoute::_($uri->toString(array('path', 'query', 'fragment')));
	}

	public static function getTicketsRoute($bookingId = null, $eventId = null, $my = false)
	{
		$args         = array();
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
		$args         = array();
		$args['task'] = 'ticketform.edit';
		$args['t_id'] = $ticketId;

		if (empty($return)) {
			$return = JUri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getCalendarIcalRoute($calId, $token = '')
	{
		$url = JUri::base();
		$url .= 'index.php?option=com_dpcalendar&task=ical.download&id=' . $calId;

		if ($token) {
			$url .= '&token=' . $token;
		}

		return $url;
	}

	public static function getCalendarRoute($calId)
	{
		if ($calId instanceof JCategoryNode) {
			$id       = $calId->id;
			$calendar = $calId;
		} else {
			$id       = $calId;
			$calendar = DPCalendarHelper::getCalendar($id);
		}

		if ($id == '0') {
			$link = '';
		} else {
			$needles = array(
				'calendar' => array(
					$id
				),
				'list'     => array(
					$id
				),
				'map'      => array(
					$id
				)
			);

			if ($item = self::findItem($needles)) {
				$link = 'index.php?Itemid=' . $item;
			} else {
				// Create the link
				$link = 'index.php?option=com_dpcalendar&view=calendar&id=' . $id;

				if ($calendar) {
					$calIds = array();
					if ($calId instanceof JCategoryNode) {
						$calIds = array_reverse($calendar->getPath());
					} else {
						$calIds[] = $calendar->id;
					}

					$needles = array(
						'calendar' => $calIds,
						'map'      => $calIds,
						'list'     => $calIds
					);

					if ($item = self::findItem($needles)) {
						$link .= '&Itemid=' . $item;
					} else if ($item = self::findItem()) {
						$link .= '&Itemid=' . $item;
					}
				}
			}
		}

		return $link;
	}

	public static function findItem($needles = null)
	{
		$app   = JFactory::getApplication();
		$menus = $app->getMenu('site');

		// Prepare the reverse lookup array.
		if (self::$lookup === null) {
			self::$lookup = array();

			$component = JComponentHelper::getComponent('com_dpcalendar');
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
							self::$lookup[$view] = array();
						}

						$ids = $item->params->get('ids');
						if (!is_array($ids) && $ids) {
							$ids = array(
								$ids
							);
						}
						if (!$ids && isset($item->query['id'])) {
							$ids = array(
								$item->query['id']
							);
						}

						if ($ids === null) {
							$ids = array();
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
			if ($active && $active->component == 'com_dpcalendar' && isset($active->query) && isset($active->query['view']) &&
				isset($needles[$active->query['view']])
			) {
				// Move the actual item to the first position
				$tmp = array(
					$active->query['view'] => $needles[$active->query['view']]
				);
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

	private static function getUrl($arguments = array(), $route = true)
	{
		$uri = clone JUri::getInstance();
		if (JFactory::getDocument()->getType() != 'html') {
			$uri = JUri::getInstance(JUri::root() . 'index.php');
		}
		$uri->setQuery('');
		$input = JFactory::getApplication()->input;

		if ($input->get('option') != 'com_dpcalendar' || strpos($uri->getPath(), 'index.php') !== false) {
			$arguments['option'] = 'com_dpcalendar';

			if ($itemId = self::findItem(array())) {
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
			return JRoute::_($uri->toString(array(
				'path',
				'query',
				'fragment'
			)));
		}

		return $uri;
	}
}
