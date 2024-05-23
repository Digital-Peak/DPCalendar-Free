<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class CaldavController extends BaseController
{
	public function syncUsers(): bool
	{
		$users = $this->getModel()->syncUsers();

		$msg = sprintf(Text::_('COM_DPCALENDAR_CONTROLLER_CALDAV_SYNC_SUCCESS'), $users);

		$this->setRedirect(Route::_('index.php?option=com_dpcalendar&view=tools', false), $msg);

		return true;
	}
}
