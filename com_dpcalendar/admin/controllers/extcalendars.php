<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerExtcalendars extends AdminController
{
	protected $text_prefix = 'COM_DPCALENDAR_EXTCALENDAR';

	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->input = Factory::getApplication()->input;
	}

	public function getModel($name = 'Extcalendar', $prefix = 'DPCalendarModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function import()
	{
		$this->setRedirect(
			'index.php?option=com_dpcalendar&view=extcalendars&layout=import&dpplugin=' . $this->input->getCmd('dpplugin') . '&tmpl=' .
			$this->input->getCmd('tmpl')
		);

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
			Factory::getApplication()->enqueueMessage(Text::_('COM_DPCALENDAR_VIEW_EXTCALENDAR_CACHE_CLEAR_SUCCESS'), 'message');
		} else {
			Factory::getApplication()->enqueueMessage(Text::_('COM_DPCALENDAR_VIEW_EXTCALENDAR_CACHE_CLEAR_ERROR'), 'error');
		}

		$url = 'index.php?option=com_dpcalendar&view=extcalendars&dpplugin=' . $plugin;
		$tmp = $this->input->get('tmpl');
		if ($tmp) {
			$url .= '&tmpl=' . $tmp;
		}
		$this->setRedirect(Route::_($url, false));
	}

	public function sync()
	{
		$start = time();
		PluginHelper::importPlugin('dpcalendar');
		Factory::getApplication()->triggerEvent('onEventsSync', [$this->input->getCmd('dpplugin')]);
		$end = time();

		Factory::getApplication()->enqueueMessage(Text::sprintf('COM_DPCALENDAR_VIEW_EXTCALENDARS_SYNC_FINISHED', $end - $start), 'success');
		DPCalendarHelper::sendMessage(null);
	}
}
