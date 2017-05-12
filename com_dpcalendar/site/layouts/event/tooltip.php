<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\Paragraph;

defined('_JEXEC') or die();

$event = $displayData['event'];
if (!$event) {
	return;
}
$params = $displayData['params'];
if (!$params) {
	$params = new JRegistry();
}

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

$l = $root->addChild(
	new Link('title', DPCalendarHelperRoute::getEventRoute($event->id, $event->catid), null, array('event-link'))
);
$l->setContent($event->title);

$p = $root->addChild(new Paragraph('date', array('date')));
$p->setContent(
	DPCalendarHelper::getDateStringFromEvent(
		$event,
		$params->get('event_date_format', 'm.d.Y'),
		$params->get('event_time_format', 'g:i a')
	)
);

if ($params->get('tooltip_show_description', 1)) {
	$root->addChild(new Container('content'))->setContent(JHtml::_('string.truncate', $event->description, 100));
}

$c = $root->addChild(new Container('links', array('links')));
if (\DPCalendar\Helper\Booking::openForBooking($event)) {
	$l = $c->addChild(
		new Link('book', JRoute::_(DPCalendarHelperRoute::getBookingFormRouteFromEvent($event, $return), false))
	);
	$l->setContent(JText::_('COM_DPCALENDAR_BOOK'));
}

$calendar = DPCalendarHelper::getCalendar($event->catid);
if ($calendar->canEdit || ($calendar->canEditOwn && $event->created_by == $user->id)) {
	$l = $c->addChild(new Link('book', JRoute::_(DPCalendarHelperRoute::getFormRoute($event->id, $return), false)));
	$l->setContent(JText::_('JACTION_EDIT'));
}
if ($calendar->canDelete || ($calendar->canEditOwn && $event->created_by == $user->id)) {
	$l = $c->addChild(
		new Link(
			'book',
			JRoute::_(
				'index.php?option=com_dpcalendar&task=event.delete&e_id=' . $event->id . '&return=' . base64_encode($return),
				false
			)
		)
	);
	$l->setContent(JText::_('JACTION_DELETE'));
}
