<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Component\Alert;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\TextBlock;
use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\DescriptionListHorizontal;
use CCL\Content\Element\Basic\Description\Term;
use CCL\Content\Element\Basic\Description\Description;

$params = $this->params;
if (!$params->get('event_show_bookings', '1')) {
	// Booking stuff should not being displayed
	return;
}

$event = $this->event;
if (($event->capacity !== null && (int)$event->capacity === 0) || DPCalendarHelper::isFree()) {
	// Booking is not activated
	return;
}

// Find the tickets of the logged in user
$user    = JFactory::getUser();
$tickets = array();
foreach ($event->tickets as $t) {
	if ($user->id > 0 && $user->id == $t->user_id) {
		$tickets[] = $t;
	}
}

if ($tickets) {
	// Send the message that tickets are already taken
	JFactory::getApplication()->enqueueMessage(
		JText::plural('COM_DPCALENDAR_VIEW_EVENT_BOOKED_TEXT', count($tickets), DPCalendarHelperRoute::getTicketsRoute(null, $event->id, true)));
}

/** @var Container $root * */
$root = $this->root->addChild(new Container('booking'));

// The heading
$h = $root->addChild(new Heading('heading', 3, array('dpcalendar-heading')));
$h->setProtectedClass('dpcalendar-heading');
$h->setContent(JText::_('COM_DPCALENDAR_VIEW_EVENT_BOOKING_INFORMATION'));

// Set up the booking alert when bookings can be done
if (\DPCalendar\Helper\Booking::openForBooking($event)) {

	// Booking is possible, show the book message
	$alert = $root->addChild(new Alert('text', Alert::WARNING, array('noprint')));
	$alert->setProtectedClass('noprint');

	// The link to the booking form
	$link = $alert->addChild(new Link('link', DPCalendarHelperRoute::getBookingFormRouteFromEvent($event, JUri::getInstance()->toString())));

	// The plus icon
	$link->addChild(new Icon('icon', Icon::PLUS));

	// The text
	$link->addChild(new TextBlock('text'))->setContent(JText::_('COM_DPCALENDAR_VIEW_EVENT_TO_BOOK_TEXT'));

	// Add registration end date
	$endDate = \DPCalendar\Helper\Booking::getRegistrationEndDate($event);
	$alert->addChild(new Container('registration-closing-date'))->setContent(
		JText::sprintf(
			'COM_DPCALENDAR_VIEW_EVENT_REGISTRATION_END_TEXT',
			$endDate->format($params->get('event_date_format', 'm.d.Y'), true),
			$endDate->format('H:i') != '00:00' ? $endDate->format($params->get('event_time_format', 'h:i a'), true) : ''
		)
	);
}

// Show the price
if ($params->get('event_show_price', '1') && $event->price) {
	// The discount container
	$dc = new Alert('discount', Alert::WARNING, array('noprint'));
	$dc->setProtectedClass('noprint');

	if ($event->earlybird) {
		// Create the earlybird element
		$now = DPCalendarHelper::getDate();
		foreach ($event->earlybird->value as $index => $value) {
			if (\DPCalendar\Helper\Booking::getPriceWithDiscount(1000, $event, $index, -2) == 1000) {
				// No discount
				continue;
			}

			$limit = $event->earlybird->date[$index];
			$date  = DPCalendarHelper::getDate($event->start_date);
			if (strpos($limit, '-') === 0 || strpos($limit, '+') === 0) {
				// Relative date
				$date->modify(str_replace('+', '-', $limit));
			} else {
				// Absolute date
				$date = DPCalendarHelper::getDate($limit);
				if ($date->format('H:i') == '00:00') {
					$date->setTime(23, 59, 59);
				}
			}

			// Earlybird container
			$ec = new Container('earlybird');

			// Add the earlybird label
			$label = $event->earlybird->label[$index];
			$ec->addChild(new TextBlock('label'))->setContent($label ? $label : JText::_('COM_DPCALENDAR_FIELD_EARLYBIRD_LABEL'));

			// Add the earlybird text
			$value        = ($event->earlybird->type[$index] == 'value' ? DPCalendarHelper::renderPrice($value) : $value . ' %');
			$dateFormated = $date->format($params->get('event_date_format', 'm.d.Y'), true);
			$v            = $ec->addChild(new TextBlock('value'))->setContent(' ');
			$v->setContent(JText::sprintf('COM_DPCALENDAR_VIEW_EVENT_EARLYBIRD_DISCOUNT_TEXT', $value, $dateFormated), true);

			// Add the earlybird description
			$ec->addChild(new TextBlock('description'))->setContent($event->earlybird->description[$index]);

			// Add the earlybird container
			$dc->addChild($ec);

			break;
		}
	}

	if ($event->user_discount) {
		// Create the user discount message
		foreach ($event->user_discount->value as $index => $value) {
			if (\DPCalendar\Helper\Booking::getPriceWithDiscount(1000, $event, -2, $index) == 1000) {
				// No discount
				continue;
			}

			// Earlybird container
			$uc = new Container('user-discount');

			// Add the user-discount label
			$label = $event->user_discount->label[$index];
			$uc->addChild(new TextBlock('label'))->setContent($label ? $label : JText::_('COM_DPCALENDAR_FIELD_USER_DISCOUNT_LABEL'));

			// Add the user-discount text
			$v = $uc->addChild(new TextBlock('value'))->setContent(' ');
			$v->setContent($event->user_discount->type[$index] == 'value' ? DPCalendarHelper::renderPrice($value) : $value . ' %', true);

			// Add the earlybird description
			$uc->addChild(new TextBlock('description'))->setContent($event->user_discount->description[$index]);

			// Add the user discount container
			$dc->addChild($uc);

			break;
		}
	}

	// Add the booking container only when there is content
	if ($dc->getChildren()) {
		$root->addChild($dc);
	}

	// Add the prices
	foreach ($event->price->value as $key => $value) {
		$discounted = \DPCalendar\Helper\Booking::getPriceWithDiscount($value, $event);

		// The description list
		$dl = $root->addChild(new DescriptionListHorizontal('price-' . $key));

		// Add the term
		$t = $dl->setTerm(new Term('label', array('label')));
		$t->addClass('dpcalendar-label', true);
		$t->setContent($event->price->label[$key] ?: JText::_('COM_DPCALENDAR_FIELD_PRICE_LABEL'));

		// Add the description
		$desc = $dl->setDescription(
			new Description(
				'content',
				array('content'),
				array('title' => DPCalendarHelper::getComponentParameter('currency', 'USD'))
			)
		);

		// Add the regular price to the description
		$classes = array('price-regular');
		if ($discounted != $value) {
			$classes[] = 'price-has-discount';
		}
		$desc->addChild(new TextBlock('regular', $classes))->setContent(DPCalendarHelper::renderPrice($value));

		// Add the discount price if available
		if ($discounted != $value) {
			$desc->addChild(new TextBlock('discount', array('price-discount')))->setContent(DPCalendarHelper::renderPrice($discounted));
		}

		// Add the price description
		$desc->addChild(new TextBlock('description', array('price-description')))->setContent($event->price->description[$key]);
	}
}

// Set up the capacity when possible
if ($params->get('event_show_capacity', '1') && ($event->capacity === null || $event->capacity > 0)) {
	DPCalendarHelper::renderLayout(
		'content.dl',
		array(
			'root'    => $root,
			'id'      => 'capacity',
			'label'   => 'COM_DPCALENDAR_FIELD_CAPACITY_LABEL',
			'content' => $event->capacity === null ? JText::_('COM_DPCALENDAR_FIELD_CAPACITY_UNLIMITED') : (int)$event->capacity
		)
	);
}

// Set up the capacity used when possible
if ($params->get('event_show_capacity_used', '1') && ($event->capacity === null || $event->capacity > 0)) {
	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $root, 'id' => 'capacity-used', 'label' => 'COM_DPCALENDAR_FIELD_CAPACITY_USED_LABEL', 'content' => $event->capacity_used)
	);
}

// Set up the booking information
if ($event->booking_information) {
	$root->addChild(new Container('dp-event-booking-information'))->setContent($event->booking_information);
}
