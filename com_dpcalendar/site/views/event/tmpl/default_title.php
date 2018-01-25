<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\Heading;

// Global variables
$event = $this->event;

// The title to display
$title = $event->title;

// The heading of the page
$h = $this->root->addChild(new Heading('event-header', 2, array('dpcalendar-heading')));
$h->setProtectedClass('dpcalendar-heading');

if ($this->input->get('tmpl') == 'component') {
	$url = DPCalendarHelperRoute::getEventRoute($event->id, $event->catid);

	// When we are shown in a modal dialog, make the title clickable
	$link = new Link('link', str_replace(array('?tmpl=component', 'tmpl=component'), '', $url), '_parent');
	$link->setContent($title);

	$h->addChild($link);
} else {
	// Add the title
	$h->setContent($title);
}
