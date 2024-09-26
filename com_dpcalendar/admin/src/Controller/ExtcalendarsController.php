<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;

class ExtcalendarsController extends AdminController
{
	protected $text_prefix = 'COM_DPCALENDAR_EXTCALENDAR';

	public function getModel($name = 'Extcalendar', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function import(): bool
	{
		$this->setRedirect(
			'index.php?option=com_dpcalendar&view=extcalendars&layout=import&dpplugin=' . $this->input->getCmd('dpplugin') . '&tmpl=' .
			$this->input->get('tmpl', '')
		);

		return true;
	}

	public function delete(): void
	{
		parent::delete();

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
	}

	public function publish(): void
	{
		parent::publish();

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
	}

	public function cacheclear(): void
	{
		$plugin = $this->input->getCmd('dpplugin');

		if ($this->getModel('Extcalendar')->cleanEventCache($plugin)) {
			$this->app->enqueueMessage(Text::_('COM_DPCALENDAR_VIEW_EXTCALENDAR_CACHE_CLEAR_SUCCESS'), 'message');
		} else {
			$this->app->enqueueMessage(Text::_('COM_DPCALENDAR_VIEW_EXTCALENDAR_CACHE_CLEAR_ERROR'), 'error');
		}

		$url = 'index.php?option=com_dpcalendar&view=extcalendars&dpplugin=' . $plugin;
		$tmp = $this->input->get('tmpl');
		if ($tmp) {
			$url .= '&tmpl=' . $tmp;
		}
		$this->setRedirect(Route::_($url, false));
	}

	public function sync(): void
	{
		$start = time();
		PluginHelper::importPlugin('dpcalendar');
		$this->app->triggerEvent('onEventsSync', [$this->input->getCmd('dpplugin')]);
		$end = time();

		$this->app->enqueueMessage(Text::sprintf('COM_DPCALENDAR_VIEW_EXTCALENDARS_SYNC_FINISHED', $end - $start), 'success');
		DPCalendarHelper::sendMessage('');
	}
}
