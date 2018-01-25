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

$root = $displayData['root'];

// Add the dates
$root->addChild(new Meta('start-date', 'startDate', DPCalendarHelper::getDate($event->start_date, $event->all_day)->format('c')));
$root->addChild(new Meta('end-date', 'endDate', DPCalendarHelper::getDate($event->end_date, $event->all_day)->format('c')));
