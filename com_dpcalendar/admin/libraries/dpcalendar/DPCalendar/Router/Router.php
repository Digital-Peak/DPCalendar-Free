<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Router;

use Joomla\CMS\Router\Route;

class Router
{
	public function route($url, $xhtml = true)
	{
		return Route::_($url, $xhtml);
	}

	public function getCalendarRoute($calId)
	{
		return Route::_(\DPCalendarHelperRoute::getCalendarRoute($calId));
	}

	public function getCalendarIcalRoute(string $calId, ?string $token = ''): string
	{
		return \DPCalendarHelperRoute::getCalendarIcalRoute($calId, $token);
	}

	public function getEventRoute($id, ?string $calId = '', $full = false, $autoRoute = true, $defaultItemId = 0)
	{
		return \DPCalendarHelperRoute::getEventRoute($id, $calId, $full, $autoRoute, $defaultItemId);
	}

	public function getEventFormRoute(?string $id, $return = null, $append = null, $xhtml = true)
	{
		return Route::_(\DPCalendarHelperRoute::getFormRoute($id, $return, $append), $xhtml);
	}

	public function getEventDeleteRoute(string $id, $return = null)
	{
		return Route::_('index.php?option=com_dpcalendar&task=event.delete&e_id=' . $id . '&return=' . base64_encode($return));
	}

	public function getLocationRoute($location)
	{
		return \DPCalendarHelperRoute::getLocationRoute($location);
	}

	public function getLocationFormRoute(?string $id, $return = null): string
	{
		return \DPCalendarHelperRoute::getLocationFormRoute($id, $return);
	}

	public static function getBookingRoute($booking, $full = false)
	{
		return \DPCalendarHelperRoute::getBookingRoute($booking, $full);
	}

	public static function getBookingFormRoute($bookingId, $return = null)
	{
		return \DPCalendarHelperRoute::getBookingFormRoute($bookingId, $return);
	}

	public function getBookingFormRouteFromEvent($event, $return = null, $autoRoute = true, $defaultItemId = 0)
	{
		return \DPCalendarHelperRoute::getBookingFormRouteFromEvent($event, $return, $autoRoute, $defaultItemId);
	}

	public static function getTicketRoute($ticket, $full = false)
	{
		return \DPCalendarHelperRoute::getTicketRoute($ticket, $full);
	}

	public static function getTicketFormRoute($ticketId, $return = null)
	{
		return \DPCalendarHelperRoute::getTicketFormRoute($ticketId, $return);
	}

	public static function getTicketDeleteRoute($ticketId, $return = null)
	{
		return \DPCalendarHelperRoute::getTicketDeleteRoute($ticketId, $return);
	}
}
