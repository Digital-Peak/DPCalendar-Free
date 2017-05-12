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

// Load the js libraries
DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true, 'maps' => true));

// Load the stylesheet
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/views/event/default.css', array(), true);

// Set some params tailored to the view
$params = $this->params;
$params->set('show_map', false);
$params->set('link_title', true);

// Add classes for js map library
$this->root->addClass('dpcalendar-locations-container', true);

// Create the element which holds the map
$mc = $this->root->addChild(
	new Element(
		'map',
		array('dpcalendar-map', 'dpcalendar-fixed-map'),
		array(
			'style'          => 'width: ' . $params->get('locations_map_width', '100%') . ';height: ' . $params->get('locations_map_height', '350px'),
			'data-zoom'      => $params->get('locations_map_zoom', 4),
			'data-latitude'  => $params->get('locations_map_latitude', 47),
			'data-longitude' => $params->get('locations_map_longitude', 4)
		)
	)
);
$mc->setProtectedClass('dpcalendar-map');
$mc->setProtectedClass('dpcalendar-fixed-map');

// The container of the locations
$lc = $this->root->addChild(new Container('list'))->addClass('dpcalendar-locations', true);

// Loop over the locations
foreach ($this->locations as $location)
{
	switch ($params->get('locations_expand', 1))
	{
		case 0:
			// Just adding the location information
			$lc->addChild(
				new Element('details-' . $location->id,
					array(),
					array(
						'data-color'     => \DPCalendar\Helper\Location::getColor($location),
						'data-title'     => $location->title,
						'data-latitude'  => $location->latitude,
						'data-longitude' => $location->longitude
					)
				)
			)->addClass('location-details', true);
			break;
		case 1:
			// Set show details to false
			$params->set('show_details', false);
		case 2:
			// Render the location details
			DPCalendarHelper::renderLayout('location.details', array('root' => $lc, 'location' => $location, 'params' => $this->params));
	}
}

// Render the element tree
echo DPCalendarHelper::renderElement($this->root, $this->params);
