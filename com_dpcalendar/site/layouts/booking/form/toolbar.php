<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Component\Icon;

/**
 * Layout variables
 * -----------------
 * @var object $booking
 * @var object $event
 * @var object $form
 * @var object $input
 * @var object $params
 * @var string $returnPage
 **/
extract($displayData);

/** @var integer $bookingId * */
$bookingId = $booking && $booking->id ? $booking->id : 0;

/** @var Container $root * */
$root = $root->addChild(new Container('actions'));
$root->addClass('noprint', true);
$root->addClass('dp-actions-container', true);

// Determine the text for the button
$text = 'JSAVE';
if (!$bookingId) {
	$text = 'COM_DPCALENDAR_VIEW_BOOKING_BOOK_BUTTON';
} else if ($booking->state == 3) {
	$text = 'COM_DPCALENDAR_PAY';
}

// Create the save/book button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::OK,
		'root'    => $root,
		'text'    => $text,
		'onclick' => "checkIfPaymentIsneeded(event);"
	)
);

// Create the cancel button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::CANCEL,
		'root'    => $root,
		'text'    => 'JCANCEL',
		'onclick' => "Joomla.submitbutton('bookingform.cancel')"
	)
);

if ($bookingId && (!$booking->price || $booking->price == '0.00')) {
	// Create the delete button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'type'    => Icon::DELETE,
			'root'    => $root,
			'text'    => 'JACTION_DELETE',
			'onclick' => "Joomla.submitbutton('bookingform.delete')"
		)
	);
}
