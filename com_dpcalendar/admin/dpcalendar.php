<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

if (!Factory::getUser()->authorise('core.manage', 'com_dpcalendar')) {
	return Factory::getApplication()->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
}

$input = Factory::getApplication()->input;

JLoader::import('joomla.application.component.controller');

// Load the model
BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models', 'InstallerModel');
$model = BaseDatabaseModel::getInstance('Updatesites', 'InstallerModel', ['ignore_request' => true]);

// Determine the type
$type = '';
if ($model) {
	$model->setState('filter.search', '');
	$model->setState('list.ordering', 'update_site_id');
	foreach ($model->getItems() as $updateSite) {
		if (strpos($updateSite->update_site_name, 'DPCalendar') === false) {
			continue;
		}
		$type .= str_replace(['DPCalendar', 'Update Site'], '', $updateSite->update_site_name);
	}
}

// Determine the version
$path = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
if (file_exists($path)) {
	$manifest = simplexml_load_file($path);
	$input->set('DPCALENDAR_VERSION', $manifest->version . ' ' . $type);
} else {
	$input->set('DPCALENDAR_VERSION', $type);
}

// Map the front location form controller
if ($input->get('task') == 'locationform.save') {
	$input->set('task', 'location.save');
}

PluginHelper::importPlugin('dpcalendar');
Factory::getApplication()->triggerEvent('onDPCalendarBeforeExecute', [$input]);

// Execute the task
$controller = BaseController::getInstance('DPCalendar');
$controller->execute($input->get('task'));
$controller->redirect();
