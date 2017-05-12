<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2013 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Sabre\DAV\Auth\Backend;
use Sabre\DAV\Auth\Backend;
use Sabre\HTTP;

class Joomla extends Backend\AbstractBasic
{

	protected function validateUserPass ($username, $password)
	{
		$authenticate = \JAuthentication::getInstance();
		$response = $authenticate->authenticate(array(
				'username' => $username,
				'password' => $password
		));

		if ($response->status === \JAuthentication::STATUS_SUCCESS)
		{
			$user = \JUser::getInstance((\JUserHelper::getUserId($username)));
			\JFactory::getSession()->set('user', $user);
		}

		return $response->status === \JAuthentication::STATUS_SUCCESS;
	}
}
