<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Basic\Paragraph;
use CCL\Content\Element\Basic\TextBlock;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Component\Icon;

// Add the needed stylesheet
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/views/location/default.css', ['relative' => true]);
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/dpcalendar.css', ['relative' => true]);

// The params
$params = $this->params;

// The heading
$h = $this->root->addChild(new Heading('header', 2, array('dpcalendar-heading')));
$h->setProtectedClass('dpcalendar-heading');

// Allow to edit
if (JFactory::getUser()->authorise('core.edit', 'com_dpcalendar')) {
	$l = $h->addChild(new Link('edit-icon', DPCalendarHelperRoute::getLocationFormRoute($this->item->id, JUri::getInstance())));
	$l->addChild(new Icon('location-edit-icon', Icon::EDIT));
}

$l = $h->addChild(new Link('external', \DPCalendar\Helper\Location::getMapLink($this->item), '_blank'));
$l->setContent($this->item->title);

// The resource view
if ($params->get('location_show_resource_view', 1) && !\DPCalendar\Helper\DPCalendarHelper::isFree()) {
	$resourceParams = clone $params;

	// Some defaults for the calendar
	$resourceParams->set('header_show_datepicker', $params->get('location_header_show_datepicker', 1));
	$resourceParams->set('header_show_title', $params->get('location_header_show_title', 1));
	$resourceParams->set('header_show_month', false);
	$resourceParams->set('header_show_week', false);
	$resourceParams->set('header_show_day', false);
	$resourceParams->set('header_show_list', false);
	$resourceParams->set('header_show_timeline_day', true);
	$resourceParams->set('header_show_timeline_week', true);
	$resourceParams->set('header_show_timeline_month', true);
	$resourceParams->set('header_show_timeline_year', true);
	$resourceParams->set('timeformat_timeline_year', $params->get('location_timeformat_year', 'g:i a'));
	$resourceParams->set('timeformat_timeline_month', $params->get('location_timeformat_month', 'g:i a'));
	$resourceParams->set('timeformat_timeline_week', $params->get('location_timeformat_week', 'g:i a'));
	$resourceParams->set('timeformat_timeline_day', $params->get('location_timeformat_day', 'g:i a'));
	$resourceParams->set('show_selection', false);
	$resourceParams->set('show_map', false);
	$resourceParams->set('default_view', $params->get('location_default_view', 'resday'));
	$resourceParams->set('use_hash', true);
	$resourceParams->set('event_create_form', 2);
	$resourceParams->set('screen_size_list_view', 0);

	// Resource specific options
	$resourceParams->set('calendar_filter_locations', array($this->item->id));
	$resourceParams->set('calendar_resource_views', ['timeline']);

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

// The map
$c = $this->root->addChild(new Container('map'));
$params->set('show_title', false);
$params->set('show_details', false);
$params->set('show_map', true);
DPCalendarHelper::renderLayout('location.details', array('location' => $this->item, 'params' => $params, 'root' => $c));

// Get the location details
if ($params->get('location_expand', 1)) {
	// The heading
	$h = $this->root->addChild(new Heading('info-header', 3, array('dpcalendar-heading')));
	$h->setProtectedClass('dpcalendar-heading');
	$h->setContent(JText::_('COM_DPCALENDAR_VIEW_EVENT_LOCATION_INFORMATION'));

	// The details
	$c = $this->root->addChild(new Container('details'));
	$params->set('show_title', false);
	$params->set('show_details', true);
	$params->set('show_map', false);
	DPCalendarHelper::renderLayout('location.details', array('location' => $this->item, 'params' => $params, 'root' => $c));
}

// The upcoming events view
if ($params->get('location_show_upcoming_events', 1)) {
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

echo DPCalendarHelper::renderElement($this->root, $this->params);
