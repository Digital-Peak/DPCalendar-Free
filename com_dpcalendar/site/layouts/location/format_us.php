<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$locations = $displayData['locations'];

if (empty($locations))
{
	return '';
}
if (! is_array($locations))
{
	$locations = array(
			$locations
	);
}
$buffer = '';
foreach ($locations as $index => $location)
{
	if (isset($location->street) && ! empty($location->street))
	{
		$buffer .= (isset($location->number) && ! empty($location->number) ? $location->number . ' ' : '') . $location->street . ', ';
	}
	if (isset($location->city) && ! empty($location->city))
	{
		$buffer .= (isset($location->zip) && ! empty($location->zip) ? $location->zip . ' ' : '') . $location->city . ', ';
	}
	if (isset($location->province) && ! empty($location->province))
	{
		$buffer .= $location->province . ', ';
	}
	if (isset($location->country) && ! empty($location->country))
	{
		$buffer .= $location->country . ', ';
	}
	$buffer = trim($buffer, ', ');

	if ($index < count($locations) - 1)
	{
		$buffer .= '; ';
	}
}
echo $buffer;
