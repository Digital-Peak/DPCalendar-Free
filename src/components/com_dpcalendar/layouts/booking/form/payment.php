<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Component\Alert;
use CCL\Content\Element\Basic\Form\Label;
use CCL\Content\Element\Basic\Form\Input;
use CCL\Content\Element\Basic\Image;
use CCL\Content\Element\Basic\Paragraph;
use CCL\Content\Element\Basic\Table;
use CCL\Content\Element\Basic\Table\Row;
use CCL\Content\Element\Basic\Table\Cell;
use CCL\Content\Element\Basic\Form\Select;
use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\TextBlock;

/**
 * Layout variables
 * -----------------
 * @var object   $booking
 * @var object   $event
 * @var object[] $tickets
 * @var object   $form
 * @var object   $input
 * @var object   $params
 * @var string   $returnPage
 **/
extract($displayData);

// The series events
$events = \DPCalendar\Helper\Booking::getSeriesEvents($event);

// If no series events are found add the single event
if ($event && !$events) {
	$events = array($event);
}

// The booking id of the actual booking
$bookingId = $booking && $booking->id ? $booking->id : 0;

// Add a need payment message when state is needs payment
if ($bookingId && $booking->state == 3) {
	$app->enqueueMessage(JText::_('COM_DPCALENDAR_VIEW_BOOKING_STATE_NEEDS_PAYMENT_INFORMATION'));
}

// Add a is on hold message when payment is on hold
$needsPayment = false;
if ($bookingId && $booking->state == 4) {
	$app->enqueueMessage(JText::_('COM_DPCALENDAR_VIEW_BOOKING_STATE_ON_HOLD_INFORMATION'));
	$needsPayment = true;
} else {
	// Calculate if a payment is needed
	$needsPayment = \DPCalendar\Helper\Booking::paymentRequired($event);
	foreach ($events as $s) {
		if (\DPCalendar\Helper\Booking::paymentRequired($s)) {
			$needsPayment = true;
			break;
		}
	}
}

/** @var Container $root * */
$root = $root->addChild(new Container('options'));

// Add the payment options when a payment is needed
if ($needsPayment || ($bookingId && $booking->state == 3)) {
	// The container
	$c = $root->addChild(new Container('payment'));

	// The alert box with the choose payment text
	$c->addChild(new Alert('info', Alert::INFO))->setContent(JText::_('COM_DPCALENDAR_VIEW_BOOKING_CHOOSE_PAYMENT_OPTION'));

	// Loop trough all payment plugins
	$plugins = JPluginHelper::getPlugin('dpcalendarpay');
	foreach ($plugins as $key => $plugin) {
		// Load the language file of the plugin
		$app->getLanguage()->load('plg_' . $plugin->type . '_' . $plugin->name, JPATH_PLUGINS . '/' . $plugin->type . '/' . $plugin->name);

		// The payment container
		$pc = $c->addChild(new Container('plugin-' . $plugin->name, array('payment-plugin')));

		// Create the label
		$l = $pc->addChild(new Label('label', $pc->getId() . '-label-method'));

		// The attributes for the input box
		$attributes = array();
		if (count($plugins) == 1) {
			// When there is only one plugin select it directly
			$attributes['checked'] = 'checked';
		}

		// The radio box
		$l->addChild(new Input('method', 'radio', 'paymentmethod', $plugin->name, array(), $attributes));

		// The image of the  plugin
		$l->addChild(new Image('image', 'plugins/' . $plugin->type . '/' . $plugin->name . '/images/' . $plugin->name . '.png'));

		// The description of the payment plugin
		$l->addChild(new Paragraph('text'))->setContent(JText::_('PLG_' . strtoupper($plugin->type . '_' . $plugin->name) . '_PAY_BUTTON_DESC'));
	}
}

if (!$bookingId) {
	if ($event && (int)$event->original_id > 0) {
		// Add the series option
		$s = $root->addChild(new Container('series'));

		$field = $form->getField('series');
		$s->addChild(new TextBlock('label'))->setContent($field->__get('label'));
		$s->addChild(new TextBlock('content'))->setContent($field->__get('input'));
		$s->addChild(new TextBlock('description'))->setContent(JText::_($field->__get('description')));
	}

	$columns = array('', '', '');
	if ($needsPayment) {
		$columns = array('', '', '', '', '', '');
	}

	/** @var Table $t * */
	$t = $root->addChild(new Container('events'))->addChild(new Table('table', $columns));

	// Loop trough the events
	foreach ($events as $index => $instance) {
		// Set up the price correctly
		$price = $instance->price;
		if (!$price || !$price->value) {
			$price = new JObject(array('value' => array('0'), 'label' => array(''), 'description' => array('')));
		}

		// Loop trough the prices
		foreach ($price->value as $key => $value) {
			// Compose the id
			$id = $instance->id . '-' . $key;

			// Get the row
			$row = $t->addRow(new Row('row-' . $id, array($instance->id == $event->id ? 'event-original' : 'event-instance')));

			// Add the title cell
			$row->addCell(new Cell('title'))->setContent($instance->title . ': ' . $price->label[$key]);

			// Add the date cell
			$row->addCell(new Cell('date'))->setContent(
				DPCalendarHelper::getDateStringFromEvent(
					$instance,
					$params->get('event_date_format', 'm.d.Y'),
					$params->get('event_time_format'), 'g:i a'
				)
			);

			// Add the price cell
			$cell = $row->addCell(new Cell('amount', array('options-amount')));

			// Create the select box
			$select = $cell->addChild(new Select('tickets', $form->getFormControl() . '[event_id][' . $instance->id . '][' . $key . ']'));

			$info = JText::_('COM_DPCALENDAR_VIEW_BOOKINGFORM_CHOOSE_TICKETS');
			$max  = $instance->max_tickets ? $instance->max_tickets : 1;

			// Loop trough the existing tickets
			foreach ($instance->tickets as $ticket) {
				// Check if the user has already tickets
				if ($user->guest || $ticket->user_id != $user->id || $ticket->type != $key) {
					continue;
				}
				$max--;

				if ($max == 0) {
					// Tickets are not available
					$info = JText::_('COM_DPCALENDAR_VIEW_BOOKINGFORM_CHOOSE_TICKET_LIMIT_REACHED');
					break;
				}
			}

			// For every possible ticket add an option
			for ($i = 0; $i <= $max; $i++) {
				$select->addOption($i, $i, $i == 1 && $instance->id == $event->id);
			}

			// Set up the info cell for the amount
			$cell = $row->addCell(new Cell('amount-info', array('options-amount-info')));
			$cell->addChild(new Icon('info-icon', Icon::INFO, array(), array('title' => $info)));

			if ($needsPayment) {
				$cell = $row->addCell(new Cell('price', array('options-price')));

				$o = $cell->addChild(new Element('live', array('options-price-live')));
				$o->setContent('0.00');

				$o = $cell->addChild(new Element('original', array('options-price-cell-original')));
				$o->setContent('0.00');

				// Add the info icon
				$cell = $row->addCell(new Cell('info', array('options-price-info')));
				$i    = $cell->addChild(
					new Icon(
						'icon', Icon::INFO,
						array('price-info'),
						array('title' => JText::_('COM_DPCALENDAR_VIEW_BOOKINGFORM_DISCOUNT'))
					)
				);
			}
		}
	}

} else {
	DPCalendarHelper::renderLayout(
		'tickets.list',
		array(
			'tickets' => $tickets,
			'params'  => $params,
			'root'    => $root->addChild(new Container('events'))
		)
	);
}

// Add a total row, when needed
if ($needsPayment || $booking->state == 3) {
	$row = $root->addChild(new Container('total-price'));
	$row->addChild(new TextBlock('label'))->setContent(JText::_('COM_DPCALENDAR_VIEW_BOOKING_TOTAL') . ': ');
	$row->addChild(new TextBlock('price-content'))->setContent($booking && $booking->id ? $booking->price : 0.00);
	$row->addChild(new TextBlock('price-currency'))->setContent(DPCalendarHelper::getComponentParameter('currency_symbol', '$'));
}
