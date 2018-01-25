<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Link;
use CCL\Content\Element\Basic\Image;


// The event to show the images from
$event = $displayData['event'];
if (!$event || !isset($event->images)) {
	return;
}

// The images of the event
$images = json_decode($event->images);
if (!$images) {
	// Nothing to set up
	return;
}

/** @var Container $root * */
$root = $displayData['root'];

// Check if we have images in the data
$hasImage = false;
for ($i = 1; $i <= 3; $i++) {
	if (!isset($images->{'image' . $i})) {
		continue;
	}

	$imagePath = $images->{'image' . $i};
	if (!$imagePath) {
		continue;
	}

	$hasImage = true;
}

if (!$hasImage) {
	return '';
}

$eventLink = null;
if (isset($displayData['linkImages']) && $displayData['linkImages']) {
	// Should the images link to the event
	$eventLink = DPCalendarHelperRoute::getEventRoute($event->id, $event->catid);
}

// The container for the images
$c = $root->addChild(new Container('details-images'));

// Loop trough the images
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

	// Get the caption
	$caption = '';
	if (isset($images->{'image' . $i . '_caption'})) {
		$caption = $images->{'image' . $i . '_caption'};
	}
	if ($caption) {
		JHtml::_('behavior.caption');
		$caption = 'class="caption" title="' . htmlspecialchars($caption) . '" width="auto"';
	}

	// The actual container which holds the image
	$imageContainer = $c->addChild(new Container('image-' . $i . '-container', array('image-container')));

	if ($eventLink) {
		// The image should link to the event
		$imageContainer = $imageContainer->addChild(new Link('image-' . $i . '-link', $eventLink, array('image-link')));
	}

	// Set up the image element and add it to the container
	$imageContainer->addChild(
		new Image(
			'image-' . $i,
			$imagePath,
			isset($images->{'image' . $i . '_alt'}) ? htmlspecialchars($images->{'image' . $i . '_alt'}) : '',
			array('image')
		)
	);
}
