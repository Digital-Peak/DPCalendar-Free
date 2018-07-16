<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

// Load the required modal JS libraries
if ($params->get('show_as_popup')) {
	$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
	$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MODAL);
	$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_URL);
	$document->loadScriptFile('default.js', 'mod_dpcalendar_upcoming');
}

// Load the stylesheet
$document->loadStyleFile(str_replace('_:', '', $params->get('layout', 'default')) . '.css', 'mod_dpcalendar_upcoming');
