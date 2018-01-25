<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

use Joomla\Registry\Registry;

defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controller');

class DPCalendarControllerIcal extends JControllerLegacy
{
	public function download()
	{
		// Remove the script time limit.
		@set_time_limit(0);

		$loggedIn = false;
		if (JFactory::getUser()->guest && $token = $this->input->get('token')) {
			$loggedIn = $this->login($token);
		}

		// Get the calendar
		$calendar = DPCalendarHelper::getCalendar($this->input->getCmd('id'));

		if (!$calendar) {
			throw new Exception('Invalid calendar!');
		}

		// Download the external url
		if (!empty($calendar->icalurl)) {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . $calendar->title . '.ics"');
			echo \DPCalendar\Helper\DPCalendarHelper::fetchContent($calendar->icalurl);
			JFactory::getApplication()->close();
		}

		if (!is_numeric($calendar->id)) {
			throw new Exception('Only native calendars are allowed!');
		}

		// Also include children when available
		$calendars = array($this->input->getCmd('id'));
		if (method_exists($calendar, 'getChildren')) {
			$childrens = $calendar->getChildren();
			if ($childrens) {
				foreach ($childrens as $c) {
					$calendars[] = $c->id;
				}
			}
		}

		// Download the ical content
		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-disposition: attachment; filename="' . \JPath::clean($calendar->title) . '.ics"');

		echo \DPCalendar\Helper\Ical::createIcalFromCalendar($calendars, false);

		if ($loggedIn) {
			JFactory::getSession()->set('user', null);
		}
		\JFactory::getApplication()->close();
	}

	private function login($token)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select('id, params')->from('#__users')->where($db->quoteName('params') . ' like ' . $db->q('%' . $token . '%'));
		$db->setQuery($query);

		$user = $db->loadAssoc();

		if (!array_key_exists('id', $user)) {
			return false;
		}

		$user       = JFactory::getUser($user['id']);
		$userParams = new Registry($user->params);

		// Check if really the token is passed
		if ($userParams->get('token') != $token) {
			return false;
		}

		// Get a fake login response
		\JLoader::import('joomla.user.authentication');
		$options            = array('remember' => false);
		$response           = new JAuthenticationResponse;
		$response->status   = JAuthentication::STATUS_SUCCESS;
		$response->type     = 'icstoken';
		$response->username = $user->username;
		$response->email    = $user->email;
		$response->fullname = $user->name;

		// Run the login user events
		JPluginHelper::importPlugin('user');
		JFactory::getApplication()->triggerEvent('onLoginUser', array((array)$response, $options));

		// Set the user in the session, effectively logging in the user
		JFactory::getSession()->set('user', JFactory::getUser($user->id));

		return true;
	}
}
