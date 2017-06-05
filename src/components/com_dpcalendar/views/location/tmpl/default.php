<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Basic\Paragraph;
use CCL\Content\Element\Basic\TextBlock;
use CCL\Content\Element\Basic\Link;

// Add the needed stylesheet
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/views/location/default.css', array(), true);
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/views/event/default.css', array(), true);

// The params
$params = $this->params;

// Get the location details
$params->set('link_title', 'external');
DPCalendarHelper::renderLayout('location.details', array('location' => $this->item, 'params' => $params, 'root' => $this->root));

// The heading of the upcoming events
$h = $this->root->addChild(new Heading('heading', 3, array('dp-event-header')));
$h->setProtectedClass('dp-event-header');
$h->setContent(JText::_('COM_DPCALENDAR_VIEW_PROFILE_UPCOMING_EVENTS'));

// Upcoming events container
$c = $this->root->addChild(new Container('upcoming-events'));

// Loop trough the events
foreach ($this->events as $event)
{
	$startDate = DPCalendarHelper::getDate($event->start_date, $event->all_day);

	// The event paragraph
	$p = $c->addChild(new Paragraph('event-' . $event->id, array(), array('style' => 'border-color:#' . $event->color, 'itemprop' => 'startDate', 'content' => $startDate->format('c'))));
	$p->addChild(new TextBlock('date-' . $event->id))->setContent(DPCalendarHelper::getDateStringFromEvent($event, $params->get('date_format'), $params->get('time_format')));

	// The link
	$l = $p->addChild(new Link('link-' . $event->id, DPCalendarHelperRoute::getEventRoute($event->id, $event->catid), array(), array('itemprop' => 'url')));

	// The title block
	$l->addChild(new TextBlock('title-' . $event->id, array(), array('itemprop' => 'name')))->setContent($event->title);
}

echo DPCalendarHelper::renderElement($this->root, $this->params);
