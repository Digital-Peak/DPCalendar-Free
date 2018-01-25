<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.helper');

class JHtmlDPCalendaricon
{

	public static function invoice ($booking, $showText = true)
	{
		$icon = 'icon-download';

		$text = '<i class="' . $icon . '"></i> ';
		if ($showText)
		{
			$text .= JText::_('COM_DPCALENDAR_INVOICE');
		}
		$button = JHtml::_('link', JRoute::_('index.php?option=com_dpcalendar&task=booking.invoice&b_id=' . $booking->id), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_INVOICE') . '">' . $button . '</span>';
		return $output;
	}

	public static function invoicesend ($booking, $showText = true)
	{
		$icon = 'icon-mail';

		$text = '<i class="' . $icon . '"></i> ';
		if ($showText)
		{
			$text .= JText::_('COM_DPCALENDAR_SEND');
		}
		$button = JHtml::_('link',
				JRoute::_(
						'index.php?option=com_dpcalendar&task=booking.invoicesend&b_id=' . $booking->id . '&return=' .
								 base64_encode(JUri::getInstance()->toString())), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_SEND') . '">' . $button . '</span>';
		return $output;
	}

	public static function pdfticket ($ticketId, $showText = true)
	{
		$icon = 'icon-download';

		$text = '<i class="' . $icon . '"></i> ';
		if ($showText)
		{
			$text .= JText::_('COM_DPCALENDAR_DOWNLOAD');
		}

		$button = JHtml::_('link', JRoute::_('index.php?option=com_dpcalendar&task=ticket.pdfdownload&uid=' . $ticketId), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_DOWNLOAD') . '">' . $button . '</span>';
		return $output;
	}

	public static function pdfticketsend ($ticketId, $showText = true)
	{
		$icon = 'icon-mail';

		$text = '<i class="' . $icon . '"></i> ';
		if ($showText)
		{
			$text .= JText::_('COM_DPCALENDAR_SEND');
		}

		$button = JHtml::_('link',
				JRoute::_(
						'index.php?option=com_dpcalendar&task=ticket.pdfsend&uid=' . $ticketId . '&return=' .
								 base64_encode(JUri::getInstance()->toString())), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_SEND') . '">' . $button . '</span>';
		return $output;
	}

	public static function booking ($booking)
	{
		if (! $booking)
		{
			return '';
		}

		$icon = 'icon-users';

		$text = '<i class="' . $icon . '"></i> ' . JText::_('COM_DPCALENDAR_TICKET_FIELD_BOOKING_LABEL');
		$button = JHtml::_('link', DPCalendarHelperRoute::getBookingRoute($booking), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_TICKET_FIELD_BOOKING_LABEL') . '">' . $button . '</span>';
		return $output;
	}

	public static function bookingAcceptInvite ($booking, $accept)
	{
		if (! $booking)
		{
			return '';
		}

		$string = $accept ? 'COM_DPCALENDAR_VIEW_BOOKING_INVITE_ACCEPT' : 'COM_DPCALENDAR_VIEW_BOOKING_INVITE_DECLINE';

		$icon = $accept ? 'icon-ok' : 'icon-stop';

		$text = '<i class="' . $icon . '"></i> ' . JText::_($string);
		$button = JHtml::_('link', DPCalendarHelperRoute::getInviteChangeRoute($booking, $accept, false), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_($string) . '">' . $button . '</span>';
		return $output;
	}

	public static function invite ($event)
	{
		if (! $event)
		{
			return '';
		}

		$icon = 'icon-signup';

		$text = '<i class="' . $icon . '"></i> ' . JText::_('COM_DPCALENDAR_INVITE');
		$button = JHtml::_('link', DPCalendarHelperRoute::getInviteRoute($event), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm">' . $button . '</span>';
		return $output;
	}

	public static function editBooking ($booking)
	{
		$icon = 'icon-edit';

		$text = JText::_('JGLOBAL_EDIT');

		$text = '<i class="' . $icon . '"></i> ' . $text;
		$button = JHtml::_('link', DPCalendarHelperRoute::getBookingFormRoute($booking->id), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm">' . $button . '</span>';
		return $output;
	}

	public static function bookings ($event)
	{
		$icon = 'icon-user';

		$text = '<i class="' . $icon . '"></i> ' . JText::_('COM_DPCALENDAR_BOOKING_PROGRESS');
		$button = JHtml::_('link', DPCalendarHelperRoute::getBookingsRoute($event->id), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_BOOKING_PROGRESS') . '">' . $button . '</span>';
		return $output;
	}

	public static function editTicket ($ticket)
	{
		$title = JText::_('JGLOBAL_EDIT');
		$icon = 'icon-edit';

		$text = JText::_('JGLOBAL_EDIT');

		$text = '<i class="' . $icon . '"></i> ' . $text;
		$button = JHtml::_('link', DPCalendarHelperRoute::getTicketFormRoute($ticket->id), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . $title . '">' . $button . '</span>';
		return $output;
	}

	public static function tickets ($event = null, $booking = null)
	{
		$icon = 'icon-signup';

		$text = '<i class="' . $icon . '"></i> ' . JText::_('COM_DPCALENDAR_BOOKING_FIELD_TICKETS_LABEL');
		$button = JHtml::_('link', DPCalendarHelperRoute::getTicketsRoute($booking ? $booking->id : null, $event ? $event->id : null), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_BOOKING_FIELD_TICKETS_LABEL') . '">' . $button . '</span>';
		return $output;
	}

	public static function printWindow ($idToPrint, $showText = true, $small = true)
	{
		JFactory::getDocument()->addScriptDeclaration(
				"function printDiv(divName) {
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = printContents;

     window.print();

     document.body.innerHTML = originalContents;
}");

		$icon = 'icon-print';

		$text = '<i class="' . $icon . '"></i>';
		$button = '<span onclick="printDiv(\'' . $idToPrint . '\');return false;">' . $text . '</span>';
		if ($showText)
		{
			$text .= ' ' . JText::_('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT');
			$button = JHtml::_('link', '#', $text, array(
					'onclick' => "printDiv('" . $idToPrint . "');return false;"
			));
		}
		$output = '<span class="hasTooltip btn btn-default' . ($small ? ' btn-small btn-sm' : '') . '" title="' . JText::_('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT') .
				 '">' . $button . '</span>';
		return $output;
	}

	public static function event ($event)
	{
		$icon = 'icon-info';

		$text = '<i class="' . $icon . '"></i> ' . JText::_('COM_DPCALENDAR_EVENT');
		$button = JHtml::_('link', DPCalendarHelperRoute::getEventRoute($event->id, $event->catid), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_EVENT') . '">' . $button . '</span>';
		return $output;
	}

	public static function emailEvent ($event)
	{
		require_once JPATH_SITE . '/components/com_mailto/helpers/mailto.php';

		$uri = JUri::getInstance();
		$base = $uri->toString(array(
				'scheme',
				'host',
				'port'
		));
		$template = JFactory::getApplication()->getTemplate();
		$link = $base . DPCalendarHelperRoute::getEventRoute($event->id, $event->catid, false, true);
		$url = 'index.php?option=com_mailto&tmpl=component&template=' . $template . '&link=' . MailToHelper::addLink($link);

		$status = 'width=400,height=350,menubar=yes,resizable=yes';

		$text = '<span class="icon-envelope"></span> ';

		$output = '<a class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('JGLOBAL_EMAIL') .
				 '" onclick="window.open(\'' . $url . '\',\'win2\',\'' . $status . '\'); return false;">' . $text . '</a>';

		return $output;
	}

	public static function create ($event, $params)
	{
		$uri = JFactory::getURI();

		$url = JRoute::_(DPCalendarHelperRoute::getFormRoute(0, $uri));
		$text = '<i class="icon-plus"></i> ' . JText::_('JNEW');
		$button = JHtml::_('link', $url, $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_VIEW_FORM_SUBMIT_EVENT') . '">' . $button . '</span>';
		return $output;
	}

	public static function edit ($event)
	{
		$user = JFactory::getUser();
		$uri = JFactory::getURI();

		if ($event->state < 0)
		{
			return;
		}

		JHtml::_('behavior.tooltip');
		$url = DPCalendarHelperRoute::getFormRoute($event->id, $uri);
		$text = '<i class="icon-edit"></i> ' . JText::_('JGLOBAL_EDIT');

		if ($event->state == 0)
		{
			$overlib = JText::_('JUNPUBLISHED');
		}
		else
		{
			$overlib = JText::_('JPUBLISHED');
		}

		$date = JHtml::_('date', $event->created);
		$author = $event->created_by_alias ? $event->created_by_alias : $event->author;

		$overlib .= '&lt;br /&gt;';
		$overlib .= $date;
		$overlib .= '&lt;br /&gt;';
		$overlib .= htmlspecialchars($author, ENT_COMPAT, 'UTF-8');

		$button = JHtml::_('link', JRoute::_($url), $text);

		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_VIEW_FORM_BUTTON_EDIT_EVENT') . ' :: ' . $overlib . '">' .
				 $button . '</span>';

		return $output;
	}

	public static function delete ($event)
	{
		JHtml::_('behavior.tooltip');
		$text = '<i class="icon-delete icon-remove"></i> ' . JText::_('COM_DPCALENDAR_DELETE');

		if ($event->state == 0)
		{
			$overlib = JText::_('JUNPUBLISHED');
		}
		else
		{
			$overlib = JText::_('JPUBLISHED');
		}

		$date = JHtml::_('date', $event->created);
		$author = $event->created_by_alias ? $event->created_by_alias : $event->author;

		$overlib .= '&lt;br /&gt;';
		$overlib .= $date;
		$overlib .= '&lt;br /&gt;';
		$overlib .= htmlspecialchars($author, ENT_COMPAT, 'UTF-8');

		$return = clone JFactory::getURI();
		if (JFactory::getApplication()->input->getCmd('view', null) == 'event')
		{
			$return->setVar('layout', 'empty');
		}

		$link = 'index.php?option=com_dpcalendar&task=event.delete&e_id=' . $event->id . '&tmpl=' . JFactory::getApplication()->input->getWord('tmpl') . '&return=' .
				 base64_encode($return);
		$button = JHtml::_('link', JRoute::_($link), $text);

		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_VIEW_FORM_BUTTON_DELETE_EVENT') . ' :: ' . $overlib . '">' .
				 $button . '</span>';

		return $output;
	}

	public static function deleteSeries ($originalEventId)
	{
		if ($originalEventId < 1)
		{
			return null;
		}

		$return = clone JFactory::getURI();
		if (JFactory::getApplication()->input->getCmd('view', null) == 'event')
		{
			$return->setVar('layout', 'empty');
		}

		$icon = 'icon-delete';

		$text = '<i class="' . $icon . '"></i> ' . JText::_('COM_DPCALENDAR_DELETE_SERIES');
		$link = 'index.php?option=com_dpcalendar&task=event.delete&e_id=' . $originalEventId . '&tmpl=' . JFactory::getApplication()->input->getWord('tmpl') . '&return=' .
				 base64_encode($return);
		$button = JHtml::_('link', JRoute::_($link), $text);
		$output = '<span class="hasTooltip btn btn-small btn-default btn-sm" title="' . JText::_('COM_DPCALENDAR_DELETE_SERIES') . '">' . $button . '</span>';
		return $output;
	}

	public static function editLocation ($location)
	{
		$user = JFactory::getUser();
		$uri = JFactory::getURI();

		if ($location->state < 0)
		{
			return;
		}

		JHtml::_('behavior.tooltip');
		$url = DPCalendarHelperRoute::getLocationFormRoute($location->id, $uri);
		$text = '<i class="icon-edit"></i> ';

		if ($location->state == 0)
		{
			$overlib = JText::_('JUNPUBLISHED');
		}
		else
		{
			$overlib = JText::_('JPUBLISHED');
		}

		$date = JHtml::_('date', $location->created);
		$author = $location->created_by_alias ? $location->created_by_alias : JFactory::getUser($location->created_by)->name;

		$overlib .= '&lt;br /&gt;';
		$overlib .= $date;
		$overlib .= '&lt;br /&gt;';
		$overlib .= htmlspecialchars($author, ENT_COMPAT, 'UTF-8');

		$button = JHtml::_('link', JRoute::_($url), $text);

		$output = '<small class="hasTooltip" title="' . JText::_('JGLOBAL_EDIT') . ' :: ' . $overlib . '">' . $button . '</small>';

		return $output;
	}
}
