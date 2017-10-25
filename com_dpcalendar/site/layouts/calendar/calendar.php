<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Image;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Form\Input;

// Load the JS libraries
DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true, 'fullcalendar' => true));
JHtml::_('script', 'system/core.js', false, true);
JHtml::_('script', 'com_dpcalendar/jquery/ext/jquery.tooltipster.min.js', false, true);
JHtml::_('stylesheet', 'com_dpcalendar/jquery/ext/tooltipster.css', array(), true);

JHtml::_('script', 'com_dpcalendar/dpcalendar/calendar.js', false, true);
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/views/calendar/default.css', array(), true);

// Set up the params
$params = $displayData['params'];

// The root element
$root = $displayData['root'];

// Activate modal behavior when needed
if ($params->get('show_event_as_popup')) {
	JHtml::_('behavior.modal', 'a.fc-event');
}

// The loader image
$l = $root->addChild(new Container('loader'));
$l->addClass('dpcalendar-loader', true);
$l->addChild(new Image('image', 'media/com_dpcalendar/images/site/ajax-loader.gif', 'loader'));

// Load the calendarlist above the calendar view
DPCalendarHelper::renderLayout('calendar.list', $displayData);

// The element which holds the calendar
$c = $root->addChild(
	new Element(
		'calendar',
		array(),
		array('data-popupwidth' => $params->get('popup_width', 700), 'data-popupheight' => $params->get('popup_height', 500))
	)
);
$c->addClass('dp-calendar', true);

// Load the calendar options
$displayData['id'] = $c->getId();

$js = DPCalendarHelper::renderLayout('calendar.options', $displayData);
JFactory::getDocument()->addScriptDeclaration($js);

// The datepicker input
$root->addChild(new Input('date-picker', 'hidden', 'date-picker'));

// Add quick add
if (DPCalendarHelper::canCreateEvent() && $params->get('event_create_form', 1)) {
	DPCalendarHelper::renderLayout('calendar.quickadd', $displayData);
}

// Load the map
DPCalendarHelper::renderLayout('calendar.map', $displayData);

if ($params->get('echo_js_code')) {
	echo $js;
}
