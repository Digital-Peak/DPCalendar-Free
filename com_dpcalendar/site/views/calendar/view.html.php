<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('libraries.dpcalendar.fullcalendar', JPATH_COMPONENT);

class DPCalendarViewCalendar extends \DPCalendar\View\BaseView
{

	public function init()
	{
		$items = $this->get('AllItems');

		if ($items === false)
		{
			return $this->setError(JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
		}

		$this->items = $items;

		$selectedCalendars = array();
		foreach ($items as $calendar)
		{
			$selectedCalendars[] = $calendar->id;
		}
		$this->selectedCalendars = $selectedCalendars;

		$doNotListCalendars = array();
		foreach ($this->params->get('idsdnl', array()) as $id)
		{
			$parent = DPCalendarHelper::getCalendar($id);
			if ($parent == null)
			{
				continue;
			}

			if ($parent->id != 'root')
			{
				$doNotListCalendars[$parent->id] = $parent;
			}

			if (!$parent->external)
			{
				foreach ($parent->getChildren(true) as $child)
				{
					$doNotListCalendars[$child->id] = DPCalendarHelper::getCalendar($child->id);
				}
			}
		}
		// if none are selected, use selected calendars
		$this->doNotListCalendars = empty($doNotListCalendars) ? $this->items : $doNotListCalendars;

		return parent::init();
	}
}
