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

// Load the required CSS files
JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/views/calendar/default.min.css', ['relative' => true]);

// User timezone
DPCalendarHelper::renderLayout('user.timezone', array('root' => $this->root));

// Set up the share buttons
$sc = $this->root->addChild(new Container('share'));
DPCalendarHelper::renderLayout('share.twitter',  array('params' => $this->params, 'root' => $sc));
DPCalendarHelper::renderLayout('share.facebook', array('params' => $this->params, 'root' => $sc));
DPCalendarHelper::renderLayout('share.google',   array('params' => $this->params, 'root' => $sc));
DPCalendarHelper::renderLayout('share.linkedin', array('params' => $this->params, 'root' => $sc));
DPCalendarHelper::renderLayout('share.xing',     array('params' => $this->params, 'root' => $sc));

// The text before content
$this->root->addChild(new Container('text-before'))->setContent(JHtml::_('content.prepare', JText::_($this->params->get('textbefore'))));

$this->params->set('use_hash', true);

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

// The comments container
$cc = $this->root->addChild(new Container('comments', array('noprint')));
$cc->setProtectedClass('noprint');

// Call the comment layouts
DPCalendarHelper::renderLayout('comment.facebook',  array('params' => $this->params, 'root' => $cc));
DPCalendarHelper::renderLayout('comment.google',    array('params' => $this->params, 'root' => $cc));

// Render the tree
echo DPCalendarHelper::renderElement($this->root, $this->params);
