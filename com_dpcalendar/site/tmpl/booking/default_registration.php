<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;

// Check if we can register
if (!$this->user->guest
	|| ComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0
	|| $this->params->get('booking_registration', 1) != 1) {
	return '';
}

// Prefill the data
$data = $this->app->setUserState(
	'com_users.registration.data',
	[
		'name'     => $this->booking->name,
		'username' => preg_replace('/([^@]*).*/', '$1', (string) $this->booking->email),
		'email1'   => $this->booking->email,
		'email2'   => $this->booking->email
	]
);

// Set which view to display and add appropriate paths
$this->app->getInput()->set('view', 'registration');

// Path to the users component
$com = JPATH_SITE . '/components/com_users';

$controller = $this->app->bootComponent('com_users')->getMVCFactory($this->app)->createController(
	'Display',
	'Site',
	[],
	$this->app,
	$this->app->getInput()
);

// Get the view and add the correct template path
$view = $controller->getView('registration', 'html');
$view->addTemplatePath($com . '/tmpl/registration');
$view->addTemplatePath($com . '/views/registration/tmpl');

Form::addFormPath($com . '/forms');
Form::addFormPath($com . '/models/forms');
Form::addFieldPath($com . '/models/fields');

// Load the language file
$this->app->getLanguage()->load('com_users', JPATH_SITE);
?>
<div class="com-dpcalendar-booking__registration dp-registration">
	<div class="dp-registration__info dp-info-box">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_REGISTER_INFORMATION'); ?>
	</div>
	<div class="dp-registration__form"><?php $controller->display(); ?></div>
</div>
