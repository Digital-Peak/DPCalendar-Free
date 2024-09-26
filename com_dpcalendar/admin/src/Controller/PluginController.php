<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Plugin\DPCalendarPlugin;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;

class PluginController extends BaseController
{
	public function action(): void
	{
		$plugin = $this->input->getWord('dpplugin', $this->input->getWord('plugin', ''));

		PluginHelper::importPlugin('dpcalendar');

		if (!PluginHelper::isEnabled('dpcalendar', $plugin)) {
			$pluginData = $this->getModel()->getPluginData($plugin);

			$pluginInstance = $this->app->bootPlugin($plugin, 'dpcalendar');
			if ($pluginInstance instanceof DPCalendarPlugin) {
				$pluginInstance->setConfig((array)$pluginData);
			}

			$pluginInstance->registerListeners();
		}

		$this->app->triggerEvent('onDPCalendarDoAction', [$this->input->getWord('action', ''), $plugin]);
		$this->app->close();
	}
}
