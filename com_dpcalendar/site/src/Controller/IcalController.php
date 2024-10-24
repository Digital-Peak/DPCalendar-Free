<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\ThinHTTP\CurlClient;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Filesystem\Path;

class IcalController extends BaseController implements UserFactoryAwareInterface, CurrentUserInterface
{
	use UserFactoryAwareTrait;
	use CurrentUserTrait;

	public function download(): void
	{
		$app = $this->app;
		if (!$app instanceof CMSWebApplicationInterface) {
			return;
		}

		// Remove the script time limit.
		@set_time_limit(0);

		$loggedIn = false;
		if ($this->getCurrentUser()->guest && $token = $this->input->get('token')) {
			$loggedIn = $this->login($token);
		}

		// Get the calendar
		$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($this->input->getCmd('id'));
		if (!$calendar instanceof CalendarInterface) {
			throw new \Exception('Invalid calendar!');
		}

		// Download the external url
		if ($calendar->getIcalUrl() !== '' && $calendar->getIcalUrl() !== '0' && $calendar->getIcalUrl() === 'plugin') {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . $calendar->getTitle() . '.ics"');

			echo implode('', $app->triggerEvent('onDPCalendarGetIcal', ['id' => $calendar->getId()]));
			$app->close();
		}

		if ($calendar->getIcalUrl() !== '' && $calendar->getIcalUrl() !== '0') {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . $calendar->getTitle() . '.ics"');

			$headers = [
				'Accept-Language: ' . $this->getCurrentUser()->getParam('language', $app->getLanguage()->getTag()),
				'Accept: */*'
			];
			echo (new CurlClient())->get($calendar->getIcalUrl(), null, null, $headers)->dp->body;
			$app->close();
		}

		if (!is_numeric($calendar->getId())) {
			throw new \Exception('Only native calendars are allowed!');
		}

		// Also include children when available
		$calendars = [$this->input->getCmd('id')];
		foreach ($calendar->getChildren() as $c) {
			$calendars[] = $c->getId();
		}

		// Download the ical content
		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-disposition: attachment; filename="' . Path::clean($calendar->getTitle()) . '.ics"');

		echo $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ical', 'Administrator')->createIcalFromCalendar($calendars, false);

		if ($loggedIn) {
			$app->getSession()->set('user', null);
		}
		$app->close();
	}

	private function login(string $token): bool
	{
		$app = $this->app;
		if (!$app instanceof CMSWebApplicationInterface) {
			return false;
		}

		// Check if really the token is passed
		$user = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Profile', 'Site')->getUserForToken($token);
		if ($user === null) {
			return false;
		}

		// Get a fake login response
		$options            = ['remember' => false];
		$response           = new AuthenticationResponse();
		$response->status   = (string)Authentication::STATUS_SUCCESS;
		$response->type     = 'icstoken';
		$response->username = $user->username;
		$response->email    = $user->email;
		$response->fullname = $user->name;

		// Run the login user events
		PluginHelper::importPlugin('user');
		$app->triggerEvent('onLoginUser', [(array)$response, $options]);

		// Set the user in the session, effectively logging in the user
		$app->getSession()->set('user', $user);

		return true;
	}
}
