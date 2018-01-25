<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Meta;

// The locations to display
$event = $displayData['event'];
if (!$event) {
	return;
}

// Add the title as name
$displayData['root']->addChild(new Meta('name', 'name', $event->title));
