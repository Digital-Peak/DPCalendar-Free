<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;

// Load the needed javascript libraries
DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true));

// Load the event stylesheet
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/views/event/default.css', array(), true);

// The params
$params = $this->params;

// Load the maps javascript when needed
if ($params->get('event_show_map', '1')) {
	DPCalendarHelper::loadLibrary(array('maps' => true));
}

// The event
$event = $this->event;

// Load the dpcalendar plugins
JPluginHelper::importPlugin('dpcalendar');

// User timezone
DPCalendarHelper::renderLayout('user.timezone', array('root' => $this->root));

// Add the schema data to the root container
$this->root->addAttribute('itemscope', 'itemscope');
$this->root->addAttribute('itemtype', 'http://schema.org/Event');

// The text before content
$text = JHtml::_('content.prepare', $params->get('event_textbefore'));
if ($text) {
	$this->root->addChild(new Container('text-before'))->setContent($text);
}

// The before event content
$text = implode(' ', JDispatcher::getInstance()->trigger('onEventBeforeDisplay', array(&$event)));
if ($text) {
	$this->root->addChild(new Container('event-before-display'))->setContent($text);
}

// Header with buttons and title
$this->loadTemplate('header');

// Joomla event
$text = $event->displayEvent->afterDisplayTitle;
if ($text) {
	$this->root->addChild(new Container('after-display-title'))->setContent($text);
}

// Informations like date calendar
$this->loadTemplate('information');

// Contains custom fields
$text = $event->displayEvent->beforeDisplayContent;
if ($text) {
	$this->root->addChild(new Container('before-display-content'))->setContent($text);
}

// Tags
$text = JLayoutHelper::render('joomla.content.tags', $event->tags->itemTags);
if ($text) {
	$this->root->addChild(new Container('tags'))->setContent($text);
}

// Booking details when available
$this->loadTemplate('bookings');

// Attendees
$this->loadTemplate('tickets');

// Description
$this->loadTemplate('description');

// Joomla event
$text = $event->displayEvent->afterDisplayContent;
if ($text) {
	$this->root->addChild(new Container('after-display-content'))->setContent($text);
}

// Locations detail information
$this->loadTemplate('locations');

// Load the comments
$this->loadTemplate('comments');

// After event trigger
$text = implode(' ', JDispatcher::getInstance()->trigger('onEventAfterDisplay', array(&$event)));
if ($text) {
	$this->root->addChild(new Container('event-after-display'))->setContent($text);
}

// The text after parameter
$text = JHtml::_('content.prepare', $params->get('event_textafter'));
if ($text) {
	$this->root->addChild(new Container('text-after'))->setContent($text);
}

// Render the tree
echo DPCalendarHelper::renderElement($this->root, $this->params);
