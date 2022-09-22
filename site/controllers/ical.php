<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use DigitalPeak\ThinHTTP as HTTP;
use Joomla\Registry\Registry;

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

			$headers = [
				'Accept-Language: ' . \JFactory::getUser()->getParam('language', \JFactory::getLanguage()->getTag()),
				'Accept: */*'
			];
			echo (new HTTP())->get($calendar->icalurl, null, null, $headers)->dp->body;
			JFactory::getApplication()->close();
		}

		if (!is_numeric($calendar->id)) {
			throw new Exception('Only native calendars are allowed!');
		}

		// Also include children when available
		$calendars = [$this->input->getCmd('id')];
		if (method_exists($calendar, 'getChildren')) {
			foreach ($calendar->getChildren() as $c) {
				$calendars[] = $c->id;
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
		$options            = ['remember' => false];
		$response           = new JAuthenticationResponse;
		$response->status   = JAuthentication::STATUS_SUCCESS;
		$response->type     = 'icstoken';
		$response->username = $user->username;
		$response->email    = $user->email;
		$response->fullname = $user->name;

		// Run the login user events
		JPluginHelper::importPlugin('user');
		JFactory::getApplication()->triggerEvent('onLoginUser', [(array)$response, $options]);

		// Set the user in the session, effectively logging in the user
		JFactory::getSession()->set('user', JFactory::getUser($user->id));

		return true;
	}
}
