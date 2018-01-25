<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Extension\FacebookLike;
use CCL\Content\Element\Extension\FacebookComment;
use CCL\Content\Element\Basic\Container;

$params = $displayData['params'];

if (!$params->get('enable_like', 1)) {
	return;
}

// Prepare the url
$url = str_replace('&tmpl=component', '', str_replace('?tmpl=component', '', htmlspecialchars(JUri::getInstance())));

// Set up the root container
$root = $displayData['root']->addChild(new Container('facebook', array('dp-share-button')));
$root->setProtectedClass('dp-share-button');

// Create the FB like element
$language = FacebookComment::getCorrectLanguage(DPCalendarHelper::getFrLanguage());
JFactory::getDocument()->addScript('//connect.facebook.net/' . $language . '/all.js#xfbml=1');

$attributes                     = array();
$attributes['data-layout']      = $params->get('layout_style', 'button_count');
$attributes['data-action']      = $params->get('verb_to_display', 1) == 1 ? 'like' : 'recommend';
$attributes['data-show-faces']  = $params->get('show_faces', 1) == 1 ? 'true' : 'false';
$attributes['data-colorscheme'] = $params->get('color_scheme', 'light');
$attributes['data-share']       = $params->get('send', '0') == 1 ? 'true' : 'false';
$attributes['data-width']       = $params->get('width_like');
$root->addChild(new FacebookLike('facebook-button', $url, array(), $attributes));

// Add the custom tags to the document
if ($params->get('comment_fb_og_url', '1') == '1') {
	$fbUrl = $url;
	$fbUrl = str_replace("/?option", "/index.php?option", $fbUrl);
	$pos   = strpos($fbUrl, "&fb_comment_id");
	if ($pos) {
		$fbUrl = substr($fbUrl, 0, $pos);
	}
	$pos = strpos($fbUrl, "?fb_comment_id");
	if ($pos) {
		$fbUrl = substr($fbUrl, 0, $pos);
	}

	JFactory::getDocument()->addCustomTag('<meta property="og:url" content="' . $fbUrl . '"/>');
}
