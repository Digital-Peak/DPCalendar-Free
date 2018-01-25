<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Link;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

$event = $displayData['event'];
if (!$event) {
	return;
}
$params = $displayData['params'];
if (!$params) {
	$params = new Registry();
}

// The user
$user = !empty($displayData['user']) ? $displayData['user'] : JFactory::getUser();

/** @var \CCL\Content\Element\Basic\Container $root */
$root = $displayData['root']->addChild(new Container('tooltip', array('tooltip')));

// Compile the return url
$return = JFactory::getApplication()->input->getInt('Itemid', null);
if (!empty($return)) {
	$uri    = clone JUri::getInstance();
	$uri    = $uri->toString(
		array(
			'scheme',
			'host',
			'port'
		)
	);
	$return = $uri . JRoute::_('index.php?Itemid=' . $return, false);
}

// Add the date
$p = $root->addChild(new Container('date', array('date')));
$p->setContent(
	DPCalendarHelper::getDateStringFromEvent(
		$event,
		$params->get('event_date_format', 'm.d.Y'),
		$params->get('event_time_format', 'g:i a')
	)
);

// Add the title
$l = $root->addChild(
	new Link('title', DPCalendarHelperRoute::getEventRoute($event->id, $event->catid), null, array('event-link'))
);
$l->setContent($event->title);

// Add the description
if ($params->get('tooltip_show_description', 1)) {
	$root->addChild(new Container('content'))->setContent(JHtml::_('string.truncate', $event->description, 100));
}

$c = $root->addChild(new Container('links', array('links')));

// Add the booking link when possible
if (\DPCalendar\Helper\Booking::openForBooking($event)) {
	$l = $c->addChild(
		new Link('book', JRoute::_(DPCalendarHelperRoute::getBookingFormRouteFromEvent($event, $return), false))
	);
	$l->setContent(JText::_('COM_DPCALENDAR_BOOK'));
}

$calendar = DPCalendarHelper::getCalendar($event->catid);

// Add the edit link when possible
if (($calendar->canEdit || ($calendar->canEditOwn && $event->created_by == $user->id))
	&& (!$event->checked_out || $user->id == $event->checked_out)) {
	$l = $c->addChild(new Link('edit', JRoute::_(DPCalendarHelperRoute::getFormRoute($event->id, $return), false)));
	$l->setContent(JText::_('JACTION_EDIT'));
}

// Add the delete link when possible
if ($calendar->canDelete || ($calendar->canEditOwn && $event->created_by == $user->id)) {
	$l = $c->addChild(
		new Link(
			'delete',
			JRoute::_(
				'index.php?option=com_dpcalendar&task=event.delete&e_id=' . $event->id . '&return=' . base64_encode($return),
				false
			)
		)
	);
	$l->setContent(JText::_('JACTION_DELETE'));
}
