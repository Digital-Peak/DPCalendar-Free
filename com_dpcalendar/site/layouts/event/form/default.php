<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use CCL\Content\Element\Basic\Form;
use CCL\Content\Element\Basic\Element;
use CCL\Content\Element\Basic\Form\Input;
use CCL\Content\Element\Basic\Heading;
use CCL\Content\Element\Component\Alert;

/**
 * Layout variables
 * -----------------
 * @var object $event
 * @var object $form
 * @var object $user
 * @var object $input
 * @var object $params
 * @var string $returnPage
 **/
extract($displayData);

// Load the needed JS libraries
DPCalendarHelper::loadLibrary(array('jquery' => true, 'chosen' => true, 'dpcalendar' => true, 'url' => true));
JHtml::_('script', 'com_dpcalendar/moment/moment.min.js', ['relative' => true], ['defer' => true]);

JHtml::_('stylesheet', 'com_dpcalendar/dpcalendar/layouts/event/form/default.min.css', ['relative' => true]);
JHtml::_('script', 'com_dpcalendar/dpcalendar/layouts/event/form/default.min.js', ['relative' => true], ['defer' => true]);

if ($params->get('save_history')) {
	// Initialise the modal behavior
	JHtml::_('behavior.modal', 'a.modal_jform_contenthistory');
}

// Some JS language strings
JText::script('COM_DPCALENDAR_VIEW_EVENT_SEND_TICKET_HOLDERS_NOFICATION');

if (DPCalendarHelper::isFree()) {
	JText::script('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS');
}

// Load the maps scripts when required
if ($params->get('event_form_change_location', 1)) {
	DPCalendarHelper::loadLibrary(array('maps' => true));
}

// The form element
$tmpl = $input->getCmd('tmpl') ? '&tmpl=' . $input->getCmd('tmpl') : '';
$root = new Form(
	'dp-event-form',
	JRoute::_('index.php?option=com_dpcalendar&layout=edit&e_id=' . $event->id, false),
	'adminForm',
	'POST',
	array('form-validate')
);

if ($event->original_id != '0') {
	if ($event->original_id == '-1') {
		$root->addChild(new Heading('original-information', 4))->setContent(JText::_('COM_DPCALENDAR_VIEW_EVENT_ORIGINAL_WARNING'));
	} elseif (!empty($event->original_id)) {
		$root->addChild(new Heading('original-information', 4))->setContent(
			JText::sprintf(
				'COM_DPCALENDAR_VIEW_EVENT_GOTO_ORIGINAL',
				DPCalendarHelperRoute::getFormRoute($event->original_id, base64_decode($returnPage))
			)
		);
	}
}
if ($params->get('event_form_check_overlaping', 0)) {
	$box = $root->addChild(new Alert('message-box', Alert::INFO));
	$box->addAttribute('data-overlapping', $params->get('event_form_check_overlaping', 0) == '2');
}

if ($app->isSite()) {
	$displayData['root'] = $root;

	// Load the header template
	DPCalendarHelper::renderLayout('event.form.header', $displayData);
}

// Determine which fieldsets should be hidden
$externalEvent = !is_numeric($event->catid) && !empty($event->id);
$hideFieldsets = array();
if (!$user->authorise('core.admin', 'com_dpcalendar')) {
	if (!$params->get('event_form_change_location', 1)) {
		$hideFieldsets[] = 'location';
	}
	if ($externalEvent || !$params->get('event_form_change_options', 1)) {
		$hideFieldsets[] = 'jbasic';
	}
	if ($externalEvent || !$params->get('event_form_change_book', 1) || ($event->catid && !is_numeric($event->catid))) {
		$hideFieldsets[] = 'booking';
	}
	if ($externalEvent || !$params->get('event_form_change_publishing', 1)) {
		$hideFieldsets[] = 'publishing';
	}
	if ($externalEvent || !$params->get('event_form_change_metadata', 1)) {
		$hideFieldsets[] = 'jmetadata';
	}
}

// Determine which fields should be hidden
$hideFields = array();
if (!$params->get('save_history', 0)) {
	// Save is not activated
	$hideFields[] = 'version_note';
}

if ($params->get('event_form_change_tags', '1') != '1') {
	// Tags can't be changed
	$hideFields[] = 'tags';
}

if ((!$event->id && !$user->authorise('core.edit.state', 'com_dpcalendar'))
	|| ($event->id && !$user->authorise('core.edit.state', 'com_dpcalendar.category.' . $event->catid))
) {
	// Changing state is not allowed
	$hideFields[] = 'state';
}

if ($event->original_id > '0') {
	// Hide the scheduling fields
	$hideFields[] = 'rrule';
	$hideFields[] = 'scheduling';
	$hideFields[] = 'scheduling_end_date';
	$hideFields[] = 'scheduling_interval';
	$hideFields[] = 'scheduling_repeat_count';
	$hideFields[] = 'scheduling_daily_weekdays';
	$hideFields[] = 'scheduling_weekly_days';
	$hideFields[] = 'scheduling_monthly_options';
	$hideFields[] = 'scheduling_monthly_week';
	$hideFields[] = 'scheduling_monthly_week_days';
	$hideFields[] = 'scheduling_monthly_days';
}

// Load the form from the layout
DPCalendarHelper::renderLayout(
	'content.form',
	array(
		'root'            => $root,
		'jform'           => $form,
		'fieldsToHide'    => $hideFields,
		'fieldsetsToHide' => $hideFieldsets,
		'return'          => $returnPage,
		'flat'            => $params->get('event_form_flat_mode')
	)
);

// Load the location layout
DPCalendarHelper::renderLayout(
	'event.form.location',
	array(
		'root'   => $root,
		'jform'  => $form,
		'params' => $params,
		'app'    => $app,
		'return' => $returnPage
	)
);

// Add some hidden inputs for redirects
$root->addChild(new Input('tmpl', 'hidden', 'tmpl', $input->get('tmpl')));
$root->addChild(new Input('urlhash', 'hidden', 'urlhash', $input->getString('urlhash')));

// Render the element tree
echo DPCalendarHelper::renderElement($root, $params);
