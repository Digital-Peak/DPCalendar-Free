<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Heading;

// Global variables
$event  = $this->event;
$params = $this->params;

if (!$event->locations || $params->get('event_show_location', '2') != '2')
{
	// Return when we don't have to show something
	return;
}

// Set some parameters for the map
$params->set('show_map', $params->get('event_show_map', '1') == '1');
$params->set('full_width', false);
$params->set('link_title', true);
$params->set('location_map_height', '200px');

/** @var Container $root **/
$root = $this->root->addChild(new Container('locations', array(), array('itemprop' => 'description')));
$root->setProtectedClass('dplocations');

// Add the heading
$h = $root->addChild(new Heading('header', 2, array('dp-event-header')));
$h->setProtectedClass('dp-event-header');
$h->setContent(JText::_('COM_DPCALENDAR_VIEW_EVENT_LOCATION_INFORMATION'));

// Create the locations
foreach ($event->locations as $location)
{
	DPCalendarHelper::renderLayout(
		'location.details',
		array('location' => $location, 'params' => $params, 'root' => $root)
	);
}
