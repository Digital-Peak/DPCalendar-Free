<?php

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerCountries extends AdminController
{
	protected $text_prefix = 'COM_DPCALENDAR_COUNTRY';

	public function getModel($name = 'Country', $prefix = 'DPCalendarModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
