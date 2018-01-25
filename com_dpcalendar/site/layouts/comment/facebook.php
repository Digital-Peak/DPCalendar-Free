<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Extension\FacebookComment;

// Get the params
$params = $displayData['params'];

// Check if enabled
if ($params->get('comment_system', 'facebook') != 'facebook') {
	// Nothing to set up
	return;
}

// Include the required assets
\DPCalendar\Helper\DPCalendarHelper::loadLibrary(array('dpcalendar' => true));

// Prepare the url
$url = str_replace('&tmpl=component', '', str_replace('?tmpl=component', '', htmlspecialchars(JUri::getInstance())));

// Create the FB comments element
$fb = new FacebookComment('facebook', $url);

// Set the width on the Facebook element
if ($params->get('comment_fb_width')) {
	$fb->addAttribute('width', $params->get('comment_fb_width'));
} else {
	// Make the box responsive when no width is set
	$fb->addAttribute('data-width', '100%');
}

// Set the number of posts
$fb->setNumberOfPostsLimit($params->get('comment_fb_num_posts', 10));

// Set the color scheme
$fb->setColorScheme($params->get('comment_fb_colorscheme', 'light'));

// Get the language
$language = $fb->getCorrectLanguage(DPCalendarHelper::getFrLanguage());

// Get the docs
$doc = JFactory::getDocument();

// Add the required javascript scripts
$doc->addScript('//connect.facebook.net/' . $language . '/all.js#xfbml=1');

// Check if there is an app id set
if ($params->get('comment_fb_app_id')) {
	$doc->addCustomTag('<meta property="fb:app_id" content="' . $params->get('comment_fb_app_id') . '"/>');
}

// Should the og url be set
if ($params->get('comment_fb_og_url', '1') == '1') {
	// Compile the ulr for the og parameter
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

	// Add the custom tags to the document
	$doc->addCustomTag('<meta property="og:url" content="' . $fbUrl . '"/>');
	$doc->addCustomTag('<meta property="fb:admins" content="' . $params->get('comment_fb_admin_id', '') . '"/>');
	$doc->addCustomTag('<meta property="og:type" content="' . $params->get('comment_fb_og_type', 'article') . '"/>');
	$doc->addCustomTag('<meta property="og:site_name" content="' . JFactory::getConfig()->get('config.sitename') . '"/>');
	$doc->addCustomTag('<meta property="og:locale" content="' . $language . '"/>');
	$doc->addCustomTag('<meta property="og:title" content="' . $doc->getTitle() . '"/>');
}

// Add an image if set
if ($params->get('comment_fb_og_image')) {
	$doc->addCustomTag('<meta property="og:image" content="' . $params->get('comment_fb_og_image') . '"/>');
}

// Add the Facebook element to the passed container
$displayData['root']->addChild($fb);
