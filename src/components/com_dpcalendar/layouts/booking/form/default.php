<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
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

JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/layouts/booking/form/default.css', array(), true);
JHtml::_('script', 'com_dpcalendar/dpcalendar/layouts/booking/form/default.js', false, true);

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

// Add the loader image for the ajax requests
$root->addChild(new Container('loader'))->addChild(new Image('loader-image', JUri::base() . 'media/com_dpcalendar/images/site/ajax-loader.gif'));

if ($app->isSite()) {
	$displayData['root'] = $root;

	// Load the header template
	DPCalendarHelper::renderLayout('booking.form.toolbar', $displayData);
}

// Load the payment template
DPCalendarHelper::renderLayout('booking.form.payment', $displayData);

// Load the form from the layout
$hideFields = array('latitude', 'longitude', 'series', 'state', 'transaction_id', 'type', 'payer_email');

if ($app->isAdmin()) {
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
}
DPCalendarHelper::renderLayout(
	'content.form',
	array('root' => $root, 'jform' => $form, 'fieldsToHide' => $hideFields, 'return' => $returnPage, 'flat' => true)
);

// Render the tree
echo DPCalendarHelper::renderElement($root, $params);
