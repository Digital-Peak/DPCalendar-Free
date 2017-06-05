<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controller');

class DPCalendarControllerIcal extends JControllerLegacy
{

	public function download ()
	{
		// Remove the script time limit.
		@set_time_limit(0);

		$calendars = array(
				$this->input->getCmd('id')
		);
		$calendar = DPCalendarHelper::getCalendar($this->input->getCmd('id'));
		if (method_exists($calendar, 'getChildren'))
		{
			$childrens = $calendar->getChildren();
			if ($childrens)
			{
				foreach ($childrens as $c)
				{
					$calendars[] = $c->id;
				}
			}
		}
		\DPCalendar\Helper\Ical::createIcalFromCalendar($calendars, true);
		return true;
	}
}
