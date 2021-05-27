<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerTickets extends JControllerAdmin
{
	protected $text_prefix = 'COM_DPCALENDAR_TICKET';

	public function getModel($name = 'Ticket', $prefix = 'DPCalendarModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
