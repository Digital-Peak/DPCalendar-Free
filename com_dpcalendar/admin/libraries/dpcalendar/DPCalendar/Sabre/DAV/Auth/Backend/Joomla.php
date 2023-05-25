<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Sabre\DAV\Auth\Backend;

use Sabre\DAV\Auth\Backend;

class Joomla extends Backend\AbstractBasic
{
	protected function validateUserPass($username, $password)
	{
		$authenticate = \JAuthentication::getInstance();
		$response     = $authenticate->authenticate(['username' => $username, 'password' => $password]);

		if ($response->status === \JAuthentication::STATUS_SUCCESS) {
			$user = \JUser::getInstance((\JUserHelper::getUserId($username)));
			\JFactory::getSession()->set('user', $user);
		}

		return $response->status === \JAuthentication::STATUS_SUCCESS;
	}
}
