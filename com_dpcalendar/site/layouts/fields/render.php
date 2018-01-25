<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;

// Check if we have all the data
if (!key_exists('item', $displayData) || !key_exists('context', $displayData)) {
	return;
}

// Setting up for display
$item = $displayData['item'];

if (!$item) {
	return;
}

$context = $displayData['context'];

if (!$context) {
	return;
}

JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

$parts     = explode('.', $context);
$component = $parts[0];
$fields    = array();

if (key_exists('fields', $displayData)) {
	$fields = $displayData['fields'];
} else {
	if (!empty($item->jcfields)) {
		$fields = $item->jcfields;
	}

	if (!$fields) {
		$fields = FieldsHelper::getFields($context, $item, true);
	}
}
if (!$fields) {
	return;
}

$c = new Container('fields', array(), array('ccl-prefix' => 'fields'));

// Loop through the fields and print them
foreach ($fields as $field) {
	// If the value is empty do nothing
	if (!isset($field->value) || $field->value == '') {
		continue;
	}

	DPCalendarHelper::renderLayout(
		'content.dl',
		array(
			'root'    => $c,
			'id'      => 'field-' . $field->id,
			'label'   => $field->params->get('showlabel') ? $field->label : '',
			'content' => $field->value
		)
	);
}

foreach ($c->getChildren() as $child) {
	echo DPCalendarHelper::renderElement($child);
}
