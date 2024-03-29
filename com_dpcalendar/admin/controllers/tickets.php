<?php

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerTickets extends AdminController
{
	protected $text_prefix = 'COM_DPCALENDAR_TICKET';

	public function getModel($name = 'Ticket', $prefix = 'DPCalendarModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
