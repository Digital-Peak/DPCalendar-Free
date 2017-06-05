<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
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

// Set up the root container
$root = $displayData['root']->addChild(new Container('schema-offer'));

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

	$c->addChild(new Meta('price', 'price', htmlspecialchars($value, ENT_QUOTES)));
	if ($label) {
		$c->addChild(new Meta('name', 'name', htmlspecialchars($label, ENT_QUOTES)));
	}
	if ($desc) {
		$c->addChild(new Meta('description', 'description', htmlspecialchars($desc, ENT_QUOTES)));
	}
	$c->addChild(
		new Meta(
			'availability',
			'availability',
			htmlspecialchars(JText::_('COM_DPCALENDAR_FIELD_CAPACITY_LABEL') . ': ' . $event->capacity, ENT_QUOTES)
		)
	);
	$c->addChild(new Meta('url', 'url', htmlspecialchars(DPCalendarHelperRoute::getEventRoute($event->id, $event->catid, true, true), ENT_QUOTES)));
}
