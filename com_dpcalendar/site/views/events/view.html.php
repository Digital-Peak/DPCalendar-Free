<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.view');

class DPCalendarViewEvents extends JViewLegacy
{

	public function display($tpl = null)
	{
		$input = JFactory::getApplication()->input;
		$input->set('list.limit', 1000);

		$this->params = $this->get('State')->params;

		$this->items = $this->get('Items');

		$this->compactMode = $input->getInt('compact', 0);
		if ($this->compactMode == 1) {
			$this->setLayout('compact');
		}

		parent::display($tpl);
	}
}
