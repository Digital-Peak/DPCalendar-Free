<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\ThinHTTP as HTTP;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class DPCalendarControllerIcal extends BaseController
{
	public function download()
	{
		// Remove the script time limit.
		@set_time_limit(0);

		$loggedIn = false;
		if (Factory::getUser()->guest && $token = $this->input->get('token')) {
			$loggedIn = $this->login($token);
		}

		// Get the calendar
		$calendar = DPCalendarHelper::getCalendar($this->input->getCmd('id'));
		if (!$calendar) {
			throw new Exception('Invalid calendar!');
		}

		// Download the external url
		if ($calendar->icalurl === 'plugin') {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . $calendar->title . '.ics"');

			echo implode('', Factory::getApplication()->triggerEvent('onDPCalendarGetIcal', ['id' => $calendar->id]));
			Factory::getApplication()->close();
		}

		if (!empty($calendar->icalurl)) {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . $calendar->title . '.ics"');

			$headers = [
				'Accept-Language: ' . Factory::getUser()->getParam('language', Factory::getLanguage()->getTag()),
				'Accept: */*'
			];
			echo (new HTTP())->get($calendar->icalurl, null, null, $headers)->dp->body;
			Factory::getApplication()->close();
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
		header('Content-disposition: attachment; filename="' . Path::clean($calendar->title) . '.ics"');

		echo \DPCalendar\Helper\Ical::createIcalFromCalendar($calendars, false);

		if ($loggedIn) {
			Factory::getSession()->set('user', null);
		}
		Factory::getApplication()->close();
	}

	private function login($token)
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('id, params')->from('#__users')->where($db->quoteName('params') . ' like ' . $db->q('%' . $token . '%'));
		$db->setQuery($query);

		$user = $db->loadAssoc();

		if (!array_key_exists('id', $user)) {
			return false;
		}

		$user       = Factory::getUser($user['id']);
		$userParams = new Registry($user->params);

		// Check if really the token is passed
		if ($userParams->get('token') != $token) {
			return false;
		}

		// Get a fake login response
		\JLoader::import('joomla.user.authentication');
		$options            = ['remember' => false];
		$response           = new AuthenticationResponse();
		$response->status   = Authentication::STATUS_SUCCESS;
		$response->type     = 'icstoken';
		$response->username = $user->username;
		$response->email    = $user->email;
		$response->fullname = $user->name;

		// Run the login user events
		PluginHelper::importPlugin('user');
		Factory::getApplication()->triggerEvent('onLoginUser', [(array)$response, $options]);

		// Set the user in the session, effectively logging in the user
		Factory::getSession()->set('user', Factory::getUser($user->id));

		return true;
	}
}
