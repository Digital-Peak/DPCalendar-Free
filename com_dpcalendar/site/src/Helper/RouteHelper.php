<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Helper;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class RouteHelper
{
	private static ?array $lookup = null;

	/**
	 * @param string|int $id
	 */
	public static function getEventRoute($id, ?string $calId = '', ?bool $full = false, ?bool $autoRoute = true, ?int $defaultItemId = 0): string
	{
		// Check if we come from com_tags where the link is generated id:alias
		$parts = $id ? explode(':', (string)$id) : [];
		if (\count($parts) == 2 && is_numeric($parts[0])) {
			$id = (int)$id;
		}

		// Create the link
		$link = 'index.php?option=com_dpcalendar&view=event&id=' . $id;
		if ($tmpl = Factory::getApplication()->getInput()->getWord('tmpl')) {
			$link .= '&tmpl=' . $tmpl;
		}

		$link .= '&calid=' . $calId;
		if ($defaultItemId !== null && $defaultItemId !== 0) {
			$link .= '&force_item_id=' . $defaultItemId;
		}

		if ($autoRoute !== true) {
			return ($full === true ? Uri::root() : '') . $link;
		}

		return Route::link('site', $link, false, Route::TLS_IGNORE, $full ?? false);
	}

	public static function getFormRoute(?string $id, ?string $return = null, ?string $append = null): string
	{
		if ($id !== null && $id !== '' && $id !== '0') {
			$link = 'index.php?option=com_dpcalendar&task=event.edit&e_id=' . $id;
		} elseif (Factory::getApplication()->isClient('administrator')) {
			$link = 'index.php?option=com_dpcalendar&task=event.add&e_id=0';
		} else {
			$link = 'index.php?option=com_dpcalendar&view=form&e_id=0';
		}

		$itemId = Factory::getApplication()->getInput()->get('Itemid', null);
		if (!empty($itemId)) {
			$link .= '&Itemid=' . $itemId;
		}

		if ($append !== null && $append !== '' && $append !== '0') {
			$link .= '&' . $append;
		}
		if (Factory::getApplication()->getInput()->getWord('tmpl')) {
			$link .= '&tmpl=' . Factory::getApplication()->getInput()->getWord('tmpl');
		}
		if ($return !== null && $return !== '' && $return !== '0') {
			$link .= '&return=' . base64_encode($return);
		}

		return $link;
	}

	public static function getLocationRoute(\stdClass $location, ?bool $full = false): string
	{
		// Create the link
		$link = ($full === true ? Uri::root() : '') . 'index.php?option=com_dpcalendar&view=location&id=' . $location->id;

		if ($tmpl = Factory::getApplication()->getInput()->getWord('tmpl')) {
			$link .= '&tmpl=' . $tmpl;
		}

		return Route::_($link, false);
	}

	public static function getLocationFormRoute(?string $id, ?string $return = null): string
	{
		if ($id !== null && $id !== '' && $id !== '0') {
			$link = 'index.php?option=com_dpcalendar&task=locationform.edit&l_id=' . $id;
		} else {
			$link = 'index.php?option=com_dpcalendar&view=locationform&l_id=0';
		}

		if (Factory::getApplication()->getInput()->getWord('tmpl')) {
			$link .= '&tmpl=' . Factory::getApplication()->getInput()->getWord('tmpl');
		}

		if ($return !== null && $return !== '' && $return !== '0') {
			$link .= '&return=' . base64_encode($return);
		}

		return $link;
	}

	public static function getBookingRoute(\stdClass $booking, ?bool $full = false): string
	{
		$args         = [];
		$args['view'] = 'booking';
		$args['uid']  = $booking->uid;

		if (!empty($booking->token)) {
			$args['token'] = $booking->token;
		}

		$uri = self::getUrl($args, false);

		$url = Route::_($uri->toString(['path', 'query', 'fragment']), false);
		if ($full === true) {
			$url = Uri::getInstance()->toString(['host', 'port', 'scheme']) . Route::_('index.php' . $uri->toString(['query', 'fragment']), false);
		}

		// When a booking is created on the back end it contains the administrator part
		return str_replace('/administrator/', '/', $url);
	}

	public static function getBookingsRoute(?string $eventId): string
	{
		$args         = [];
		$args['view'] = 'bookings';

		if ($eventId !== null && $eventId !== '' && $eventId !== '0') {
			$args['e_id'] = $eventId;
		}

		return self::getUrl($args, true);
	}

	public static function getInviteRoute(\stdClass $event, ?string $return = null): string
	{
		$args         = [];
		$args['view'] = 'invite';
		$args['id']   = $event->id;
		if ($return === null || $return === '' || $return === '0') {
			$return = Uri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getInviteChangeRoute(\stdClass $booking, ?bool $accept, ?bool $full): string
	{
		$args           = [];
		$args['task']   = 'booking.invite';
		$args['uid']    = $booking->uid;
		$args['accept'] = $accept === true ? '1' : '0';

		if ($booking->token) {
			$args['token'] = $booking->token;
		}

		$uri = self::getUrl($args, false);

		$url = Route::_($uri->toString(['path', 'query', 'fragment']), false);
		if ($full === true) {
			$url = Uri::getInstance()->toString(['host', 'port', 'scheme']) . Route::_('index.php' . $uri->toString(['query', 'fragment']), false);
		}

		// When a booking is created on the back end it contains the administrator part
		return str_replace('/administrator/', '/', $url);
	}

	public static function getBookingFormRoute(\stdClass $booking, ?string $return = null): string
	{
		$args         = [];
		$args['task'] = 'bookingform.edit';
		$args['b_id'] = $booking->id;

		if (!empty($booking->token)) {
			$args['token'] = $booking->token;
		}

		if ($return === null || $return === '' || $return === '0') {
			$return = Uri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getBookingFormRouteFromEvent(\stdClass $event, ?string $return = null, ?bool $autoRoute = true, ?int $defaultItemId = 0): string
	{
		$args         = [];
		$args['task'] = 'bookingform.add';
		$args['e_id'] = $event->id;
		if ($return === null || $return === '' || $return === '0') {
			$return = self::getEventRoute($event->id, $event->catid);
		}
		$args['return'] = base64_encode($return);

		$needles             = ['event' => [$event->id]];
		$needles['calendar'] = [$event->catid];
		$needles['list']     = [$event->catid];
		$needles['map']      = [$event->catid];

		return self::getUrl($args, $autoRoute ?? true, $needles, $defaultItemId ?? 0);
	}

	public static function getTicketRoute(\stdClass $ticket, ?bool $full = false): string
	{
		$args         = [];
		$args['view'] = 'ticket';
		$args['uid']  = $ticket->uid;

		if (!empty($ticket->booking_token)) {
			$args['token'] = $ticket->booking_token;
		}

		$url = Route::link('site', 'index.php' . self::getUrl($args, false)->toString(['query', 'fragment']), false);
		if ($full === true) {
			return Uri::getInstance()->toString(['host', 'port', 'scheme']) . $url;
		}

		return $url;
	}

	public static function getTicketCheckinRoute(\stdClass $ticket, ?bool $full = false): string
	{
		$args         = [];
		$args['uid']  = $ticket->uid;
		$args['task'] = 'ticket.checkin';

		$uri = self::getUrl($args, false);

		$url = Route::_($uri->toString(['path', 'query', 'fragment']), false);
		if ($full === true) {
			$url = Uri::getInstance()->toString(['host', 'port', 'scheme']) . Route::_('index.php' . $uri->toString(['query', 'fragment']), false);
		}

		// When a Ticket urls is created on the back end it contains the administrator part
		return str_replace('/administrator/', '/', $url);
	}

	public static function getTicketsRoute(?string $bookingId = null, ?string $eventId = null, ?bool $my = false): string
	{
		$args         = [];
		$args['view'] = 'tickets';

		if ($bookingId !== null && $bookingId !== '' && $bookingId !== '0') {
			$args['b_id'] = $bookingId;
		}
		if ($eventId !== null && $eventId !== '' && $eventId !== '0') {
			$args['e_id'] = $eventId;
		}
		if ($my === true) {
			$args['filter[my]'] = 1;
		}

		return self::getUrl($args, true);
	}

	public static function getTicketFormRoute(string $ticketId, ?string $return = null): string
	{
		$args         = [];
		$args['task'] = 'ticketform.edit';
		$args['t_id'] = $ticketId;

		if ($return === null || $return === '' || $return === '0') {
			$return = Uri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getTicketDeleteRoute(string $ticketId, ?string $return = null): string
	{
		$args         = [];
		$args['task'] = 'ticketform.delete';
		$args['t_id'] = $ticketId;

		if ($return === null || $return === '' || $return === '0') {
			$return = Uri::getInstance()->toString();
		}
		$args['return'] = base64_encode($return);

		return self::getUrl($args, true);
	}

	public static function getCalendarIcalRoute(string $calId, ?string $token = ''): string
	{
		$url = Uri::base();
		$url .= 'index.php?option=com_dpcalendar&task=ical.download&id=' . $calId;

		if ($token !== null && $token !== '' && $token !== '0') {
			$url .= '&token=' . $token;
		}

		return $url;
	}

	public static function getCalendarRoute(string $calId): string
	{
		$id       = $calId;
		$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($id);

		if ($id === '0') {
			$link = '';
		} else {
			$needles = ['calendar' => [$id], 'list' => [$id], 'map' => [$id]];

			if (($item = self::findItem($needles)) !== '' && ($item = self::findItem($needles)) !== '0') {
				$link = 'index.php?Itemid=' . $item;
			} else {
				// Create the link
				$link = 'index.php?option=com_dpcalendar&view=calendar&id=' . $id;

				if ($calendar instanceof CalendarInterface) {
					$calIds = [$calendar->getId()];

					$needles = ['calendar' => $calIds, 'map' => $calIds, 'list' => $calIds];

					if (($item = self::findItem($needles)) !== '' && ($item = self::findItem($needles)) !== '0') {
						$link .= '&Itemid=' . $item;
					} elseif (($item = self::findItem()) !== '' && ($item = self::findItem()) !== '0') {
						$link .= '&Itemid=' . $item;
					}
				}
			}
		}

		return $link;
	}

	public static function getCategoryRoute(?string $catid): string
	{
		// Is needed for smart search categories indexing
		return self::getCalendarRoute($catid !== null && $catid !== '' && $catid !== '0' ? $catid : '');
	}

	public static function findItem(array $needles = []): string
	{
		$app = Factory::getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return '';
		}

		$menus = $app->getMenu('site');
		if (!$menus instanceof AbstractMenu) {
			return '';
		}

		// Prepare the reverse lookup array.
		if (self::$lookup === null) {
			self::$lookup = [];

			$component = ComponentHelper::getComponent('com_dpcalendar');
			$items     = $menus->getItems('component_id', $component->id);
			if (!\is_array($items)) {
				$items = [$items];
			}

			if ($items !== []) {
				// The active item should be moved to the last position that it doesn't get overwritten
				$active = $menus->getActive();
				if ($active && $active->component == 'com_dpcalendar') {
					$items[] = $active;
				}

				foreach ($items as $item) {
					if (!isset($item->query['view'])) {
						continue;
					}

					$view = $item->query['view'];

					if (!isset(self::$lookup[$view])) {
						self::$lookup[$view] = [];
					}

					$ids = $item->getParams()->get('ids');
					if (!\is_array($ids) && $ids) {
						$ids = [$ids];
					}

					if (!$ids && isset($item->query['id'])) {
						$ids = [$item->query['id']];
					}

					if ($view === 'event' && isset($item->query['id'])) {
						self::$lookup[$view][$item->query['id']] = $item->id;
					}

					if (empty($ids)) {
						continue;
					}

					foreach ($ids as $id) {
						$root = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($id);
						if (!$root instanceof CalendarInterface && $view !== 'location') {
							continue;
						}
						self::$lookup[$view][$id] = $item->id;
						if (!$root instanceof CalendarInterface) {
							continue;
						}

						foreach ($root->getChildren(true) as $child) {
							self::$lookup[$view][$child->getId()] = $item->id;
						}
					}
				}
			}
		}

		if ($needles !== []) {
			$active = $menus->getActive();
			if ($active
				&& $active->component == 'com_dpcalendar'
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
				return (string)$active->id;
			}
		}

		return '';
	}

	/**
	 * @return ($route is true ? string : Uri)
	 */
	private static function getUrl(array $arguments = [], bool $route = true, array $needles = [], int $defaultItemId = 0)
	{
		$app = Factory::getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return '';
		}

		$uri = clone Uri::getInstance();
		if (!$app->getDocument() instanceof HtmlDocument || $app->isClient('site')) {
			$uri = Uri::getInstance('index.php');
		}

		$uri->setQuery('');
		$input = $app->getInput();

		if ($input->get('option') != 'com_dpcalendar' || str_contains($uri->getPath(), 'index.php')) {
			$arguments['option'] = 'com_dpcalendar';

			if (($itemId = self::findItem($needles)) !== '' && ($itemId = self::findItem($needles)) !== '0') {
				$arguments['Itemid'] = $itemId;
			}
		}

		$tmpl = $input->getWord('tmpl');
		if ($tmpl) {
			$arguments['tmpl'] = $tmpl;
		}

		if ($defaultItemId !== 0) {
			$arguments['Itemid'] = $defaultItemId;
		}

		foreach ($arguments as $key => $value) {
			$uri->setVar($key, $value);
		}

		if ($route) {
			return Route::link('site', $uri->toString(['path', 'query', 'fragment']));
		}

		return $uri;
	}
}
