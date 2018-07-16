<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

// The booking
$booking = $displayData['booking'];
if (!$booking) {
	return;
}

// The tickets
$tickets = $displayData['tickets'];
if (!$tickets) {
	return;
}

// The params
$params = $displayData['params'];
if (!$params) {
	$params = clone JComponentHelper::getParams('com_dpcalendar');
}

// Check if we can register
if (!JFactory::getUser()->guest
	|| JComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0
	|| !$params->get('booking_show_registration', 1)) {
	return '';
}

// Load the language
JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

// Some styling
JFactory::getDocument()->addStyleDeclaration(
	'#member-registration legend, #member-registration fieldset .control-group:nth-of-type(1) {display:none} #member-registration {padding: 5}');

// Prefill the data
$data = JFactory::getApplication()->setUserState(
	'com_users.registration.data',
	array(
		'name'     => $booking->name,
		'username' => preg_replace('/([^@]*).*/', '$1', $booking->email),
		'email1'   => $booking->email,
		'email2'   => $booking->email
	)
);

// Path to the users component
$com = JPATH_SITE . '/components/com_users';

// Get/configure the users controller
if (!class_exists('UsersController')) {
	require($com . '/controller.php');
}

$config['base_path'] = $com;
$cont                = new UsersController($config);

// Get the view and add the correct template path
$cont->getView('registration', 'html')->addTemplatePath($com . '/views/registration/tmpl');

// Set which view to display and add appropriate paths
JFactory::getApplication()->input->set('view', 'registration');
JForm::addFormPath($com . '/models/forms');
JForm::addFieldPath($com . '/models/fields');

// Load the language file
JFactory::getLanguage()->load('com_users', JPATH_SITE);
?>
<div class="dp-registration">
	<div class="dp-info-box">
		<?php echo $displayData['translator']->translate('COM_DPCALENDAR_LAYOUT_BOOKING_REGISTER_INFORMATION'); ?>
	</div>
	<div class="dp-registration__form"><?php $cont->display(); ?></div>
</div>
