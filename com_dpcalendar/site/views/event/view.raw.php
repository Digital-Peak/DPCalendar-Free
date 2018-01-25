<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.view');

class DPCalendarViewEvent extends JViewLegacy
{

	public function display($tpl = null)
	{
		$event = $this->get('Item');
		if ($event->original_id > 0)
		{
			// Download the series
			$event = $this->getModel()->getItem($event->original_id);
		}
		\DPCalendar\Helper\Ical::createIcalFromEvents(array(
				$event
		), true);
	}
}
