<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Extension\GoogleLike;
use CCL\Content\Element\Basic\Container;

$params = $displayData['params'];

if (!$params->get('enable_google', 1)) {
	return;
}

// Prepare the url
$url = str_replace('&tmpl=component', '', str_replace('?tmpl=component', '', htmlspecialchars(JUri::getInstance())));

// Set up the root container
$root = $displayData['root']->addChild(new Container('google', array('dp-share-button')));
$root->setProtectedClass('dp-share-button');

// Add the Google like button
$attributes               = array();
$attributes['data-size']  = $params->get('size_google', 'standard');
$attributes['data-count'] = $params->get('show_count_google', '1');
$attributes['language']   = GoogleLike::getCorrectLanguage(DPCalendarHelper::getFrLanguage());
$root->addChild(new GoogleLike('google-button', $url, array(), $attributes));
