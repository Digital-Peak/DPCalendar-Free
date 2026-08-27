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
use Joomla\CMS\Mail\MailHelper;
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

		$calendarModel = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator');

		// Get the calendar
		$calendar = $calendarModel->getCalendar($this->input->getCmd('id'));

		// Download the external url
		if ($calendar instanceof CalendarInterface && $calendar->getIcalUrl() !== '' && $calendar->getIcalUrl() === 'plugin') {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . $calendar->getTitle() . '.ics"');

			echo implode('', $app->triggerEvent('onDPCalendarGetIcal', ['id' => $calendar->getId()]));
			$app->close();
		}

		if ($calendar instanceof CalendarInterface && $calendar->getIcalUrl() !== '') {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . $calendar->getTitle() . '.ics"');

			$headers = [
				'Accept-Language: ' . $this->getCurrentUser()->getParam('language', $app->getLanguage()->getTag()),
				'Accept: */*'
			];
			echo (new CurlClient())->get($calendar->getIcalUrl(), null, null, $headers)->dp->body;
			$app->close();
		}

		$calendars = [];
		$ids       = [];
		if ($id = $this->input->getCmd('id')) {
			$ids[] = $id;
		}
		if ($id = $this->input->getString('ids')) {
			$ids = array_merge($ids, explode(',', $id));
		}

		foreach ($ids as $id) {
			$calendar = $calendarModel->getCalendar(trim($id));
			if (!$calendar instanceof CalendarInterface) {
				throw new \Exception('Calendar not found with id: ' . $id . '!', 404);
			}

			if (!is_numeric($calendar->getId())) {
				continue;
			}

			// Also include children when available
			$calendars[] = $calendar->getId();
			foreach ($calendar->getChildren() as $c) {
				$calendars[] = $c->getId();
			}
		}

		if (!$calendar instanceof CalendarInterface) {
			throw new \Exception('Calendar not found!', 404);
		}

		$fileName = \count($calendars) > 1 ? 'calendars' : Path::clean($calendar->getTitle());

		// Download the ical content
		$app->setHeader('Content-Type', 'text/calendar; charset=utf-8', true);
		$app->setHeader('Content-disposition', 'attachment; filename="' . $fileName . '.ics"', true);

		$buffer = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ical', 'Administrator')->createIcalFromCalendar($calendars, false);
		$buffer = MailHelper::convertRelativeToAbsoluteUrls($buffer);
		echo $buffer;

		if ($loggedIn) {
			$app->getSession()->set('user');
		}

		// @deprecated no format is not supported
		if ($app->getInput()->get('format') !== 'raw') {
			header('Content-Type: text/calendar; charset=utf-8');
			header('Content-disposition: attachment; filename="' . $fileName . '.ics"');
			$app->close();
		}
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
