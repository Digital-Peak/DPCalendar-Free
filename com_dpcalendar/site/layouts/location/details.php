<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\TextBlock;
use CCL\Content\Element\Component\Grid;
use CCL\Content\Element\Component\Grid\Row;
use CCL\Content\Element\Component\Grid\Column;
use DPCalendar\Helper\Location;
use DPCalendar\Helper\DPCalendarHelper;
use Joomla\Registry\Registry;

// The location to display
$location = $displayData['location'];
if (!$location) {
	return;
}

// The params
$params = $displayData['params'];
if (!$params) {
	$params = new Registry();
}

// Set up the root container
$root = $displayData['root'];

// Should the details being shown
$showDetails = $params->get('show_details', true);

// The location container
$mapContainer = null;

// Should the map being shown
if ($params->get('show_map', true)) {
	// The container which holds the map
	DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true, 'maps' => true));

	// Create the element which holds the map
	$mapContainer = new Element(
		'map',
		array('dpcalendar-map', 'dpcalendar-fixed-map'),
		array(
			'style'          => 'width: ' . $params->get('location_map_width', '100%') . ';height: ' . $params->get('location_map_height', '350px'),
			'data-zoom'      => $params->get('location_map_zoom', 4),
			'data-latitude'  => $params->get('location_map_latitude', 47),
			'data-longitude' => $params->get('location_map_longitude', 4)
		)
	);
	$mapContainer->setProtectedClass('dpcalendar-map');
	$mapContainer->setProtectedClass('dpcalendar-fixed-map');
}

// The container which holds the location details
$c = $root->addChild(new Container($location->id, array('dpcalendar-locations-container')));
$c->setProtectedClass('dpcalendar-locations-container');

// In full width mode, show the map on the beginning
if ($mapContainer && $params->get('full_width', true)) {
	$c->addChild($mapContainer);
}

if ($params->get('show_title', true)) {
	// Create the header
	$h = $c->addChild(new Heading('header', 3));
	$h->addChild(new Icon('location-icon', Icon::LOCATION));

	// Allow to edit
	if (JFactory::getUser()->authorise('core.edit', 'com_dpcalendar')) {
		$l = $h->addChild(new Link('edit-icon', DPCalendarHelperRoute::getLocationFormRoute($location->id, JUri::getInstance())));
		$l->addChild(new Icon('location-edit-icon', Icon::EDIT));
	}

	// Add the title
	if ($params->get('link_title') === 'external') {
		// The link to the external map provider
		$l = $h->addChild(new Link('external', Location::getMapLink($location), '_blank'));
		$l->setContent($location->title);
	} else {
		if ($params->get('link_title')) {
			// Link to the location detail view
			$h->addChild(new Link('external', DPCalendarHelperRoute::getLocationRoute($location)))->setContent($location->title);
		} else {
			// Just add the title
			$h->addChild(new TextBlock('title'))->setContent($location->title);
		}
	}
}

if ($mapContainer && !$params->get('full_width', true)) {
	// If the location should be shown beside the details, use a grid
	$grid = $c->addChild(new Grid('content'));
	$row  = $grid->addRow(new Row('details'));

	// Set the container for the lodation information to the first column
	$c = $row->addColumn(new Column('col1', 60));
	$row->addColumn(new Column('col2', 40))->addChild($mapContainer);
}

// The container which holds the location details
$ld = new Container(
	'details',
	array('location-details'),
	array(
		'data-color'       => Location::getColor($location),
		'data-title'       => $location->title,
		'data-description' => '<a href="' . DPCalendarHelperRoute::getLocationRoute($location) . '">' . $location->title . '</a>',
		'data-latitude'    => $location->latitude,
		'data-longitude'   => $location->longitude
	)
);
$ld->setProtectedClass('location-details');
$c->addChild($ld);

// Add the location informations
if ($showDetails && $location->country) {
	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $ld, 'id' => 'country', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL', 'content' => $location->country)
	);
}
if ($showDetails && $location->province) {
	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $ld, 'id' => 'province', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL', 'content' => $location->province)
	);
}
if ($showDetails && $location->city) {
	$content = $location->zip . ' ' . $location->city;

	if ($params->get('location_format', 'format_us') == 'format_us') {
		$content = $location->city . ' ' . $location->zip;
	}
	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $ld, 'id' => 'city', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL', 'content' => $content)
	);
}
if ($showDetails && $location->street) {
	$content = $location->street . ' ' . $location->number;

	if ($params->get('location_format', 'format_us') == 'format_us') {
		$content = $location->number . ' ' . $location->street;
	}

	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $ld, 'id' => 'street', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL', 'content' => $content)
	);
}
if ($showDetails && $location->rooms) {
	$buffer = array();
	foreach ($location->rooms as $room) {
		$t = new Container($room->id);
		$t->setContent($room->title);
		$buffer[] = $t;
	}

	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $ld, 'id' => 'rooms', 'label' => 'COM_DPCALENDAR_ROOMS', 'content' => $buffer)
	);
}
if ($showDetails && $location->url) {
	$l = new Link('url-link', $location->url, '_blank');
	$l->setContent($location->url);
	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $ld, 'id' => 'url', 'label' => 'COM_DPCALENDAR_FIELD_URL_LABEL', 'content' => $l)
	);
}

// Trigger the display event
$output = JFactory::getApplication()->triggerEvent(
	'onContentBeforeDisplay',
	array('com_dpcalendar.location', &$location, &$params, 0)
);

// Add the plugins output
$ld->addChild(new Container('plugins'))->setContent(trim(implode("\n", $output)));

// Trigger the prepare event when a description is available
if ($showDetails && $location->description) {
	$ld->addChild(new Container('description'))->setContent(JHTML::_('content.prepare', $location->description));
}
