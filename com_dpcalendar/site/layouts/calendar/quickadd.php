<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Container;
use CCL\Content\Element\Basic\Form;
use CCL\Content\Element\Basic\Form\Input;
use CCL\Content\Element\Component\Icon;
use Joomla\Registry\Registry;

$params = $displayData['params'];
if (!$params) {
	$params = new Registry();
}

$root = $displayData['root']->addChild(new Container('quickadd'));

$uniqueId = $displayData['id'];

JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
JFactory::getLanguage()->load('com_dpcalendar', JPATH_ROOT . '/components/com_dpcalendar');

$dateVar = JFactory::getApplication()->input->getVar('date', null);
$local   = false;
if (strpos($dateVar, '00-00') != false) {
	$dateVar = substr($dateVar, 0, 10) . DPCalendarHelper::getDate()->format(' H:i');
	$local   = true;
}
$date = DPCalendarHelper::getDate($dateVar);
$date->setTime($date->format('H'), 0);

$format = $params->get('event_form_date_format', 'm.d.Y') . ' ' . $params->get('event_form_time_format', 'g:i a');

JLoader::import('joomla.form.form');

JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
JForm::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/fields');

$form = JForm::getInstance('com_dpcalendar.event', 'event', array('control' => 'jform'));
$form->setValue('start_date', null, $date->format($format, $local));
$date->modify('+1 hour');
$form->setValue('end_date', null, $date->format($format, $local));
$form->setFieldAttribute('title', 'class', 'input-medium');

$form->setFieldAttribute('start_date', 'format', $params->get('event_form_date_format', 'm.d.Y'));
$form->setFieldAttribute('start_date', 'formatTime', $params->get('event_form_time_format', 'g:i a'));
$form->setFieldAttribute('start_date', 'formated', true);
$form->setFieldAttribute('end_date', 'format', $params->get('event_form_date_format', 'm.d.Y'));
$form->setFieldAttribute('end_date', 'formatTime', $params->get('event_form_time_format', 'g:i a'));
$form->setFieldAttribute('end_date', 'formated', true);

$formElement = $root->addChild(
	new Form(
		'form',
		JRoute::_(DPCalendarHelperRoute::getFormRoute(0, JUri::getInstance()->toString())),
		'adminForm',
		'POST',
		array('form-validate')
	)
);
$formElement->addClass('timepair', true);

// Render the form layout
DPCalendarHelper::renderLayout('content.form', array(
	'root'         => $formElement,
	'jform'        => $form,
	'fieldsToShow' => array('start_date', 'end_date', 'title', 'catid'),
	'flat'         => true
));

// Add some hidden input fields
$formElement->addChild(new Input('urlhash', 'hidden', 'urlhash'));
$formElement->addChild(new Input('capacity', 'hidden', 'jform[capacity]', '0'));
$formElement->addChild(new Input('all_day', 'hidden', 'form[all_day]', '0'));
$formElement->addChild(new Input('layout', 'hidden', 'layout', 'edit'));

// Create the submit button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::OK,
		'root'    => $root,
		'text'    => 'COM_DPCALENDAR_VIEW_FORM_BUTTON_SUBMIT_EVENT',
		'onclick' => "jQuery('#" . $formElement->getId() . " [name=\"task\"').val('event.save'); jQuery('#" . $formElement->getId() . "').submit()"
	)
);

// Create the submit button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::EDIT,
		'root'    => $root,
		'text'    => 'COM_DPCALENDAR_VIEW_FORM_BUTTON_EDIT_EVENT',
		'onclick' => "jQuery('#" . $formElement->getId() . "').submit()"
	)
);

// Create the cancel button
DPCalendarHelper::renderLayout(
	'content.button',
	array(
		'type'    => Icon::CANCEL,
		'root'    => $root,
		'text'    => 'JCANCEL',
		'onclick' => "jQuery('#" . $root->getId() . "').toggle(); jQuery('#" . $formElement->getId() . " [name=\"title\"').val('')"
	)
);

// Some JS code to handle closing and hashchanges
$calCode = "// <![CDATA[
jQuery(document).ready(function(){
    jQuery('body').mouseup(function(e) {
        var form = jQuery('#" . $root->getId() . "');

        if (form.has(e.target).length === 0 && !jQuery('#ui-datepicker-div').is(':visible') && !jQuery(e.target).parent().hasClass('ui-timepicker-list')) {
            form.hide();
        }
    });
    
    document.onkeydown = function(evt) {
	    evt = evt || window.event;
	    var isEscape = false;
	    if (\"key\" in evt) {
	        isEscape = (evt.key == \"Escape\" || evt.key == \"Esc\");
	    } else {
	        isEscape = (evt.keyCode == 27);
	    }
	    if (isEscape) {
	        jQuery('#" . $root->getId() . "').hide();
	    }
	};
    
    jQuery(window).on('hashchange', function() {
      jQuery('#" . $formElement->getId() . " input[name=urlhash]').val(window.location.hash);
    });
    jQuery('#" . $formElement->getId() . " input[name=urlhash]').val(window.location.hash);
});
// ]]>\n";
JFactory::getDocument()->addScriptDeclaration($calCode);

JFactory::getDocument()->addStyleDeclaration('#' . $root->getId() . ' {
	display: none;
	position: absolute;
	background-color: white;
	z-index: 1002;
	border: 1px solid #ccc;
	max-width: 320px;
	padding: 5px;
}

#' . $root->getId() . ' .control-group, #' . $root->getId() . ' .control-group .controls  {
	margin: 2px;
	padding: 0;
}

#' . $root->getId() . ' .control-group .control-label {
	width: 80px;
}

#' . $root->getId() . ' .control-group label {
	height: 14px;
	font-size: 10px;
	line-height: 14px;
	margin-top: 5px;
}

#' . $root->getId() . ' .ui-timepicker-list li {
	height: 14px;
	font-size: 10px;
	line-height: 14px;
	margin-top: 5px;
}

#' . $root->getId() . ' .control-group input {
	height: 14px;
	font-size: 10px;
	line-height: 14px;
}

#' . $root->getId() . ' .control-group select {
	height: 28px;
	font-size: 10px;
	line-height: 14px;
	padding: 0;
}');
