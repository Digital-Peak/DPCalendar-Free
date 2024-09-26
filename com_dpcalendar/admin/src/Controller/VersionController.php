<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

class VersionController extends BaseController
{
	public function check(): void
	{
		Session::checkToken() or jexit('Invalid token');

		$version = 0;
		try {
			$version = $this->getModel()->getRemoteVersion($this->input->get('DPCALENDAR_VERSION', ''));
		} catch (\Throwable) {
		}

		DPCalendarHelper::sendMessage('', false, ['version' => $version]);
	}
}
