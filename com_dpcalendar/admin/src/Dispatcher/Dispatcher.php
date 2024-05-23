<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Dispatcher;

defined('_JEXEC') or die();

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Plugin\PluginHelper;

class Dispatcher extends ComponentDispatcher
{
	public function dispatch(): void
	{
		// Determine the version
		$path = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$this->input->set('DPCALENDAR_VERSION', $manifest instanceof \SimpleXMLElement ? (string)$manifest->version : '');
		}

		// Map the front location form controller
		if ($this->input->get('task') == 'locationform.save') {
			$this->input->set('task', 'location.save');
		}

		PluginHelper::importPlugin('dpcalendar');
		$this->app->triggerEvent('onDPCalendarBeforeExecute', [$this->input]);

		parent::dispatch();
	}
}
