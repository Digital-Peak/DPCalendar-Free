<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Form;
use CCL\Content\Element\Basic\TextBlock;

/**
 * Layout variables
 * -----------------
 * @var object $ticket
 * @var object $form
 * @var object $user
 * @var object $input
 * @var object $params
 * @var string $returnPage
 **/
extract($displayData);

// Load the needed JS libraries
DPCalendarHelper::loadLibrary(array('jquery' => true, 'dpcalendar' => true));

// Add some CSS rules
JFactory::getDocument()->addStyleDeclaration('
	.ui-datepicker, .ui-timepicker-list { font:90% Arial,sans-serif; }
	#jform_remind_time, #jform_remind_type {max-width:100px}
	#dp-ticketform-actions {margin-bottom:10px}'
);

// The tmpl parameter
$tmpl = $input->getCmd('tmpl') ? '&tmpl=' . $input->getCmd('tmpl') : '';

// The form element
$root = new Form(
	'dp-ticketform',
	JRoute::_('index.php?option=com_dpcalendar&layout=edit&t_id=' . $ticket->id . $tmpl, false),
	'adminForm',
	'POST',
	array('form-validate'),
	array('ccl-prefix' => $root->getPrefix())
);

if ($app->isSite()) {
	$displayData['root'] = $root;

	// Load the header template
	DPCalendarHelper::renderLayout('ticket.form.toolbar', $displayData);
}

// Load the form from the layout
$hideFields = array('id', 'user_id', 'latitude', 'longitude', 'type', 'remind_time', 'remind_type');

if ($ticket->price == '0.00') {
	$hideFields[] = 'price';
}

DPCalendarHelper::renderLayout(
	'content.form',
	array(
		'root'         => $root,
		'jform'        => $form,
		'fieldsToHide' => $hideFields,
		'return'       => $returnPage,
		'flat'         => true
	)
);

// Display the reminder special
$b = $root->addChild(new TextBlock('reminder'));
$b->setContent(
	JLayoutHelper::render(
		'joomla.form.renderfield',
		array(
			'label' => $form->getLabel('remind_time'),
			'input' => $form->getInput('remind_time') . $form->getInput('remind_type')
		)
	)
);

// Render the element tree
echo DPCalendarHelper::renderElement($root, $params);
