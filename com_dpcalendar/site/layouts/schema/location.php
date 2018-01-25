<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Meta;

// The locations to display
$locations = $displayData['locations'];
if (!$locations) {
	return;
}

// Set up the root container
$root = $displayData['root']->addChild(new Container('schema-location'));

foreach ((array)$locations as $location) {
	// Create the location container
	$c = $root->addChild(
		new Container(
			'container',
			array(),
			array(
				'itemscope' => 'itemscope',
				'itemtype'  => 'https://schema.org/Place',
				'itemprop'  => 'location'
			)
		)
	);

	// Add the name meta tag
	$c->addChild(new Meta('name', 'name', $location->title));

	// Add the container for the location details
	$c = $c->addChild(
		new Container(
			'address',
			array(),
			array(
				'itemscope' => 'itemscope',
				'itemtype'  => 'https://schema.org/PostalAddress',
				'itemprop'  => 'address'
			)
		)
	);

	if (isset($location->city) && $location->city) {
		$c->addChild(new Meta('address-city', 'addressLocality', $location->city));
	}
	if (isset($location->province) && $location->province) {
		$c->addChild(new Meta('address-province', 'addressRegion', $location->province));
	}
	if (isset($location->zip) && $location->zip) {
		$c->addChild(new Meta('address-zip', 'postalCode', $location->zip));
	}
	if (isset($location->street) && $location->street) {
		$c->addChild(new Meta('address-street', 'streetAddress', $location->street . ' ' . $location->number));
	}
	if (isset($location->country) && $location->country) {
		$c->addChild(new Meta('address-country', 'addressCountry', $location->country));
	}
}
