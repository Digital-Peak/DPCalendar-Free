<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;

// Allow access from same server
JFactory::getApplication()->setHeader('Access-Control-Allow-Origin', JURI::base());

// Load the required assets
DPCalendarHelper::loadLibrary(array('dpcalendar' => true));

JFactory::getDocument()->addStyleDeclaration("
#dp-calendar-list, 
#dp-calendar-calendar, 
#dp-calendar-map {
	width: 900px !important;
	margin: 0 auto 10px auto;
}");

// Remove the hrefs as they will be shown in the print window
JFactory::getDocument()->addScriptDeclaration("jQuery(document).ready(function() {
setInterval(function(){
	jQuery('a').removeAttr('href');
}, 2000);
})");

// User timezone
DPCalendarHelper::renderLayout('user.timezone', array('root' => $this->root));

// The text before content
$this->root->addChild(new Container('text-before'))->setContent(JHtml::_('content.prepare', JText::_($this->params->get('textbefore'))));

$this->params->set('use_hash', true);
$this->params->set('header_show_print', false);
$this->params->set('show_map', $this->params->get('show_map', 1) == 1);
$this->params->set('show_export_links', false);

// Load the calendar layout
DPCalendarHelper::renderLayout(
	'calendar.calendar',
	array(
		'params'            => $this->params,
		'root'              => $this->root,
		'calendars'         => $this->doNotListCalendars,
		'selectedCalendars' => $this->selectedCalendars
	)
);

// The text after content
$this->root->addChild(new Container('text-after'))->setContent(JHtml::_('content.prepare', JText::_($this->params->get('textafter'))));

// Render the tree
echo DPCalendarHelper::renderElement($this->root, $this->params);
