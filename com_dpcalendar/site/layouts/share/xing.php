<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Extension\XingShare;
use CCL\Content\Element\Basic\Container;

$params = $displayData['params'];

if (!$params->get('enable_xing', 0)) {
	return;
}

$root = $displayData['root']->addChild(new Container('xing', array('dp-share-button')));
$root->setProtectedClass('dp-share-button');

// Add the Xing share button
$attributes                 = array();
$attributes['data-counter'] = 'data-counter="right"';
if ($params->get('show_count_xing', '') == 'vertical') {
	$attributes['data-counter'] = 'top';
}
if ($params->get('show_count_xing', '') == 'none') {
	$attributes['data-counter'] = '';
}
$root->addChild(new XingShare('xing-button', array(), $attributes));
