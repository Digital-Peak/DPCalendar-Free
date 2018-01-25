<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Paragraph;
use CCL\Content\Element\Basic\TextBlock;
use CCL\Content\Element\Basic\Link;

if (!$events) {
	echo JText::_('MOD_DPCALENDAR_UPCOMING_NO_EVENT_TEXT');

	return;
}

// Load the required JS libraries
DPCalendarHelper::loadLibrary(array('dpcalendar' => true, 'url' => true));

if ($params->get('show_as_popup')) {
	// Load the required JS libraries
	DPCalendarHelper::loadLibrary(array('modal' => true));
	JHtml::_('script', 'mod_dpcalendar_upcoming/default.min.js', ['relative' => true], ['defer' => true]);
}

// Load the stylesheet
JHtml::_('stylesheet', 'mod_dpcalendar_upcoming/default.min.css', ['relative' => true]);

// The root container
$root = new Container('dp-module-upcoming-default-' . $module->id, array('root'), array('ccl-prefix' => 'dp-module-upcoming-default-'));
$root->addClass('dp-module-upcoming-root', true);

// The events container
$c = $root->addChild(new Container('events'));

// The last computed heading
$lastHeading = '';

// The grouping parameter
$grouping = $params->get('output_grouping', '');

// Loop over the events
foreach ($events as $index => $event) {
	// The start date
	$startDate = DPCalendarHelper::getDate($event->start_date, $event->all_day);

	// Grouping functionality
	if ($grouping) {
		// Check if the actual grouping header is different than from the event before
		$groupHeading = $startDate->format($grouping, true);
		if ($groupHeading != $lastHeading) {
			// Add a new grouping header
			$lastHeading = $groupHeading;
			$c->addChild(new Paragraph('heading-' . ($index + 1), array('heading')))->setContent($groupHeading);
		}
	}

	// The event container
	$ec = $c->addChild(new Container($event->id, array('container')));

	// The calendar icon element
	$cal = $ec->addChild(new Container('calendar', array('calendar')));
	$cal->addChild(new TextBlock('day', array('day')))->setContent($startDate->format('j', true));
	$m = $cal->addChild(new TextBlock('month', array('month')))->setContent($startDate->format('M', true));

	// Add per event the color for the calendar icon
	JFactory::getDocument()->addStyleDeclaration('#' . $m->getId() . ' {background-color: #' . $event->color . '; box-shadow: 0 2px 0 #' . $event->color . ';}');

	// The container for the event details
	$e = $ec->addChild(new Container('event', array('event')));

	// Add the date element
	$e->addChild(new Container('date'))->setContent(
		DPCalendarHelper::getDateStringFromEvent($event, $params->get('date_format'), $params->get('time_format'))
	);

	// Add the link
	$l = $e->addChild(new Link('link', $event->realUrl));

	// Add a special class when popup is enabled
	$l->addClass('dp-module-upcoming-modal-' . ($params->get('show_as_popup') ? 'enabled' : 'disabled'), true);

	// Add the title
	$l->addChild(new TextBlock('title'))->setContent($event->title);

	// Add the location information
	if ($params->get('show_location') && isset($event->locations) && $event->locations) {
		foreach ($event->locations as $location) {
			$l = $ec->addChild(new TextBlock('location-' . $location->id, array('location')));
			$l->addAttribute('data-latitude', $location->latitude);
			$l->addAttribute('data-longitude', $location->longitude);
			$l->addAttribute('data-title', $location->title);

			$l->addChild(new Link('link', DPCalendarHelperRoute::getLocationRoute($location)))->setContent($location->title);
		}
	}

	// Add the price schema
	DPCalendarHelper::renderLayout('schema.event', array('event' => $event, 'root' => $ec));
}

// Render the element tree
echo DPCalendarHelper::renderElement($root, $params);
