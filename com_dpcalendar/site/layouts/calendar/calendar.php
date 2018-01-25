<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Image;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Form\Input;

// Load the JS libraries
JHtml::_('behavior.core');
DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true, 'url' => true));
JHtml::_('script', 'com_dpcalendar/md5/md5.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('script', 'com_dpcalendar/popper/popper.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('script', 'com_dpcalendar/tippy/tippy.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('stylesheet', 'com_dpcalendar/tippy/tippy.min.css', ['relative' => true]);

JHtml::_('script', 'com_dpcalendar/moment/moment.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('script', 'com_dpcalendar/fullcalendar/fullcalendar.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('stylesheet', 'com_dpcalendar/fullcalendar/fullcalendar.min.css', ['relative' => true]);

JHtml::_('script', 'com_dpcalendar/dpcalendar/calendar.min.js', ['relative' => true], ['defer' => true]);
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/layouts/calendar/calendar.min.css', ['relative' => true]);

// Set up the params
$params = $displayData['params'];

// The root element
$root = $displayData['root'];

// Activate modal behavior when needed
if ($params->get('show_event_as_popup')) {
	DPCalendarHelper::loadLibrary(array('modal' => true));
}

// Load the spinning wheel
DPCalendarHelper::renderLayout('calendar.loader', $displayData);

// Load the calendarlist above the calendar view
DPCalendarHelper::renderLayout('calendar.list', $displayData);

// The element which holds the calendar
$c = $root->addChild(
	new Element(
		'calendar',
		array(),
		array('data-popupwidth' => $params->get('popup_width'), 'data-popupheight' => $params->get('popup_height', 500))
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
