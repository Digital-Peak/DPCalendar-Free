<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Button;
use CCL\Content\Element\Component\Icon;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\Heading;

// Global variables
$event  = $this->event;
$params = $this->params;

/** @var Container $root **/
$root = $this->root->addChild(new Container('actions-container', array('noprint')));
$root->setProtectedClass('noprint');

// The share container
$sc = $root->addChild(new Container('share'));

// Set up the share buttons
DPCalendarHelper::renderLayout('share.twitter', array('params' => $params, 'root' => $sc));
DPCalendarHelper::renderLayout('share.facebook', array('params' => $params, 'root' => $sc));
DPCalendarHelper::renderLayout('share.google', array('params' => $params, 'root' => $sc));
DPCalendarHelper::renderLayout('share.linkedin', array('params' => $params, 'root' => $sc));
DPCalendarHelper::renderLayout('share.xing', array('params' => $params, 'root' => $sc));

$bc = $root->addChild(new Container('user'));

// Add the print button
DPCalendarHelper::renderLayout(
	'content.button.print',
	array(
		'root'     => $bc,
		'id'       => 'print',
		'selector' => 'dp-event-container'
	)
);

// Compile the url fo the email button
require_once JPATH_SITE . '/components/com_mailto/helpers/mailto.php';
$uri = JUri::getInstance()->toString(array('scheme', 'host', 'port'));
$url = 'index.php?option=com_mailto&link=' . MailToHelper::addLink($uri . DPCalendarHelperRoute::getEventRoute($event->id, $event->catid, false, true));

// Create the email button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::MAIL,
		'root'    => $bc,
		'title'   => 'JGLOBAL_EMAIL',
		'onclick' => "window.open('" . $url ."')"
	)
);

if ($params->get('event_show_copy', '1'))
{
	// Compile the Google url
	$startDate  = DPCalendarHelper::getDate($event->start_date, $event->all_day);
	$endDate    = DPCalendarHelper::getDate($event->end_date, $event->all_day);
	$copyFormat = $event->all_day ? 'Ymd' : 'Ymd\THis';
	if ($event->all_day)
	{
		$endDate->modify('+1 day');
	}
	$url = 'http://www.google.com/calendar/render?action=TEMPLATE&text=' . urlencode($event->title);
	$url .= '&dates=' . $startDate->format($copyFormat, true) . '%2F' . $endDate->format($copyFormat, true);
	$url .= '&location=' . urlencode(\DPCalendar\Helper\Location::format($event->locations));
	$url .= '&details=' . urlencode(JHtml::_('string.truncate', $event->description, 200));
	$url .= '&hl=' . DPCalendarHelper::getFrLanguage() . '&ctz=' . $startDate->getTimezone()->getName();
	$url .= '&sf=true&output=xml';

	// Add the Google button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'id'      => 'google',
			'type'    => Icon::DOWNLOAD,
			'root'    => $bc,
			'title'   => 'COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COPY_GOOGLE',
			'onclick' => "window.open('" . $url ."')"
		)
	);

	// Add the ics button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'id'      => 'ics',
			'type'    => Icon::DOWNLOAD,
			'root'    => $bc,
			'title'   => 'COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COPY_OUTLOOK',
			'onclick' => "window.open('" . JRoute::_("index.php?option=com_dpcalendar&view=event&format=raw&id=" . $event->id) ."')"
		)
	);
}

if (\DPCalendar\Helper\Booking::openForBooking($event) && $event->params->get('access-invite') && !DPCalendarHelper::isFree() )
{
	// Add the invite button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'id'      => 'invite',
			'type'    => Icon::SIGNUP,
			'root'    => $bc,
			'text'    => 'COM_DPCALENDAR_INVITE',
			'onclick' => "location.href='" . DPCalendarHelperRoute::getInviteRoute($event) ."'"
		)
	);
}

if ($event->capacity != '0' && $event->params->get('access-tickets') && !DPCalendarHelper::isFree())
{
	// Add the tickets button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'id'      => 'tickets',
			'type'    => Icon::SIGNUP,
			'root'    => $bc,
			'text'    => 'COM_DPCALENDAR_BOOKING_FIELD_TICKETS_LABEL',
			'onclick' => "location.href='" . DPCalendarHelperRoute::getTicketsRoute(null, $event->id) ."'"
		)
	);
}

if ($event->params->get('access-edit'))
{
	// Add the tickets button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'type'    => Icon::EDIT,
			'root'    => $bc,
			'text'    => 'COM_DPCALENDAR_VIEW_FORM_BUTTON_EDIT_EVENT',
			'onclick' => "location.href='" . DPCalendarHelperRoute::getFormRoute($event->id, JUri::getInstance()) ."'"
		)
	);
}

if ($event->params->get('access-delete'))
{
	$return = clone JFactory::getURI();
	if ($this->input->getCmd('view', null) == 'event')
	{
		$return->setVar('layout', 'empty');
	}

	$deleteUrl = 'index.php?option=com_dpcalendar&task=event.delete&tmpl=' . $this->input->getWord('tmpl') . '&return=' . base64_encode($return) . '&e_id=';

	// Add the delete button
	DPCalendarHelper::renderLayout(
		'content.button',
		array(
			'id'      => 'delete',
			'type'    => Icon::DELETE,
			'root'    => $bc,
			'text'    => 'COM_DPCALENDAR_DELETE',
			'onclick' => "location.href='" . JRoute::_($deleteUrl . $event->id) ."'"
		)
	);

	if ($event->original_id > 0)
	{
		// Add the series delete button
		DPCalendarHelper::renderLayout(
			'content.button',
			array(
				'id'      => 'delete-series',
				'type'    => Icon::DELETE,
				'root'    => $bc,
				'text'    => 'COM_DPCALENDAR_DELETE_SERIES',
				'onclick' => "location.href='" . JRoute::_($deleteUrl . $event->original_id) ."'"
			)
		);
	}
}

// The heading of the page
$h = $root->addChild(new Heading('event-header', 2, array('dp-event-header'), array('itemprop' => 'name')));
$h->setProtectedClass('dp-event-header');

if (JFactory::getApplication()->input->get('tmpl') == 'component')
{
	// When we are shown in a modal dialog, make the title clickable
	$link = new Link('link', str_replace(array('?tmpl=component', 'tmpl=component'), '', DPCalendarHelperRoute::getEventRoute($event->id, $event->catid)), '_parent');
	$link->setContent($event->title);

	$h->addChild($link);
}
else
{
	// Add the title
	$h->setContent($event->title);
}
