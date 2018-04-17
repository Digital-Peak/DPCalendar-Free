<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarController extends JControllerLegacy
{

	public function display($cachable = false, $urlparams = false)
	{
		$view = $this->input->get('view');

		if (!$view) {
			if ($this->input->get->get('filter')) {
				$view = 'events';
			} else {
				$view = 'cpanel';
			}
		}

		$this->input->set('view', $view);
		$layout = $this->input->getCmd('layout', 'default');
		$id     = $this->input->getInt('id');

		if ($view != 'event' && $view != 'location' && $view != 'booking') {
			DPCalendarHelper::addSubmenu($this->input->getCmd('view', 'cpanel'));
		}

		// Check for edit form.
		if ($view == 'event' && $layout == 'edit' && !$this->checkEditId('com_dpcalendar.edit.event', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=events', false));

			return false;
		}

		parent::display();

		return $this;
	}

	public function getModel($name = '', $prefix = 'DPCalendarModel', $config = array())
	{
		if ($name == 'event') {
			$name = 'AdminEvent';
		}

		if ($name == 'events') {
			$name = 'AdminEvents';
		}

		return parent::getModel($name, $prefix, $config);
	}
}
