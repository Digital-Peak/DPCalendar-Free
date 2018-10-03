<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Router;

class Router
{
	public function route($url, $xhtml = true)
	{
		return \JRoute::_($url, $xhtml);
	}

	public function getCalendarRoute($calId)
	{
		return \JRoute::_(\DPCalendarHelperRoute::getCalendarRoute($calId));
	}

	public function getCalendarIcalRoute($calId, $token = '')
	{
		return \DPCalendarHelperRoute::getCalendarIcalRoute($calId, $token);
	}

	public function getEventRoute($id, $calId, $full = false, $autoRoute = true, $defaultItemId = 0)
	{
		return \DPCalendarHelperRoute::getEventRoute($id, $calId, $full, $autoRoute, $defaultItemId);
	}

	public function getEventFormRoute($id, $return = null, $append = null)
	{
		return \JRoute::_(\DPCalendarHelperRoute::getFormRoute($id, $return, $append));
	}

	public function getEventDeleteRoute($id, $return = null)
	{
		return \JRoute::_('index.php?option=com_dpcalendar&task=event.delete&e_id=' . $id . '&return=' . base64_encode($return));
	}

	public function getLocationRoute($location)
	{
		return \DPCalendarHelperRoute::getLocationRoute($location);
	}

	public function getLocationFormRoute($id, $return = null)
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

	public function getBookingFormRouteFromEvent($event, $return = null)
	{
		return \DPCalendarHelperRoute::getBookingFormRouteFromEvent($event, $return);
	}

	public static function getTicketRoute($ticket, $full = false)
	{
		return \DPCalendarHelperRoute::getTicketRoute($ticket, $full);
	}

	public static function getTicketFormRoute($ticketId, $return = null)
	{
		return \DPCalendarHelperRoute::getTicketFormRoute($ticketId, $return);
	}
}
