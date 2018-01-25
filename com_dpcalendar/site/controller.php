<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controller');

class DPCalendarController extends JControllerLegacy
{

	public function display ($cachable = false, $urlparams = false)
	{
		$cachable = true;
		$user = JFactory::getUser();

		$id = JFactory::getApplication()->input->getVar('e_id');
		$vName = JFactory::getApplication()->input->getCmd('view', 'calendar');
		JFactory::getApplication()->input->set('view', $vName);

		if ($user->get('id') || ($_SERVER['REQUEST_METHOD'] == 'POST' && $vName = 'list') || $vName = 'events')
		{
			$cachable = false;
		}

		$safeurlparams = array(
				'id' => 'STRING',
				'limit' => 'UINT',
				'limitstart' => 'UINT',
				'filter_order' => 'CMD',
				'filter_order_Dir' => 'CMD',
				'lang' => 'CMD'
		);

		// Check for edit form.
		if ($vName == 'form' && ! $this->checkEditId('com_dpcalendar.edit.event', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
		}

		return parent::display($cachable, $safeurlparams);
	}
}
