<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\Paragraph;
use CCL\Content\Element\Basic\TextBlock;

// Load the js libraries
DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true, 'maps' => true));

// Load the stylesheet
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/views/locations/default.css', ['relative' => true]);
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/dpcalendar.css', ['relative' => true]);

// Set some params tailored to the view
$params = $this->params;
$params->set('show_map', false);
$params->set('link_title', true);

// Add classes for js map library
$this->root->addClass('dpcalendar-locations-container', true);

// The resource view
if ($params->get('locations_show_resource_view', 1) && !\DPCalendar\Helper\DPCalendarHelper::isFree()) {
	$resourceParams = clone $params;

	// Some defaults for the calendar
	$resourceParams->set('header_show_datepicker', $params->get('locations_header_show_datepicker', 1));
	$resourceParams->set('header_show_title', $params->get('locations_header_show_title', 1));
	$resourceParams->set('header_show_print', false);
	$resourceParams->set('header_show_month', false);
	$resourceParams->set('header_show_week', false);
	$resourceParams->set('header_show_day', false);
	$resourceParams->set('header_show_list', false);
	$resourceParams->set('header_show_timeline_day', true);
	$resourceParams->set('header_show_timeline_week', true);
	$resourceParams->set('header_show_timeline_month', true);
	$resourceParams->set('header_show_timeline_year', true);
	$resourceParams->set('timeformat_timeline_year', $params->get('locations_timeformat_year', 'g:i a'));
	$resourceParams->set('timeformat_timeline_month', $params->get('locations_timeformat_month', 'g:i a'));
	$resourceParams->set('timeformat_timeline_week', $params->get('locations_timeformat_week', 'g:i a'));
	$resourceParams->set('timeformat_timeline_day', $params->get('locations_timeformat_day', 'g:i a'));
	$resourceParams->set('show_selection', false);
	$resourceParams->set('show_map', false);
	$resourceParams->set('default_view', $params->get('locations_default_view', 'resday'));
	$resourceParams->set('use_hash', true);
	$resourceParams->set('event_create_form', 0);
	$resourceParams->set('screen_size_list_view', 0);

	// Resource specific options
	$resourceParams->set('calendar_filter_locations', $this->params->get('ids', array()));

	// Load the calendar layout
	DPCalendarHelper::renderLayout(
		'calendar.calendar',
		array(
			'params'            => $resourceParams,
			'root'              => $this->root,
			'selectedCalendars' => $this->ids
		)
	);
}

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
foreach ($this->locations as $location) {
	switch ($params->get('locations_expand', 1)) {
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

if ($params->get('locations_show_upcoming_events', 1)) {
	// The heading of the upcoming events
	$h = $this->root->addChild(new Heading('heading', 3, array('dpcalendar-heading')));

	$h->setProtectedClass('dpcalendar-heading');
	$h->setContent(JText::_('COM_DPCALENDAR_VIEW_PROFILE_UPCOMING_EVENTS'));

	// Upcoming events container
	$c = $this->root->addChild(new Container('upcoming-events'));

	// Loop trough the events
	foreach ($this->events as $event) {
		$startDate = DPCalendarHelper::getDate($event->start_date, $event->all_day);

		// The event paragraph
		$p = $c->addChild(new Paragraph('event-' . $event->id, array(), array('style' => 'border-color:#' . $event->color)));
		$b = $p->addChild(new TextBlock('date-' . $event->id));
		$b->setContent(DPCalendarHelper::getDateStringFromEvent($event, $params->get('date_format'), $params->get('time_format')));

		// The link
		$l = $p->addChild(new Link('link-' . $event->id, DPCalendarHelperRoute::getEventRoute($event->id, $event->catid)));
		$l->setContent($event->title);

		// Add the structured data schema
		DPCalendarHelper::renderLayout('schema.event', array('event' => $event, 'root' => $p));
	}
}

// Render the element tree
echo DPCalendarHelper::renderElement($this->root, $this->params);
