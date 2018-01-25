<?php

/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Component\Grid;
use CCL\Content\Element\Component\Grid\Row;
use CCL\Content\Element\Component\Grid\Column;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\DescriptionListHorizontal;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Description\Term;
use CCL\Content\Element\Basic\Description\Description;
use CCL\Content\Element\Basic\TextBlock;

// Global parameters
$params    = $this->params;
$event     = $this->event;
$calendar  = DPCalendarHelper::getCalendar($event->catid);
$startDate = DPCalendarHelper::getDate($event->start_date, $event->all_day);

// The root container
$root = $this->root->addChild(new Container('information'));

/** @var Grid $grid */
$grid = $root->addChild(new Grid('content', array('dpcalendar-locations-container')));
$grid->setProtectedClass('dpcalendar-locations-container');

/** @var Row $row */
$row = $grid->addRow(new Row('details'));

/** @var Column $column */
$column = $row->addColumn(new Column('data', 100));

// Add the calendar information
if ($params->get('event_show_calendar', '1')) {
	// Create the calendar link
	$calendarLink = DPCalendarHelperRoute::getCalendarRoute($event->catid);
	if ($calendarLink) {
		if ($params->get('event_show_calendar', '1') == '2') {
			// Link to month
			$calendarLink = $calendarLink .
				'#year=' . $startDate->format('Y', true) .
				'&month=' . $startDate->format('m', true) .
				'&day=' . $startDate->format('d', true);
		}
		// Add the link
		$content = new Link('url', JRoute::_($calendarLink), '_parent');
		$content->setContent($calendar->title);
	} else {
		// Set the name as content of the description
		$content = $calendar != null ? $calendar->title : $event->catid;
	}
	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $column, 'id' => 'calendar', 'label' => 'COM_DPCALENDAR_CALENDAR', 'content' => $content)
	);
}

// Add date
if ($params->get('event_show_date', '1')) {
	// Add a link to the url
	$start = new TextBlock('start-date');
	$start->setContent(
		DPCalendarHelper::getDateStringFromEvent($event, $params->get('event_date_format', 'm.d.Y'), $params->get('event_time_format', 'g:i a'))
	);

	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $column, 'id' => 'date', 'label' => 'COM_DPCALENDAR_DATE', 'content' => $start)
	);
}

// Add location information
if ($event->locations && $params->get('event_show_location', '2')) {
	$locations = array();
	foreach ($event->locations as $location) {
		// The container which holds the location data
		$lc = new Container(
			$location->id,
			array(
				'location',
				'location-details'
			),
			array(
				'data-latitude'    => $location->latitude,
				'data-longitude'   => $location->longitude,
				'data-title'       => $location->title,
				'data-description' => '<a href="' . DPCalendarHelperRoute::getLocationRoute($location) . '">' . $location->title . '</a>',
				'data-color'       => \DPCalendar\Helper\Location::getColor($location)
			)
		);
		$lc->setProtectedClass('location-details');

		$rooms = [];
		if (!empty($event->rooms)) {
			foreach ($event->rooms as $room) {
				list($locationId, $roomId) = explode('-', $room, 2);
				foreach ($location->rooms as $lroom) {
					if ($locationId != $location->id || $roomId != $lroom->id) {
						continue;
					}

					$rooms[$lroom->id] = $lroom->title;
				}
			}
		}

		if ($rooms) {
			$rooms = ' [' . implode(', ', $rooms) . ']';
		} else {
			$rooms = '';
		}

		if ($params->get('event_show_location', '2') == '1') {
			// Link to the location view
			$lc->addChild(new Link('link', DPCalendarHelperRoute::getLocationRoute($location)))->setContent($location->title . $rooms);
		} else if ($params->get('event_show_location', '2') == '2') {
			// Link to the location details on the same page
			$lc->addChild(new Link('link',
				$this->escape(JUri::getInstance()) . '#dp-event-locations-' . $location->id))->setContent($location->title . $rooms);
		}

		$locations[] = $lc;
	}

	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $column, 'id' => 'location', 'label' => 'COM_DPCALENDAR_LOCATION', 'content' => $locations)
	);
}

// Author
$author = JFactory::getUser($event->created_by);
if ($author && !$author->guest && $params->get('event_show_author', '1')) {
	// The description list
	$dl = $column->addChild(new DescriptionListHorizontal('author'));

	// Set the term
	$t = $dl->setTerm(new Term('label', array('label')));
	$t->addClass('dpcalendar-label', true);
	$t->setContent(JText::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_AUTHOR'));
	$desc = $dl->setDescription(new Description('description', array('content')));

	// Set the author information as content
	$desc->setContent($event->created_by_alias ? $event->created_by_alias : $author->name);

	if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php')) {
		// Set the community builder username as content
		include_once(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php');
		$cbUser = CBuser::getInstance($event->created_by);
		if ($cbUser) {
			$desc->setContent($cbUser->getField('formatname', null, 'html', 'none', 'list', 0, true));
		}
	} else if (isset($event->contactid) && !empty($event->contactid)) {
		// Link to the contact
		$needle  = 'index.php?option=com_contact&view=contact&id=' . $event->contactid;
		$item    = JFactory::getApplication()->getMenu()->getItems('link', $needle, true);
		$cntlink = !empty($item) ? $needle . '&Itemid=' . $item->id : $needle;
		$desc->addChild(new Link('link', JRoute::_($cntlink)))->setContent($desc->getContent());
		$desc->setContent('');
	}

	if ($avatar = DPCalendarHelper::getAvatar($author->id, $author->email, $params)) {
		// Show the avatar
		$desc->addChild(new Container('avatar'))->setContent($avatar);
	}
}

// Add url
if ($event->url && $params->get('event_show_url', '1')) {
	$u      = JUri::getInstance($event->url);
	$target = null;
	if ($u->getHost() && JUri::getInstance()->getHost() != $u->getHost()) {
		$target = '_blank';
	}

	// Add a link to the url
	$content = new Link('link', $event->url, $target);
	$content->setContent($event->url);

	DPCalendarHelper::renderLayout(
		'content.dl',
		array('root' => $column, 'id' => 'url', 'label' => 'COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_URL', 'content' => $content)
	);
}

// Information column
$metaColumn = new Column('metadata', 40);

if ($params->get('event_show_images', '1')) {
	// Show the images
	DPCalendarHelper::renderLayout('event.images', array('event' => $event, 'root' => $metaColumn));
}

if ($event->locations && $params->get('event_show_map', '1') == '1' && $params->get('event_show_location', '2') == '1') {
	// Add the map container
	$map = $metaColumn->addChild(
		new Element(
			'details-map',
			array('dpcalendar-map', 'dpcalendar-fixed-map'),
			array(
				'data-zoom'        => $params->get('event_map_zoom', 4),
				'data-latitude'    => $params->get('event_map_lat', 47),
				'data-longitude'   => $params->get('event_map_long', 4),
				'data-color'       => $event->color,
				'data-title'       => $location->title,
				'data-description' => '<a href="' . DPCalendarHelperRoute::getLocationRoute($location) . '">' . $location->title . '</a>',
			)
		)
	);
	$map->setProtectedClass('dpcalendar-map');
	$map->setProtectedClass('dpcalendar-fixed-map');
}

// If there are childs in the second column, add it and make the first one smaller
if ($metaColumn->getChildren()) {
	$column->setWidth(60);
	$row->addColumn($metaColumn);
}
