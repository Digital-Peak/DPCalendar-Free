<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JLoader::import('components.com_dpcalendar.views.serializer', JPATH_API);

// Execute the task
$controller = BaseController::getInstance('DPCalendar');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
