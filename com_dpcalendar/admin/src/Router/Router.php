<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Router;

use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Router\Route;

class Router
{
	public function route(string $url, ?bool $xhtml = true): string
	{
		return Route::_($url, $xhtml ?? true);
	}

	public function getCalendarRoute(string $calId): string
	{
		return Route::_(RouteHelper::getCalendarRoute($calId));
	}

	public function getCalendarIcalRoute(string $calId, ?string $token = ''): string
	{
		return RouteHelper::getCalendarIcalRoute($calId, $token);
	}

	/**
	 * @param string|int $id
	 */
	public static function getEventRoute($id, ?string $calId = '', bool $full = false, bool $autoRoute = true, ?int $defaultItemId = 0): string
	{
		return RouteHelper::getEventRoute($id, $calId, $full, $autoRoute, $defaultItemId);
	}

	public function getEventFormRoute(?string $id, ?string $return = null, ?string $append = null, ?bool $xhtml = true): string
	{
		return Route::_(RouteHelper::getFormRoute($id, $return, $append), is_bool($xhtml) ? $xhtml : true);
	}

	public function getEventDeleteRoute(string $id, ?string $return = null): string
	{
		return Route::_('index.php?option=com_dpcalendar&task=event.delete&e_id=' . $id . '&return=' . base64_encode($return !== null && $return !== '' && $return !== '0' ? $return : ''));
	}

	/**
	 * @param \stdClass $location
	 */
	public function getLocationRoute(object $location): string
	{
		return RouteHelper::getLocationRoute($location);
	}

	public function getLocationFormRoute(?string $id, ?string $return = null): string
	{
		return RouteHelper::getLocationFormRoute($id, $return);
	}

	/**
	 * @param \stdClass $booking
	 */
	public static function getBookingRoute(object $booking, ?bool $full = false): string
	{
		return RouteHelper::getBookingRoute($booking, $full);
	}

	/**
	 * @param \stdClass $booking
	 */
	public static function getBookingFormRoute(object $booking, ?string $return = null): string
	{
		return RouteHelper::getBookingFormRoute($booking, $return);
	}

	/**
	 * @param \stdClass $event
	 */
	public function getBookingFormRouteFromEvent(object $event, ?string $return = null, ?bool $autoRoute = true, ?int $defaultItemId = 0): string
	{
		return RouteHelper::getBookingFormRouteFromEvent($event, $return, $autoRoute, $defaultItemId);
	}

	/**
	 * @param \stdClass $ticket
	 */
	public static function getTicketRoute(object $ticket, ?bool $full = false): string
	{
		return RouteHelper::getTicketRoute($ticket, $full);
	}

	public static function getTicketFormRoute(string $ticketId, ?string $return = null): string
	{
		return RouteHelper::getTicketFormRoute($ticketId, $return);
	}

	public static function getTicketDeleteRoute(string $ticketId, ?string $return = null): string
	{
		return RouteHelper::getTicketDeleteRoute($ticketId, $return);
	}
}
