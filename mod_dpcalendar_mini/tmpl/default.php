<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;

$document = JFactory::getDocument();
$color    = $params->get('event_color', '135CAE');
$cssClass = '#dp-module-mini-' . $module->id . ' .dp-event-compact';
$document->addStyleDeclaration($cssClass . "," . $cssClass . " a," . $cssClass . " div{background-color:#" . $color . "; border-color: #" . $color . "} .fc-header-center{vertical-align: middle !important;} #dpcalendar_module_" . $module->id . " .fc-state-default span, #dpcalendar_module_" . $module->id . " .ui-state-default{padding:0px !important;}");
$document->addStyleDeclaration("#dp-module-mini-" . $module->id . " h2 {
	line-height: 20px;
	font-size: 19px;
}
#dp-module-mini-" . $module->id . " .dp-calendar-toggle {display: none}

#dp-popup-window-divider {
	margin: 0;
}

.dp-calendar .fc-day-grid-event > .fc-content {
	white-space: normal;
}");

$root = new Container('dp-module-mini-' . $module->id, array(), array('ccl-prefix' => 'dp-module-mini'));

$url = html_entity_decode(
	JRoute::_(
		'index.php?option=com_dpcalendar&view=events&limit=0&format=raw&my=' . $params->get('show_my_only_calendar', '0') .
		'&compact=' . $params->get('compact_events', 2) . '&ids=' . implode(',', $ids) . '&openview=' . $params->get('open_view', 'agendaDay')
	)
);

$params->set('header_show_datepicker', false);
$params->set('header_show_print', false);
$params->set('show_map', false);
$params->set('show_compact_events', $params->get('compact_events', 2) == 1);
$params->set('use_hash', false);
$params->set('event_create_form', 0);
$params->set('screen_size_list_view', 0);

// Load the calendar layout
DPCalendarHelper::renderLayout(
	'calendar.calendar',
	array(
		'params'            => $params,
		'root'              => $root,
		'selectedCalendars' => array($url)
	)
);

echo DPCalendarHelper::renderElement($root, $params);
