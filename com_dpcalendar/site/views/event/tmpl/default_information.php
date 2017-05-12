<?php

/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
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
use CCL\Content\Element\Basic\Meta;

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
$column = $row->addColumn(new Column('data', 60), array(), array('itemprop' => 'url', 'content' => DPCalendarHelperRoute::getEventRoute($event->id, $event->catid, true, true)));

// Add the calendar information
if ($params->get('event_show_calendar', '1'))
{
	// Create the calendar link
	$content = DPCalendarHelperRoute::getCalendarRoute($event->catid);
	if ($content)
	{
		if ($params->get('event_show_calendar', '1') == '2')
		{
			// Link to month
			$content = $calendarLink .
				'#year=' . $startDate->format('Y', true) .
				'&month=' . $startDate->format('m', true) .
				'&day=' . $startDate->format('d', true);
		}
		// Add the link
		$content = new Link('url', JRoute::_($content), '_parent');
		$content->setContent($calendar->title);
	}
	else
	{
		// Set the name as content of the description
		$content = $calendar != null ? $calendar->title : $event->catid;
	}
	DPCalendarHelper::renderLayout('content.dl', array('root' => $column, 'id' => 'calendar', 'label' => 'COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_CALANDAR', 'content' => $content));
}

// Add date
if ($params->get('event_show_date', '1'))
{
	// Add a link to the url
	$start = new TextBlock('start-date', array(), array('itemprop' => 'startDate', 'content' => $startDate->format('c')));
	$start->setContent(DPCalendarHelper::getDateStringFromEvent($event, $params->get('event_date_format', 'm.d.Y'), $params->get('event_time_format', 'g:i a')));

	$end = new Meta('end-date', 'endDate', DPCalendarHelper::getDate($event->end_date, $event->all_day)->format('c'));

	DPCalendarHelper::renderLayout('content.dl', array('root' => $column, 'id' => 'url', 'label' => 'COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_DATE', 'content' => array($start, $end)));
}

// Add location information
if ($event->locations && $params->get('event_show_location', '2'))
{
	$locations = array();
	foreach ($event->locations as $location)
	{
		// The container which holds the location data
		$lc = new Container(
			$location->id,
			array(
				'location',
				'location-details'
			),
			array(
				'data-latitude' => $location->latitude,
				'data-longitude' => $location->longitude,
				'data-title' => $location->title,
				'data-color' => \DPCalendar\Helper\Location::getColor($location),
			)
		);
		$lc->setProtectedClass('location-details');

		if ($params->get('event_show_location', '2') == '1')
		{
			// Link to the location view
			$lc->addChild(new Link('link', DPCalendarHelperRoute::getLocationRoute($location)))->setContent($location->title);
		}
		else if ($params->get('event_show_location', '2') == '2')
		{
			// Link to the location details on the same page
			$lc->addChild(new Link('link', '#' . $location->alias))->setContent($location->title);
		}

		// Add the location schema
		DPCalendarHelper::renderLayout('schema.location', array('locations' => array($location), 'root' => $lc));

		$locations[] = $lc;
	}

	DPCalendarHelper::renderLayout('content.dl', array('root' => $column, 'id' => 'location', 'label' => 'COM_DPCALENDAR_LOCATION', 'content' => $locations));
}

// Author
$author = JFactory::getUser($event->created_by);
if ($author && !$author->guest && $params->get('event_show_author', '1'))
{
	// The description list
	$dl = $column->addChild(new DescriptionListHorizontal('author'));
	$dl->setTerm(new Term('label', array('label')))->setContent(JText::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_AUTHOR'));
	$desc = $dl->setDescription(new Description('description', array('content'), array('itemprop' => 'performer')));

	// Set the author information as content
	$desc->setContent($event->created_by_alias ? $event->created_by_alias : $author->name);

	if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php'))
	{
		// Set the community builder username as content
		include_once (JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php');
		$cbUser = CBuser::getInstance($event->created_by);
		if ($cbUser)
		{
			$desc->setContent($cbUser->getField('formatname', null, 'html', 'none', 'list', 0, true));
		}
	}
	else if (isset($event->contactid) && !empty($event->contactid))
	{
		// Link to the contact
		$needle = 'index.php?option=com_contact&view=contact&id=' . $event->contactid;
		$item = JFactory::getApplication()->getMenu()->getItems('link', $needle, true);
		$cntlink = !empty($item) ? $needle . '&Itemid=' . $item->id : $needle;
		$desc->addChild(new Link('link', JRoute::_($cntlink)))->setContent($desc->getContent());
		$desc->setContent('');
	}

	if ($avatar = DPCalendarHelper::getAvatar($author->id, $author->email, $params))
	{
		// Show the avatar
		$desc->addChild(new Container('avatar'))->setContent($avatar);
	}
}

// Add url
if ($event->url && $params->get('event_show_url', '1'))
{
	// Add a link to the url
	$content = new Link('link', $event->url, null, array(), array('itemprop' => 'url'));
	$content->setContent($event->url);

	DPCalendarHelper::renderLayout('content.dl', array('root' => $column, 'id' => 'url', 'label' => 'COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_URL', 'content' => $content));
}

// Information column
$column = $row->addColumn(new Column('metadata', 40));

if ($params->get('event_show_images', '1'))
{
	// Show the images
	DPCalendarHelper::renderLayout('event.images', array('event' => $event, 'root' => $column));
}

if ($event->locations && $params->get('event_show_map', '1') == '1' && $params->get('event_show_location', '2') == '1')
{
	// Add the map container
	$map = $column->addChild(
		new Element(
			'details-map',
			array('dpcalendar-map', 'dpcalendar-fixed-map'),
			array(
				'data-zoom'      => $params->get('event_map_zoom', 4),
				'data-latitude'  => $params->get('event_map_lat', 47),
				'data-longitude' => $params->get('event_map_long', 4),
				'data-color'     => $event->color
			)
		)
	);
	$map->setProtectedClass('dpcalendar-map');
	$map->setProtectedClass('dpcalendar-fixed-map');
}
