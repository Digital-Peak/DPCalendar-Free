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

if (!$this->params->get('event_show_tickets', 0) || !isset($this->event->tickets) || !$this->event->tickets)
{
	// Return when no tickets are available or should not be shown
	return;
}

// Set some parameters for the layout
$this->params->set('display_list_event', false);
$this->params->set('display_list_date', false);

/** @var Container $root **/
$root = $this->root->addChild(new Container('container'));

// The heading
$h = $root->addChild(new Heading('heading', 3, array('dpcalendar-heading')));
$h->setProtectedClass('dpcalendar-heading');
$h->setContent(JText::_('COM_DPCALENDAR_VIEW_EVENT_TICKETS_LABEL'));

// Fille the event container with the tickets list
DPCalendarHelper::renderLayout(
	'tickets.list',
	array(
		'tickets'      => $this->event->tickets,
		'params'       => $this->params,
		'root'         => $root
	)
);
