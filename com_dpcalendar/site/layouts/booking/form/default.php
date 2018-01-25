<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Form;
use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Image;

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

// Load the needed javascript files
DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true));

JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/layouts/booking/form/default.css', ['relative' => true]);
JHtml::_('script', 'com_dpcalendar/dpcalendar/layouts/booking/form/default.js', ['relative' => true], ['defer' => true]);

/** @var integer $bookingId * */
$bookingId = $booking && $booking->id ? $booking->id : 0;

// The url to fetch the price information from
JFactory::getDocument()->addScriptDeclaration(
	"var PRICE_URL = '" .
	JUri::base() .
	'index.php?option=com_dpcalendar&task=booking.calculateprice&e_id=' .
	(!empty($event) ? $event->id : 0) .
	'&b_id=' . (int)$bookingId .
	"';"
);

// The form element
$tmpl = $input->getCmd('tmpl') ? '&tmpl=' . $input->getCmd('tmpl') : '';
$root = new Form(
	'dp-bookingform',
	JRoute::_('index.php?option=com_dpcalendar&view=bookingform&b_id=' . (int)$bookingId . $tmpl, false),
	'adminForm',
	'POST',
	array('form-validate'),
	array('ccl-prefix' => $root->getPrefix())
);

// Load the spinning wheel
DPCalendarHelper::renderLayout('calendar.loader', ['root' => $root]);

if ($app->isClient('site')) {
	$displayData['root'] = $root;
}

// Load the payment template
DPCalendarHelper::renderLayout('booking.form.payment', $displayData);

if ($app->isClient('site')) {
	// Load the header template
	DPCalendarHelper::renderLayout('booking.form.toolbar', $displayData);
}

// Load the form from the layout
$hideFields = array('latitude', 'longitude', 'series', 'transaction_id', 'type', 'payer_email');

if ($app->isClient('administrator')) {
	if (!$booking->id) {
		$hideFields[] = 'price';
	} else {
		$hideFields[] = 'event_id';
		$hideFields[] = 'amount';
	}
} else {
	$hideFields[] = 'price';
	$hideFields[] = 'processor';
	$hideFields[] = 'amount';
	$hideFields[] = 'event_id';
	$hideFields[] = 'state';
}
DPCalendarHelper::renderLayout(
	'content.form',
	array('root' => $root, 'jform' => $form, 'fieldsToHide' => $hideFields, 'return' => $returnPage, 'flat' => true)
);

// Render the tree
echo DPCalendarHelper::renderElement($root, $params);
