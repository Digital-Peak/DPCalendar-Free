<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Extension\TwitterShare;
use CCL\Content\Element\Basic\Container;

$params = $displayData['params'];

if (!$params->get('enable_twitter', 1)) {
	return;
}
// Prepare the url
$url = str_replace('&tmpl=component', '', str_replace('?tmpl=component', '', htmlspecialchars(JUri::getInstance())));

// Set up the root container
$root = $displayData['root']->addChild(new Container('twitter', array('dp-share-button')));
$root->setProtectedClass('dp-share-button');

// Add the twitter button
JFactory::getDocument()->addScript("//platform.twitter.com/widgets.js");

$attributes                 = array();
$attributes['data-via']     = $params->get('data_via_twitter');
$attributes['data-lang']    = TwitterShare::getCorrectLanguage(DPCalendarHelper::getFrLanguage());
$attributes['data-related'] = $params->get('data_related_twitter');
$attributes['data-text']    = htmlspecialchars(JFactory::getDocument()->getTitle(), ENT_QUOTES, "UTF-8");
$attributes['data-count']   = $params->get('show_count_twitter', 'horizontal');
$root->addChild(new TwitterShare('twitter-button', $url, array(), $attributes));

