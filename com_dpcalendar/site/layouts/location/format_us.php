<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

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
	if (!empty($location->country_code_value)) {
		$buffer .= $location->country_code_value . ', ';
	}
	if (!empty($location->country) && empty($location->country_code_value)) {
		$buffer .= $location->country . ', ';
	}
	$buffer = trim($buffer, ', ');

	if ($index < count($locations) - 1) {
		$buffer .= '; ';
	}
}
echo $buffer;
