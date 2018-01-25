<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Element;

// Set up the params
$params = $displayData['params'];

// The root element
$root = $displayData['root'];

// Check if the map should be loaded at all
if (!$params->get('show_map', 1)) {
	return;
}

// Load the JS files
DPCalendarHelper::loadLibrary(array('maps' => true));

// Add the map element
$map = $root->addChild(
	new Element(
		'map',
		array('dpcalendar-map', 'dpcalendar-fixed-map'),
		array(
			'style'          => 'width: ' . $params->get('map_width', '100%') . ';height: ' . $params->get('map_height', '350px'),
			'data-zoom'      => $params->get('map_zoom', 6),
			'data-latitude'  => $params->get('map_lat', 47),
			'data-longitude' => $params->get('map_long', 4)
		)
	)
);
$map->setProtectedClass('dpcalendar-map');
$map->setProtectedClass('dpcalendar-fixed-map');
