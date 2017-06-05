<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Extension\LinkedInShare;
use CCL\Content\Element\Basic\Container;

$params = $displayData['params'];

if (!$params->get('enable_linkedin', 1)) {
	return;
}

// Prepare the url
$url = str_replace('&tmpl=component', '', str_replace('?tmpl=component', '', htmlspecialchars(JUri::getInstance())));

// Set up the root container
$root = $displayData['root']->addChild(new Container('linkedin', array('dp-share-button')));
$root->setProtectedClass('dp-share-button');

// Add the LinkedIn share button
$attributes             = array();
$attributes['counter']  = $params->get('show_count_linkedin', '1');
$attributes['language'] = LinkedInShare::getCorrectLanguage(DPCalendarHelper::getFrLanguage());
$root->addChild(new LinkedInShare('linkedin-button', $url, array(), $attributes));
