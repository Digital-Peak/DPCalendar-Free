<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
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
	$root->addChild(new Heading('details-heading', 2))->setContent(JText::_('COM_DPCALENDAR_INVOICE_INVOICE_DETAILS'))->addClass('dpcalendar-heading',
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
$root->addChild(new Heading('details-heading', 2))->setContent(JText::_('COM_DPCALENDAR_INVOICE_BOOKING_DETAILS'))->addClass('dpcalendar-heading', true);

// The details table
$t = $root->addChild(new Table('booking-details', array('', '')));

$fields   = array();
$fields[] = (object)array('id' => 'name', 'name' => 'name');
$fields[] = (object)array('id' => 'email', 'name' => 'email');
$fields[] = (object)array('id' => 'telephone', 'name' => 'telephone');
$fields[] = (object)array('id' => 'country', 'name' => 'country', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL');
$fields[] = (object)array('id' => 'province', 'name' => 'province', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL');
$fields[] = (object)array('id' => 'city', 'name' => 'city', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL');
$fields[] = (object)array('id' => 'zip', 'name' => 'zip', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL');
$fields[] = (object)array('id' => 'street', 'name' => 'street', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL');
$fields[] = (object)array('id' => 'number', 'name' => 'number', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL');

// The fields are not fetched, load them
if (!isset($booking->jcfields)) {
	JPluginHelper::importPlugin('content');
	$booking->text = '';
	JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_dpcalendar.booking', &$booking, &$params, 0));
}

$fields = array_merge($fields, $booking->jcfields);

\DPCalendar\Helper\DPCalendarHelper::sortFields($fields, $params->get('booking_fields_order', new stdClass()));

foreach ($fields as $field) {
	if (!$params->get('booking_show_' . $field->name, 1)) {
		continue;
	}
	$label = 'COM_DPCALENDAR_BOOKING_FIELD_' . strtoupper($field->name) . '_LABEL';

	if (isset($field->label)) {
		$label = $field->label;
	}

	$content = '';
	if (property_exists($booking, $field->name)) {
		$content = $booking->{$field->name};
	}
	if (property_exists($field, 'value')) {
		$content = $field->value;
	}

	$r = $t->addRow(new Row($field->name));
	$r->addCell(new Cell('label'))->setContent(JText::_($label));
	$r->addCell(new Cell('content'))->setContent($content);
}

// The tickets heading
$h = $root->addChild(new Heading('tickets-heading', 2));
$h->setContent(JText::_('COM_DPCALENDAR_INVOICE_TICKET_DETAILS'));
$h->addClass('dpcalendar-heading', true);

// The tickets table
$columns = array(
	JText::_('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'),
	JText::_('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL'),
	JText::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL')
);

if ($params->get('ticket_show_seat', 1)) {
	$columns[] = JText::_('COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL');
}
$t = $root->addChild(new Table('ticket-details', $columns));

// Loop over the tickets
foreach ($tickets as $ticket) {
	if (!empty($ticket->event_prices)) {
		$prices = $ticket->event_prices;

		if (is_string($prices)) {
			$prices = json_decode($ticket->event_prices);
		}

		if (!empty($prices->label[$ticket->type])) {
			// Add an information row
			$r = $t->addRow(new Row($ticket->id . '-heading'));

			// Add the title cell which spans over all columns
			$c = $r->addCell(new Cell('title', array(), array('colspan' => count($columns))));
			$c->addChild(new Heading('title', 4))->setContent($prices->label[$ticket->type]);
		}
	}

	// Add an information row
	$r = $t->addRow(new Row($ticket->id . '-ticket'));

	// Set the cells and their content
	$r->addCell(new Cell('uid'))->setContent($ticket->uid);
	$r->addCell(new Cell('name'))->setContent($ticket->name);
	$r->addCell(new Cell('price'))->setContent(DPCalendarHelper::renderPrice($ticket->price, $params->get('currency_symbol', '$')));

	if ($params->get('ticket_show_seat', 1)) {
		$r->addCell(new Cell('seat'))->setContent($ticket->seat);
	}
}
