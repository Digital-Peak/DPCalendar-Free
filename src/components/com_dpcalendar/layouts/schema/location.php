<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
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
	$c->addChild(new Meta('name', 'name', htmlspecialchars(\DPCalendar\Helper\Location::format($location), ENT_QUOTES)));

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
		$c->addChild(new Meta('address-city', 'addressLocality', htmlspecialchars($location->city, ENT_QUOTES)));
	}
	if (isset($location->province) && $location->province) {
		$c->addChild(new Meta('address-province', 'addressRegion', htmlspecialchars($location->province, ENT_QUOTES)));
	}
	if (isset($location->zip) && $location->zip) {
		$c->addChild(new Meta('address-zip', 'postalCode', htmlspecialchars($location->zip, ENT_QUOTES)));
	}
	if (isset($location->street) && $location->street) {
		$c->addChild(new Meta('address-street', 'streetAddress', htmlspecialchars($location->street . ' ' . $location->number, ENT_QUOTES)));
	}
	if (isset($location->country) && $location->country) {
		$c->addChild(new Meta('address-country', 'addressCountry', htmlspecialchars($location->country, ENT_QUOTES)));
	}
}
