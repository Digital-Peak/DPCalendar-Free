<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Form;

DPCalendarHelper::loadLibrary(array('chosen' => true, 'dpcalendar' => true));

JFactory::getDocument()->addStyleDeclaration('#dp-invite-actions {margin-bottom: 10px;}');

// The form element
$tmpl = JFactory::getApplication()->input->getCmd('tmpl');
if($tmpl)
{
	$tmpl = '&tmpl=' . $tmpl;
}
$this->root = new Form(
	'dp-invite',
	JRoute::_('index.php?option=com_dpcalendar' . $tmpl),
	'adminForm',
	'POST',
	array('form-validate')
);

// Load the header template
$this->loadTemplate('header');

// Load the form from the layout
DPCalendarHelper::renderLayout('content.form', array('root' => $this->root, 'jform' => $this->form, 'flat' => true, 'return' => JFactory::getApplication()->input->getBase64('return')));

// Render the tree
echo DPCalendarHelper::renderElement($this->root, $this->params);
