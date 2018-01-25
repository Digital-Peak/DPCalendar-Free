<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
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
