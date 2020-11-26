<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerPlugin extends JControllerLegacy
{

	public function action()
	{
		DPCalendarHelper::doPluginAction($this->input->getWord('dpplugin', $this->input->getWord('plugin')), $this->input->getWord('action'));

		JFactory::getApplication()->close();
	}
}
