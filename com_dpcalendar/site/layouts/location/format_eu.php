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
	$locations = array($locations);
}
$buffer = '';
foreach ($locations as $index => $location) {
	if (!empty($location->street)) {
		$buffer .= $location->street . (!empty($location->number) ? ' ' . $location->number : '') . ', ';
	}
	if (!empty($location->city)) {
		$buffer .= (!empty($location->zip) ? $location->zip . ' ' : '') . $location->city . ', ';
	}
	if (!empty($location->province)) {
		$buffer .= $location->province . ', ';
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
