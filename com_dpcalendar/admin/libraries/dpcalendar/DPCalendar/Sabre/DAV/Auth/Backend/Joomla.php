<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Sabre\DAV\Auth\Backend;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Sabre\DAV\Auth\Backend\AbstractBasic;

class Joomla extends AbstractBasic
{
	protected function validateUserPass($username, $password)
	{
		$authenticate = Authentication::getInstance();
		$response     = $authenticate->authenticate(['username' => $username, 'password' => $password]);

		if ($response->status === Authentication::STATUS_SUCCESS) {
			$user = User::getInstance((UserHelper::getUserId($username)));
			Factory::getSession()->set('user', $user);
		}

		return $response->status === Authentication::STATUS_SUCCESS;
	}
}
