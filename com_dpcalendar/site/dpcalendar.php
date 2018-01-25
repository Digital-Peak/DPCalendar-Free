<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (version_compare(PHP_VERSION, '5.5.9') < 0)
{
	JError::raiseWarning(0,
			'You have PHP version ' . PHP_VERSION . ' installed. This version is end of life and contains some security wholes!!
					 		Please upgrade your PHP version to at least 5.3.x. DPCalendar can not run on this version.');
	return;
}

JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
JFactory::getLanguage()->load('com_dpcalendar', JPATH_SITE . '/components/com_dpcalendar');

JLoader::import('joomla.application.component.controller');
JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

$controller = JControllerLegacy::getInstance('DPCalendar');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
