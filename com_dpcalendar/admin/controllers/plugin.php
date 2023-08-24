<?php

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerPlugin extends BaseController
{
	public function action()
	{
		DPCalendarHelper::doPluginAction($this->input->getWord('dpplugin', $this->input->getWord('plugin')), $this->input->getWord('action'));

		Factory::getApplication()->close();
	}
}
