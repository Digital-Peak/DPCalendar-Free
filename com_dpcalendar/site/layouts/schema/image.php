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

// The images of the event
$images = json_decode($event->images);
if (!$images) {
	// Nothing to set up
	return;
}

for ($i = 1; $i <= 3; $i++) {
	if (!isset($images->{'image' . $i})) {
		// Image is empty, nothing todo
		continue;
	}

	// Get the image path
	$imagePath = $images->{'image' . $i};
	if (!$imagePath) {
		continue;
	}

	// Set up the root container
	$displayData['root']->addChild(new Meta('image-' . $i, 'image', trim(JUri::base(), '/') . '/' . $imagePath));
}
