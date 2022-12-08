<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

// BC classes
JLoader::registerAlias('DPCalendarHelperLocation', '\\DPCalendar\\Helper\\Location', '6.0');
JLoader::registerAlias('DPCalendarHelperBooking', '\\DPCalendar\\Helper\\Booking', '6.0');

JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
JFactory::getLanguage()->load('com_dpcalendar', JPATH_SITE . '/components/com_dpcalendar');

JLoader::import('joomla.application.component.controller');
JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

$controller = JControllerLegacy::getInstance('DPCalendar');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
