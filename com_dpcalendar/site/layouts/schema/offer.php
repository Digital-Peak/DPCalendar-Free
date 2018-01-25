<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Meta;

// The locations to display
$event = $displayData['event'];
if (!$event || !$event->price) {
	return;
}

$currency = \DPCalendar\Helper\DPCalendarHelper::getComponentParameter('currency', 'USD');

// Set up the root container
$root = $displayData['root']->addChild(
	new Container('schema-offer',
		array(),
		array(
			'itemscope' => 'itemscope',
			'itemtype'  => 'https://schema.org/AggregateOffer',
			'itemprop'  => 'offers'
		)
	)
);

foreach ($event->price->value as $key => $value) {
	$label = $event->price->label[$key];
	$desc  = $event->price->description[$key];

	// Add the container for the location details
	$c = $root->addChild(
		new Container(
			'offer',
			array(),
			array(
				'itemscope' => 'itemscope',
				'itemtype'  => 'https://schema.org/Offer',
				'itemprop'  => 'offers'
			)
		)
	);

	$c->addChild(new Meta('price', 'price', $value));
	$c->addChild(new Meta('price-currency', 'priceCurrency', $currency));
	$c->addChild(new Meta('valid', 'validFrom', \DPCalendar\Helper\DPCalendarHelper::getDate($event->created)->format('c')));
	if ($label) {
		$c->addChild(new Meta('name', 'name', $label));
	}
	if ($desc) {
		$c->addChild(new Meta('description', 'description', $desc));
	}
	$c->addChild(
		new Meta(
			'availability',
			'availability',
			JText::_('COM_DPCALENDAR_FIELD_CAPACITY_LABEL') . ': ' . $event->capacity
		)
	);
	$c->addChild(new Meta('url', 'url', DPCalendarHelperRoute::getEventRoute($event->id, $event->catid, true, true)));
}
