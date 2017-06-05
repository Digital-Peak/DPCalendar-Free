<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Extension\FacebookComment;

$params = $displayData['params'];

if ($params->get('comment_system', 'facebook') != 'facebook') {
	// Nothing to set up
	return;
}

// Prepare the url
$url = str_replace('&tmpl=component', '', str_replace('?tmpl=component', '', htmlspecialchars(JUri::getInstance())));

// Create the FB comments element
$ce = $displayData['root']->addChild(new FacebookComment('facebook', $url, array(), array('width' => $params->get('comment_fb_width', 700))));
$ce->setNumberOfPostsLimit($params->get('comment_fb_num_posts', 10));
$ce->setColorScheme($params->get('comment_fb_colorscheme', 'light'));

$language = $ce->getCorrectLanguage(DPCalendarHelper::getFrLanguage());

// Add the custom tags to the document
$doc = JFactory::getDocument();
$doc->addScript('//connect.facebook.net/' . $language . '/all.js#xfbml=1');
if ($params->get('comment_fb_app_id')) {
	$doc->addCustomTag('<meta property="fb:app_id" content="' . $params->get('comment_fb_app_id') . '"/>');
}
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

	$doc->addCustomTag('<meta property="og:url" content="' . $fbUrl . '"/>');
	$doc->addCustomTag('<meta property="fb:admins" content="' . $params->get('comment_fb_admin_id', '') . '"/>');
	$doc->addCustomTag('<meta property="og:type" content="' . $params->get('comment_fb_og_type', 'article') . '"/>');
	$doc->addCustomTag('<meta property="og:site_name" content="' . JFactory::getConfig()->get('config.sitename') . '"/>');
	$doc->addCustomTag('<meta property="og:locale" content="' . $language . '"/>');
	$doc->addCustomTag('<meta property="og:title" content="' . $doc->getTitle() . '"/>');
}
if ($params->get('comment_fb_og_image')) {
	$doc->addCustomTag('<meta property="og:image" content="' . $params->get('comment_fb_og_image') . '"/>');
}
