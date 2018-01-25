<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Image;

// Add some CSS styles
JFactory::getDocument()->addStyleDeclaration(".dpcalendar-loader {
	text-align: center;
	width: 100%;
	display: block;
}");

// Add the global function to the DPCalendar namespace
JFactory::getDocument()->addScriptDeclaration("DPCalendar = window.DPCalendar || {};
DPCalendar.loader = function(task, parent) {
	if (task == 'show') {
		parent.querySelector('.dpcalendar-loader').style.display = 'block';
	}
	if (task == 'hide') {
		parent.querySelector('.dpcalendar-loader').style.display = 'none';
	}
};
");

// Create the element
$l = $displayData['root']->addChild(new Container('loader'));
$l->addClass('dpcalendar-loader', true);
$l->addChild(new Image('image', 'media/com_dpcalendar/images/site/ajax-loader.gif', 'loader'));
