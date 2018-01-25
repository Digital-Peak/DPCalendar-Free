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
$root->addChild(new Heading('event-heading', 2))->setContent($event->title)->addClass('dpcalendar-heading', true);

// The event details table
$t = $root->addChild(new Table('event-details', array('', '')));

// Add an information row
$r = $t->addRow(new Row('date'));
$r->addCell(new Cell('label'))->setContent(JText::_('COM_DPCALENDAR_DATE'));
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
)->setContent(JText::_('COM_DPCALENDAR_INVOICE_TICKET_DETAILS'))->addClass('dpcalendar-heading', true);

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
$t = $root->addChild(new Table('ticket-details', array('', '')));


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
$fields[] = (object)array('id' => 'seat', 'name' => 'seat', 'label' => 'COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL');

// The fields are not fetched, load them
if (!isset($ticket->jcfields)) {
	JPluginHelper::importPlugin('content');
	$ticket->text = '';
	JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_dpcalendar.ticket', &$ticket, &$params, 0));
}
$fields = array_merge($fields, $ticket->jcfields);

\DPCalendar\Helper\DPCalendarHelper::sortFields($fields, $params->get('ticket_fields_order', new stdClass()));

foreach ($fields as $field) {
	if (!$params->get('ticket_show_' . $field->name, 1)) {
		continue;
	}
	$label = 'COM_DPCALENDAR_BOOKING_FIELD_' . strtoupper($field->name) . '_LABEL';

	if (isset($field->label)) {
		$label = $field->label;
	}

	$content = '';
	if (property_exists($ticket, $field->name)) {
		$content = $ticket->{$field->name};
	}
	if (property_exists($field, 'value')) {
		$content = $field->value;
	}

	$r = $t->addRow(new Row($field->name));
	$r->addCell(new Cell('label'))->setContent(JText::_($label));
	$r->addCell(new Cell('content'))->setContent($content);
}
