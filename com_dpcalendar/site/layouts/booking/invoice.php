<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Basic\Image;
use CCL\Content\Element\Basic\Table;
use CCL\Content\Element\Basic\Table\Row;
use CCL\Content\Element\Basic\Table\Cell;

// The booking
$booking = $displayData['booking'];
if (!$booking) {
	return;
}

// The tickets
$tickets = $displayData['tickets'];
if (!$tickets) {
	return;
}

// The params
$params = $displayData['params'];
if (!$params) {
	$params = clone JComponentHelper::getParams('com_dpcalendar');
}

/** @var \CCL\Content\Element\Basic\Container $root */
$root = $displayData['root'];

// Load the DPCalendar language
JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

// Get the user of the booking
$user = JFactory::getUser($booking->user_id);

// Load the plugin
$plugin = JPluginHelper::getPlugin('dpcalendarpay', $booking->processor);
if ($plugin) {
	// Load the language of the plugin
	JFactory::getLanguage()->load('plg_dpcalendarpay_' . $booking->processor, JPATH_PLUGINS . '/dpcalendarpay/' . $booking->processor);
}

// Does the booking have a price
$hasPrice = $booking->price && $booking->price != '0.00';

// Determine the tickets which do belong to the booking
$booking->amount_tickets = 0;
foreach ($tickets as $ticket) {
	if ($ticket->booking_id == $booking->id) {
		$booking->amount_tickets++;
	}
}

// The header table with the address and image from the component params
if ($params->get('show_header', true)) {
	// The full url is needed for PDF compiling
	$imageUrl = $params->get('invoice_logo');
	if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
		$imageUrl = trim(JUri::root(), '/') . '/' . trim($imageUrl, '/');
	}

	// The table
	$t = $root->addChild(new Table('header', array('', '')));

	// The row
	$r = $t->addRow(new Row('row'));

	// The address cell
	$r->addChild(new Cell('address'))->setContent(nl2br($params->get('invoice_address')));

	// The image cell
	$r->addChild(new Cell('image'))->setContent($imageUrl ? new Image('image', $imageUrl) : null);
}

// Show an invoice part when the booking has a price
if ($hasPrice) {
	// Add the header
	$root->addChild(new Heading('details-heading', 2))->setContent(JText::_('COM_DPCALENDAR_INVOICE_INVOICE_DETAILS'))->addClass('dp-event-header',
		true);

	// The details table
	$t = $root->addChild(new Table('invoice-details', array('', '')));

	// Add an information row
	$r = $t->addRow(new Row('invoice'));
	$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_INVOICE_NUMBER'));
	$r->addCell(new Cell('content'))->setContent($booking->uid);

	// Add an information row
	$r = $t->addRow(new Row('invoice-date'));
	$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_INVOICE_DATE'));
	$r->addCell(new Cell('content'))->setContent(DPCalendarHelper::getDate($booking->book_date)->format($params->get('event_date_format',
			'm.d.Y') . ' ' . $params->get('event_time_format', 'g:i a')));

	// Add an information row
	$r = $t->addRow(new Row('price'));
	$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'));
	$r->addCell(new Cell('content'))->setContent(DPCalendarHelper::renderPrice($booking->price, $params->get('currency_symbol', '$')));

	// Add an information row
	$r = $t->addRow(new Row('tickets'));
	$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_TICKETS_LABEL'));
	$r->addCell(new Cell('content'))->setContent($booking->amount_tickets);

	// Add an information row
	$r = $t->addRow(new Row('status'));
	$r->addCell(new Cell('label'))->setContent(JText::_('JSTATUS'));
	$r->addCell(new Cell('content'))->setContent(\DPCalendar\Helper\Booking::getStatusLabel($booking));
}

// The booking details heading
$root->addChild(new Heading('details-heading', 2))->setContent(JText::_('COM_DPCALENDAR_INVOICE_BOOKING_DETAILS'))->addClass('dp-event-header', true);

// The details table
$t = $root->addChild(new Table('booking-details', array('', '')));

// Add an information row
$r = $t->addRow(new Row('name'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL'));
$r->addCell(new Cell('content'))->setContent($booking->name);

// Add an information row
$r = $t->addRow(new Row('email'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_EMAIL_LABEL'));
$r->addCell(new Cell('content'))->setContent($booking->email);

// Add an information row
$r = $t->addRow(new Row('telephone'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_TELEPHONE_LABEL'));
$r->addCell(new Cell('content'))->setContent($booking->telephone);

// Add an information row
$r = $t->addRow(new Row('country'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'));
$r->addCell(new Cell('content'))->setContent($booking->country);

// Add an information row
$r = $t->addRow(new Row('province'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL'));
$r->addCell(new Cell('content'))->setContent($booking->province);

// Add an information row
$r = $t->addRow(new Row('city'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL'));
$r->addCell(new Cell('content'))->setContent($booking->zip . ' ' . $booking->city);

// Add an information row
$r = $t->addRow(new Row('street'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL'));
$r->addCell(new Cell('content'))->setContent($booking->street . ' ' . $booking->number);

// The fields are not fetched, load them
if (!isset($booking->jcfields)) {
	JPluginHelper::importPlugin('content');
	$booking->text = '';
	JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_dpcalendar.booking', &$booking, &$params, 0));
}

// The fields table
$t = $root->addChild(new Table('booking-fields', array('', '')));
if (!empty($booking->jcfields)) {
	// Loop over the fields
	foreach ($booking->jcfields as $field) {
		// Add an information row for the field
		$r = $t->addRow(new Row($field->id));
		$r->addCell(new Cell('label'))->setContent($field->label);
		$r->addCell(new Cell('content'))->setContent(!empty($field->value) ? $field->value : '');
	}
}

// The tickets heading
$h = $root->addChild(new Heading('tickets-heading', 2));
$h->setContent(JText::_('COM_DPCALENDAR_INVOICE_TICKET_DETAILS'));
$h->addClass('dp-event-header', true);

// The tickets table
$t = $root->addChild(
	new Table(
		'ticket-details',
		array(
			JText::_('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'),
			JText::_('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL'),
			JText::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'),
			JText::_('COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL')
		)
	)
);

// Loop over the tickets
foreach ($tickets as $ticket) {
	// Add an information row
	$r = $t->addRow(new Row($ticket->id . '-ticket'));

	// Set the cells and their content
	$r->addCell(new Cell('uid'))->setContent($ticket->uid);
	$r->addCell(new Cell('name'))->setContent($ticket->name);
	$r->addCell(new Cell('price'))->setContent(DPCalendarHelper::renderPrice($ticket->price, $params->get('currency_symbol', '$')));
	$r->addCell(new Cell('seat'))->setContent($ticket->seat);
}
