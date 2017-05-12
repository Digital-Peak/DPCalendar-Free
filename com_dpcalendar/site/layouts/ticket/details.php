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
use CCL\Content\Element\Basic\Paragraph;

// The ticket
$ticket = $displayData['ticket'];
if (!$ticket) {
	return;
}

// The event
$event = $displayData['event'];
if (!$event) {
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

// Does the booking have a price
$hasPrice = $ticket->price && $ticket->price != '0.00';

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

// Add the header
$root->addChild(new Heading('event-heading', 2))->setContent($event->title)->addClass('dp-event-header', true);

// The event details table
$t = $root->addChild(new Table('event-details', array('', '')));

// Add an information row
$r = $t->addRow(new Row('date'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_DATE'));
$r->addCell(new Cell('content'))->setContent(
	DPCalendarHelper::getDateStringFromEvent(
		$event,
		$params->get('event_date_format', 'm.d.Y'),
		$params->get('event_time_format', 'g:i a')
	)
);

if ($event->locations) {
	// Add the location row
	$r = $t->addRow(new Row('location'));
	$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION'));
	$c = $r->addCell(new Cell('content'));
	foreach ($event->locations as $location) {
		$c->addChild(new Paragraph($location->id))->setContent(\DPCalendar\Helper\Location::format($location));
	}
}

// Add the header
$root->addChild(
	new Heading('ticket-heading', 2)
)->setContent(JText::_('COM_DPCALENDAR_INVOICE_TICKET_DETAILS'))->addClass('dp-event-header', true);

// Add an information row
$r = $t->addRow(new Row('id'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->uid);

if ($event->price && key_exists($ticket->type, $event->price->label) && $event->price->label[$ticket->type]) {

	// Add an information row
	$r = $t->addRow(new Row('type'));
	$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_TICKET_FIELD_TYPE_LABEL'));
	$c = $r->addCell(new Cell('content'));
	$c->setContent($event->price->label[$ticket->type]);
	if ($event->price->description[$ticket->type]) {
		$c->setContent($event->price->description[$ticket->type], true);
	}
}

if ($hasPrice) {
	// Add an information row
	$r = $t->addRow(new Row('price'));
	$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_FIELD_PRICE_LABEL'));
	$r->addCell(new Cell('content'))->setContent(DPCalendarHelper::renderPrice($ticket->price, $params->get('currency_symbol', '$')));
}

// The details table
$t = $root->addChild(new Table('booking-details', array('', '')));

// Add an information row
$r = $t->addRow(new Row('name'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->name);

// Add an information row
$r = $t->addRow(new Row('email'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_EMAIL_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->email);

// Add an information row
$r = $t->addRow(new Row('telephone'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_BOOKING_FIELD_TELEPHONE_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->telephone);

// Add an information row
$r = $t->addRow(new Row('country'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->country);

// Add an information row
$r = $t->addRow(new Row('province'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->province);

// Add an information row
$r = $t->addRow(new Row('city'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->zip . ' ' . $ticket->city);

// Add an information row
$r = $t->addRow(new Row('street'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->street . ' ' . $ticket->number);

// Add an information row
$r = $t->addRow(new Row('seat'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL'));
$r->addCell(new Cell('content'))->setContent($ticket->seat);

// The fields are not fetched, load them
if (!isset($ticket->jcfields)) {
	JPluginHelper::importPlugin('content');
	$ticket->text = '';
	JEventDispatcher::getInstance()->trigger('onContentPrepare', array('com_dpcalendar.ticket', &$ticket, &$params, 0));
}

// The fields table
$t = $root->addChild(new Table('ticket-fields', array('', '')));
if (!empty($ticket->jcfields)) {
	// Loop over the fields
	foreach ($ticket->jcfields as $field) {
		// Add an information row for the field
		$r = $t->addRow(new Row($field->id));
		$r->addCell(new Cell('label'))->setContent($field->label);
		$r->addCell(new Cell('content'))->setContent(!empty($field->value) ? $field->value : '');
	}
}
