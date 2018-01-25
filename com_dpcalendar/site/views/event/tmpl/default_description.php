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

if (!$this->event->description)
{
	// When no description, then don't render it
	return;
}

// Create the facebook comments box element
$root = $this->root->addChild(new Container('container'));

// Thea heading of the page
$h = $root->addChild(new Heading('heading', 3, array('dpcalendar-heading')));
$h->setProtectedClass('dpcalendar-heading');
$h->setContent(JText::_('COM_DPCALENDAR_DESCRIPTION'));

// The container with the event description
$desc = $root->addChild(new Container('content'));

try
{
	// Set the event description as content
	$desc->setContent(JHTML::_('content.prepare', $this->event->description));
}
catch (Exception $e)
{
	// Description is somehow not valid, add a warning
	JFactory::getApplication()->enqueueMessage(nl2br($this->escape($e->getMessage())), 'error');
}
