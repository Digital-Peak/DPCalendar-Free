<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;

// Set up global variables
$params = $displayData['params'];
$event  = $displayData['event'];

if ($params->get('comment_system') != 'jcomments' || $event == null || !is_numeric($event->id)) {
	// Nothing to set up
	return;
}

$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';
if (!JFile::exists($comments)) {
	// JComments is not installed
	return;
}

// Load JComments
require_once $comments;

// Create the JComments container
$ce = $displayData['root']->addChild(new Container('jcomments'));
$ce->setContent(JComments::showComments($event->id, 'com_dpcalendar', $event->title));
