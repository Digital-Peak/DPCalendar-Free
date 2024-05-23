<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Profile;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class HtmlView extends BaseView
{
	protected array $calendars    = [];
	protected array $readMembers  = [];
	protected array $writeMembers = [];
	protected array $events       = [];
	protected array $users        = [];
	protected Pagination $pagination;

	protected function init(): void
	{
		if ($this->user->guest !== 0) {
			$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_NOT_LOGGED_IN'), 'warning');
			$this->app->redirect(Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance())));

			return;
		}

		$this->calendars    = $this->get('Items');
		$this->readMembers  = $this->get('ReadMembers');
		$this->writeMembers = $this->get('WriteMembers');
		$this->events       = $this->get('Events');
		$this->users        = $this->get('Users');
		$this->pagination   = $this->get('Pagination');
	}
}
