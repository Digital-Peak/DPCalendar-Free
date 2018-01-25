<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerExtcalendars extends JControllerAdmin
{

	protected $text_prefix = 'COM_DPCALENDAR_EXTCALENDAR';

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->input = JFactory::getApplication()->input;
	}

	public function getModel($name = 'Extcalendar', $prefix = 'DPCalendarModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function import()
	{
		$this->setRedirect(
			'index.php?option=com_dpcalendar&view=extcalendars&layout=import&dpplugin=' . $this->input->getCmd('dpplugin') . '&tmpl=' .
			$this->input->getCmd('tmpl'));

		return true;
	}

	public function delete()
	{
		$return = parent::delete();

		$redirect = $this->redirect;
		$tmp      = $this->input->get('dpplugin');
		if ($tmp) {
			$redirect .= '&dpplugin=' . $tmp;
		}
		$tmp = $this->input->get('tmpl');
		if ($tmp) {
			$redirect .= '&tmpl=' . $tmp;
		}
		$this->setRedirect($redirect);

		return $return;
	}

	public function publish()
	{
		$return = parent::publish();

		$redirect = $this->redirect;
		$tmp      = $this->input->get('dpplugin');
		if ($tmp) {
			$redirect .= '&dpplugin=' . $tmp;
		}
		$tmp = $this->input->get('tmpl');
		if ($tmp) {
			$redirect .= '&tmpl=' . $tmp;
		}
		$this->setRedirect($redirect);

		return $return;
	}

	public function cacheclear()
	{
		$plugin = $this->input->getCmd('dpplugin');

		if ($this->getModel()->cleanEventCache($plugin)) {
			JFactory::getApplication()->enqueueMessage(JText::_('COM_DPCALENDAR_VIEW_EXTCALENDAR_CACHE_CLEAR_SUCCESS'), 'message');
		} else {
			JFactory::getApplication()->enqueueMessage(JText::_('COM_DPCALENDAR_VIEW_EXTCALENDAR_CACHE_CLEAR_ERROR'), 'error');
		}

		$url = 'index.php?option=com_dpcalendar&view=extcalendars&dpplugin=' . $plugin;
		$tmp = $this->input->get('tmpl');
		if ($tmp) {
			$url .= '&tmpl=' . $tmp;
		}
		$this->setRedirect(JRoute::_($url, false));
	}

	public function sync()
	{
		$start = time();
		JPluginHelper::importPlugin('dpcalendar');
		JFactory::getApplication()->triggerEvent('onEventsSync', [$this->input->getCmd('dpplugin')]);
		$end = time();

		JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_DPCALENDAR_VIEW_EXTCALENDARS_SYNC_FINISHED', $end - $start), 'success');
		DPCalendarHelper::sendMessage(null);
	}
}
