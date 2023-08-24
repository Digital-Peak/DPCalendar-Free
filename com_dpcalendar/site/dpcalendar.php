<?php

use DPCalendar\Helper\Booking;
use DPCalendar\Helper\Location;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// BC classes
JLoader::registerAlias('DPCalendarHelperLocation', '\\' . Location::class);
JLoader::registerAlias('DPCalendarHelperBooking', '\\' . Booking::class);

Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
Factory::getLanguage()->load('com_dpcalendar', JPATH_SITE . '/components/com_dpcalendar');

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

$controller = BaseController::getInstance('DPCalendar');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
