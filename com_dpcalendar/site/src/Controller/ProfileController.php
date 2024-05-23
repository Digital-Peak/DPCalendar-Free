<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

class ProfileController extends BaseController
{
	public function change(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$this->getModel()->setUsers(explode(',', $this->input->getString('users', '')), $this->input->getString('action', ''));
		DPCalendarHelper::sendMessage('');
	}

	public function tz(): void
	{
		$tz = new \DateTimeZone($this->input->getString('tz', ''));

		if ($this->app instanceof CMSWebApplicationInterface) {
			$this->app->getSession()->set('DPCalendar.user-timezone', $tz->getName());
		}

		$this->setRedirect(base64_decode($this->input->getBase64('return', Uri::base())));
	}

	public function getModel($name = 'profile', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
