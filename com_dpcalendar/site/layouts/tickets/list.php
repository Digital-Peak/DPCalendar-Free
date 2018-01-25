<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Table;
use CCL\Content\Element\Basic\Table\Row;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\Table\Cell;

$tickets = $displayData['tickets'];
if (!$tickets) {
	return;
}

$params = $displayData['params'];
if (!$params) {
	$params = clone JComponentHelper::getParams('com_dpcalendar');
}

$root = new Container('locations');
if (isset($displayData['root'])) {
	$root = $displayData['root'];
}

$hasPrice = false;
foreach ($tickets as $ticket) {
	if ($ticket->price && $ticket->price != '0.00') {
		$hasPrice = true;
		break;
	}
}
$limited = $params->get('event_show_tickets') == '2';

// Create the columns
$columns = array();
if (!$limited) {
	$columns[] = JText::_('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL');
}

if (!$limited && $params->get('display_list_event', true)) {
	$columns[] = JText::_('COM_DPCALENDAR_EVENT');
}

if (!$limited && $params->get('display_list_date', true)) {
	$columns[] = JText::_('COM_DPCALENDAR_DATE');
}

if (!$limited) {
	$columns[] = JText::_('COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE');
}

$columns[] = JText::_('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL');
$columns[] = JText::_('COM_DPCALENDAR_LOCATION');

if (!$limited) {
	$columns[] = JText::_('COM_DPCALENDAR_CREATED_DATE');
}

if (!$limited && $params->get('ticket_show_seat', 1)) {
	$columns[] = JText::_('COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL');
}

if ($hasPrice && !$limited) {
	$columns[] = JText::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL');
}

/** @var Table * */
$t = $root->addChild(new Table('table', $columns));
foreach ($tickets as $ticket) {
	$row = $t->addRow(new Row($ticket->id));

	if (!$limited) {
		$cell = $row->addCell(new Cell('id'));
		if ($ticket->params->get('access-edit')) {
			$cell->addChild(
				new Link('link-edit', DPCalendarHelperRoute::getTicketFormRoute($ticket->id)))->setContent(new Icon('link-edit-icon', Icon::EDIT)
			);
		}
		$l = $cell->addChild(new Link('link', DPCalendarHelperRoute::getTicketRoute($ticket, true)));

		// Define the content
		$content = JHtmlString::abridge($ticket->uid, 15, 5);

		if (!empty($ticket->event_prices)) {
			$prices = json_decode($ticket->event_prices);

			if (!empty($prices->label[$ticket->type])) {
				$content = $prices->label[$ticket->type];
			}
		}
		$l->setContent($content);
	}

	if (!$limited && $params->get('display_list_event', true)) {
		$cell = $row->addCell(new Cell('event'));
		$l    = $cell->addChild(new Link('event-link', DPCalendarHelperRoute::getEventRoute($ticket->event_id, $ticket->event_calid)));
		$l->setContent($ticket->event_title);
	}

	if (!$limited && $params->get('display_list_date', true)) {
		$row->addCell(new Cell('date'))->setContent(DPCalendarHelper::getDateStringFromEvent($ticket));
	}

	if (!$limited) {
		$row->addCell(new Cell('status'))->setContent(\DPCalendar\Helper\Booking::getStatusLabel($ticket));
	}

	$row->addCell(new Cell('name'))->setContent($ticket->name);
	$row->addCell(new Cell('location'))->setContent(\DPCalendar\Helper\Location::format(array($ticket)));

	if (!$limited) {
		$date = DPCalendarHelper::getDate($ticket->created);
		$date = $date->format($params->get('event_date_format', 'm.d.Y') . ' ' . $params->get('event_time_format', 'g:i a'), true);
		$c    = $row->addCell(new Cell('date-created'))->setContent($date);
	}

	if (!$limited && $params->get('ticket_show_seat', 1)) {
		$row->addCell(new Cell('seat'))->setContent($ticket->seat);
	}

	if ($hasPrice && !$limited) {
		$row->addCell(new Cell('price'))->setContent(DPCalendarHelper::renderPrice($ticket->price));
	}
}
