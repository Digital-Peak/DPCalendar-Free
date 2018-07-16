<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$locations = $displayData['locations'];

if (empty($locations)) {
	return '';
}

if (!is_array($locations)) {
	$locations = [$locations];
}

$buffer = '';
foreach ($locations as $index => $location) {
	if (!empty($location->number)) {
		$buffer .= $location->number . ' ';
	}
	if (!empty($location->street)) {
		$buffer .= $location->street . ', ';
	}
	if (!empty($location->city)) {
		$buffer .= $location->city . ', ';
	}
	if (!empty($location->province)) {
		$buffer .= $location->province . ' ';
	}
	if (!empty($location->zip)) {
		$buffer .= $location->zip . ', ';
	}
	if (!empty($location->country)) {
		$buffer .= $location->country . ', ';
	}
	$buffer = trim($buffer, ', ');

	if ($index < count($locations) - 1) {
		$buffer .= '; ';
	}
}
echo $buffer;
