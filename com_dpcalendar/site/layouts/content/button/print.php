<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Button;
use CCL\Content\Element\Component\Icon;

// Global variables
$root     = $displayData['root'];
$id       = $displayData['id'];
$selector = $displayData['selector'];

// Add the printing script
JFactory::getDocument()->addScriptDeclaration(
	"function printDiv(divName) {
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;
     document.body.innerHTML = printContents;
     window.print();
     document.body.innerHTML = originalContents;
}");

// Render the basic button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'root'    => $root,
		'type'    => Icon::PRINTING,
		'id'      => $id,
		'onclick' => "printDiv('" . $selector . "');return false;",
		'title'   => 'COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'
	)
);
