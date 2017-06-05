<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

if (!JFactory::getUser()->authorise('core.manage', 'com_dpcalendar'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

JLoader::import('joomla.application.component.controller');

JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models', 'InstallerModel');
$model = JModelLegacy::getInstance('Updatesites', 'InstallerModel', array(
		'ignore_request' => true
));

$type = '';
if ($model)
{
	$model->setState('list.ordering', 'update_site_id');
	foreach ($model->getItems() as $updateSite)
	{
		if (strpos($updateSite->update_site_name, 'DPCalendar') === false)
		{
			continue;
		}
		$type .= str_replace(array(
				'DPCalendar',
				'Update Site'
		), '', $updateSite->update_site_name);
	}
}

$path = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
if (file_exists($path))
{
	$manifest = simplexml_load_file($path);
	JFactory::getApplication()->input->set('DPCALENDAR_VERSION', $manifest->version . ' ' . $type);
}
else
{
	JFactory::getApplication()->input->set('DPCALENDAR_VERSION', $type);
}

if (version_compare(PHP_VERSION, '5.5.9') < 0)
{
	JError::raiseWarning(0,
			'You have PHP version ' . PHP_VERSION . ' installed. Please upgrade your PHP version to at least 5.5.9. DPCalendar can not run on this version.');
	return;
}

$controller = JControllerLegacy::getInstance('DPCalendar');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
