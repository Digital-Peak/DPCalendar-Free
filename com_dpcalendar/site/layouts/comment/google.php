<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;

$params = $displayData['params'];

if ($params->get('comment_system') != 'googleplus') {
	// Nothing to set up
	return;
}

// Prepare the url
$url = str_replace('&tmpl=component', '', str_replace('?tmpl=component', '', htmlspecialchars(JUri::getInstance())));

$doc = JFactory::getDocument();
$doc->addScript("//apis.google.com/js/plusone.js");
$doc->addScriptDeclaration(
	"jQuery(document).ready(function() {
	gapi.comments.render('" . $root->getPrefix() . "gplus', {
	    href: '" . $url . "',
	    width: '" . $params->get('comment_gp_width', 700) . "',
	    first_party_property: 'BLOGGER',
	    view_type: 'FILTERED_POSTMOD'
	});
});"
);

// Create the Google plus container
$displayData['root']->addChild(new Container('gplus'));
