<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.view');
JLoader::import('libraries.fullcalendar.fullcalendar', JPATH_COMPONENT);

class DPCalendarViewEvents extends JViewLegacy
{

	public function display ($tpl = null)
	{
		JFactory::getApplication()->input->set('list.limit', 1000);

		$this->items = $this->get('Items');

		$tmp = clone $this->get('State')->params;
		$tmp->merge(JFactory::getApplication()->getParams());
		$this->params = $tmp;

		$this->compactMode = JFactory::getApplication()->input->getVar('compact', 0);
		if ($this->compactMode == 1)
		{
			$this->setLayout('compact');
		}

		parent::display($tpl);
	}
}
