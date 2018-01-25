<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Component\Badge;
use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\ListContainer;
use CCL\Content\Element\Basic\ListItem;
use CCL\Content\Element\Basic\TextBlock;
use CCL\Content\Element\Component\Icon;

// The params
$params = $displayData['params'];

/** @var Container $root */
$root = $displayData['root'];

// The return url
$return = JFactory::getApplication()->input->getInt('Itemid', null);
if ($return) {
	$return = JRoute::_('index.php?option=com_dpcalendar&Itemid=' . $return);
}

/** @var ListContainer $list * */
$list = $root->addChild(new ListContainer('events', ListContainer::UNORDERED));

// Loop over the events
foreach ($displayData['events'] as $event) {
	// The start date
	$startDate = DPCalendarHelper::getDate($event->start_date, $event->all_day);

	// The calendar
	$calendar = DPCalendarHelper::getCalendar($event->catid);

	// The list item container
	$item = $list->addListItem(new ListItem('event-' . $event->id));

	// Show the icon when activated
	if ($params->get('list_show_icon')) {
		// The calendar icon element
		$cal = $item->addChild(new Container('calendar-icon', array('calendar-icon')));
		$cal->addChild(new TextBlock('day', array('day')))->setContent($startDate->format('j', true));
		$m = $cal->addChild(new TextBlock('month', array('month')))->setContent($startDate->format('M', true));

		// Add per event the color for the calendar icon
		JFactory::getDocument()->addStyleDeclaration('#' . $m->getId() . ' {background-color: #' . $event->color . '; box-shadow: 0 2px 0 #' . $event->color . ';}');
	}

	// If possible add the book link
	if (\DPCalendar\Helper\Booking::openForBooking($event)) {
		$l = $item->addChild(new Link('book', DPCalendarHelperRoute::getBookingFormRouteFromEvent($event, $return)));
		$l->addChild(new Icon('book-icon', Icon::PLUS, array(), array('title' => JText::_('COM_DPCALENDAR_BOOK'))));
	}

	// If possible add the edit link
	if ($calendar->canEdit || ($calendar->canEditOwn && $event->created_by == $user->id)) {
		$l = $item->addChild(new Link('edit', DPCalendarHelperRoute::getFormRoute($event->id, $return)));
		$l->addChild(new Icon('book-icon', Icon::EDIT, array(), array('title' => JText::_('JACTION_EDIT'))));
	}

	// If possible add the delete link
	if ($calendar->canDelete || ($calendar->canEditOwn && $event->created_by == $user->id)) {
		$l = $item->addChild(
			new Link(
				'edit',
				JRoute::_('index.php?option=com_dpcalendar&task=event.delete&e_id=' . $event->id . '&return=' . base64_encode($return))
			)
		);
		$l->addChild(new Icon('book-icon', Icon::DELETE, array(), array('title' => JText::_('JACTION_DELETE'))));
	}

	if ($params->get('list_show_hits', 1)) {
		// The hits element
		$b = $item->addChild(new Badge('hits', array('hits')));
		$b->setContent(JText::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_HITS') . ':' . $event->hits);
	}

	// The title element with the link
	$t = $item->addChild(new TextBlock('title', array('title')));
	$l = $t->addChild(
		new Link(
			'link',
			DPCalendarHelperRoute::getEventRoute($event->id, $event->catid)
		)
	);
	$l->addChild(new TextBlock('title-text'))->setContent($event->title);

	// The date element
	$d = $item->addChild(
		new TextBlock(
			'date',
			array('date')
		)
	);
	$d->setContent('(' . JText::_('COM_DPCALENDAR_DATE') . ': ');
	$d->setContent(
		DPCalendarHelper::getDateStringFromEvent(
			$event,
			$params->get('event_date_format', 'm.d.Y'),
			$params->get('event_time_format', 'g:i a')
		),
		true
	);
	$d->setContent(')', true);

	// The calendar element
	$c = $item->addChild(new TextBlock('calendar', array('calendar')));
	$c->setContent(JText::_('COM_DPCALENDAR_CALENDAR') . ': ');
	$c->setContent($calendar != null ? $calendar->title : $event->catid, true);

	// The capacity element
	if ($event->capacity) {
		$c = $item->addChild(new TextBlock('capacity', array('capacity')));
		$c->setContent(JText::_('COM_DPCALENDAR_FIELD_CAPACITY_LABEL') . ': ' . ($event->capacity - $event->capacity_used) . '/' . (int)$event->capacity);
	}

	// The location elements
	if (isset($event->locations) && $event->locations) {
		// Deactivate the description in the tooltip layout
		$params->set('tooltip_show_description', false);

		// The locations container
		$ls = $item->addChild(
			new TextBlock(
				'locations',
				array('locations')
			)
		);
		foreach ($event->locations as $location) {
			// The link to the location
			$l = $ls->addChild(new Link($location->id, DPCalendarHelperRoute::getLocationRoute($location)));
			$l->addClass('location-details', true);
			$l->addAttribute('data-latitude', $location->latitude);
			$l->addAttribute('data-longitude', $location->longitude);
			$l->addAttribute('data-title', $location->title);
			$l->addAttribute('data-color', $event->color);
			$l->setContent($location->title);

			// The tooltip for the map
			$d = $ls->addChild(new TextBlock($location->id . '-description'));
			$d->addClass('location-description', true);
			DPCalendarHelper::renderLayout('event.tooltip', array('event' => $event, 'root' => $d, 'params' => $params));
		}
	}

	// Add the event schema
	DPCalendarHelper::renderLayout('schema.event', array('event' => $event, 'root' => $item));
}
