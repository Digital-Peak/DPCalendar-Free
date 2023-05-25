<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// BC classes
JLoader::registerAlias('DPCalendarHelperLocation', '\\DPCalendar\\Helper\\Location', '6.0');
JLoader::registerAlias('DPCalendarHelperBooking', '\\DPCalendar\\Helper\\Booking', '6.0');

Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
Factory::getLanguage()->load('com_dpcalendar', JPATH_SITE . '/components/com_dpcalendar');

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

$controller = BaseController::getInstance('DPCalendar');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
