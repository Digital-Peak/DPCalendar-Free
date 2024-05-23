<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Sabre\DAV\Auth\Backend;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Session\SessionInterface;
use Sabre\DAV\Auth\Backend\AbstractBasic;

class Joomla extends AbstractBasic
{
	use UserFactoryAwareTrait;

	public function __construct(private SessionInterface $session, UserFactoryInterface $factory, private \DPCalendarCalDavServer $app)
	{
		$this->setUserFactory($factory);
	}

	protected function validateUserPass($username, $password)
	{
		$authenticate = Authentication::getInstance();
		$response     = $authenticate->authenticate(['username' => $username, 'password' => $password]);

		if ($response->status == Authentication::STATUS_SUCCESS) {
			$user = $this->getUserFactory()->loadUserByUsername($username);
			$this->session->set('user', $user);
			$this->app->loadIdentity($user);
		}

		return $response->status == Authentication::STATUS_SUCCESS;
	}
}
