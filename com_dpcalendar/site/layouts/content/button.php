<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Button;
use CCL\Content\Element\Component\Icon;

// Global required variables
$root    = $displayData['root'];
$onclick = $displayData['onclick'];

// Global optional variables
$type  = isset($displayData['type']) ? $displayData['type'] : '';
$id    = isset($displayData['id']) ? $displayData['id'] : $type ?: 'not-set';
$text  = isset($displayData['text']) ? $displayData['text'] : '';
$title = isset($displayData['title']) ? $displayData['title'] : $text;

$icon = null;
if ($type) {
	// The icon of the button
	$icon = new Icon('icon', $type);
}

// Create the button
$button = new Button(
	$id,
	html_entity_decode(JText::_($text)),
	$icon,
	array($id),
	array(
		'title'   => html_entity_decode(JText::_($title)),
		'onclick' => $onclick
	)
);
$button->addClass('hasTooltip', true);
$button->addClass('dp-button', true);

// Add it to the parent
$root->addChild($button);
