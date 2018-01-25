<?php
/**
 *
 * @package DPCalendar
 * @author Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Heading;

// Create the container
$root = new Container('comments', array('noprint'));
$root->setProtectedClass('noprint');

// Add the heading
$h = $root->addChild(new Heading('heading', 3, array('dpcalendar-heading')));
$h->setProtectedClass('dpcalendar-heading');
$h->setContent(JText::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COMMENTS'));

// The comments container
$cc = $root->addChild(new Container('container'));

// Call the comment layouts
DPCalendarHelper::renderLayout('comment.facebook',  array('params' => $this->params, 'root' => $cc));
DPCalendarHelper::renderLayout('comment.google',    array('params' => $this->params, 'root' => $cc));
DPCalendarHelper::renderLayout('comment.jcomments', array('params' => $this->params, 'root' => $cc, 'event' => $this->event));

if (!$cc->getChildren())
{
	// Nothing to add
	return;
}

// Add it to the global root
$this->root->addChild($root);
