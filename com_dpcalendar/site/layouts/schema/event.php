<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;

// The locations to display
$event = $displayData['event'];
if (!$event) {
	return;
}

$root = $displayData['root']->addChild(new Container($event->id, [],
	array('itemprop' => 'event', 'itemtype' => 'http://schema.org/Event', 'itemscope' => 'itemscope'))
);

// Add the date schema
DPCalendarHelper::renderLayout('schema.name', array('event' => $event, 'root' => $root));

// Add the date schema
DPCalendarHelper::renderLayout('schema.date', array('event' => $event, 'root' => $root));

// Add the url schema
DPCalendarHelper::renderLayout('schema.url', array('event' => $event, 'root' => $root));

// Add the price schema
DPCalendarHelper::renderLayout('schema.offer', array('event' => $event, 'root' => $root));

// Add the performer schema
DPCalendarHelper::renderLayout('schema.performer', array('event' => $event, 'root' => $root));

// Add the image schema
DPCalendarHelper::renderLayout('schema.image', array('event' => $event, 'root' => $root));

// Add the description schema
DPCalendarHelper::renderLayout('schema.description', array('event' => $event, 'root' => $root));

// Add the location schema
if (!empty($event->locations)) {
	DPCalendarHelper::renderLayout('schema.location', array('locations' => $event->locations, 'root' => $root));
}
