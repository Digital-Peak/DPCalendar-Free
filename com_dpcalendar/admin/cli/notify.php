<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

define('_JEXEC', 1);

use Joomla\CMS\Application\CliApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Router;
use Joomla\Registry\Registry;

$path = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
if (isset($_SERVER["SCRIPT_FILENAME"])) {
	$path = dirname(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))));
}

define('JPATH_BASE', $path);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

error_reporting(E_ALL);
ini_set('display_errors', 1);

class DPCalendarEventNotifier extends CliApplication
{
	public function doExecute()
	{
		Log::addLogger(['text_file' => 'com_dpcalendars.cli.notify.errors.php'], Log::ERROR, 'com_dpcalendar');
		Log::addLogger(['text_file' => 'com_dpcalendars.cli.notify.php'], Log::NOTICE, 'com_dpcalendar');

		set_error_handler(function ($errorLevel, $errorMessage, $errorFile, $errorLine) {
			// Ignore deprecated messages
			if ($errorLevel == E_DEPRECATED || $errorLevel === E_USER_DEPRECATED) {
				return;
			}

			Log::add(
				'Fatal Error during event sync! Exception is in file ' . $errorFile . ' on line ' . $errorLine . ': ' . PHP_EOL . $errorMessage,
				Log::ERROR,
				'com_dpcalendar'
			);
		});

		Log::add('Starting with the DPCalendar notification', Log::DEBUG, 'com_dpcalendar');

		try {
			Log::add('Loading the database configuration', Log::DEBUG, 'com_dpcalendar');
			$config = Factory::getApplication();

			// Disabling session handling otherwise it will result in an error
			$config->set('session_handler', 'none');

			// Setting HOST
			$_SERVER['HTTP_HOST'] = $config->get('live_site');

			$db  = Factory::getDbo();
			$now = $db->quote(DPCalendarHelper::getDate()->format('Y-m-d H:i:00'));

			//$now = "'2022-04-09 19:00:00'";

			$query = $db->getQuery(true)
				->select('a.*')
				->from('#__dpcalendar_tickets a');
			$query->join('RIGHT', $db->quoteName('#__dpcalendar_events') . ' as e ON e.id = a.event_id');
			$query->where('a.reminder_sent_date is null');
			$query->where('e.state = 1');
			$query->where('a.state = 1');
			$query->where('e.start_date > ' . $now);
			$query->where(
				"(case when a.remind_type = 1
            then " . $now . " + interval a.remind_time minute <= e.start_date and
                 " . $now . " + interval 1 minute + interval a.remind_time minute > e.start_date
            when a.remind_type = 2
            then " . $now . " + interval a.remind_time hour <= e.start_date and
                 " . $now . " + interval 1 minute + interval a.remind_time hour > e.start_date
            when a.remind_type = 3
            then " . $now . " + interval a.remind_time day <= e.start_date and
                 " . $now . " + interval 1 minute + interval a.remind_time day > e.start_date
            when a.remind_type = 4
            then " . $now . " + interval 7*a.remind_time day <= e.start_date and
                 " . $now . " + interval 1 minute + interval 7*a.remind_time day > e.start_date
            when a.remind_type = 5
            then " . $now . " + interval a.remind_time month <= e.start_date and
                 " . $now . " + interval 1 minute + interval a.remind_time month > e.start_date
       		end) > 0"
			);
			$db->setQuery($query);

			Log::add('Loading the events to notify which should be notified for ' . $now, Log::DEBUG, 'com_dpcalendar');

			$result = $db->loadObjectList();

			Log::add('Found ' . count($result) . ' bookings to notify', Log::DEBUG, 'com_dpcalendar');

			foreach ($result as $ticket) {
				$this->send($ticket);
			}

			Log::add('Finished to send out the notification for ' . count($result) . ' bookings', Log::DEBUG, 'com_dpcalendar');
		} catch (Exception $e) {
			Log::add('Error checking notifications! Exception is: ' . PHP_EOL . $e, Log::ERROR, 'com_dpcalendar');
		}
	}

	private function send($ticket)
	{
		try {
			Log::add('Starting to send out the notificaton for the booking with the id: ' . $ticket->id, Log::DEBUG, 'com_dpcalendar');
			Log::add('Loading the event with the id: ' . $ticket->event_id, Log::DEBUG, 'com_dpcalendar');

			JLoader::register('DPCalendarTableEvent', JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables/event.php');
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');
			$model = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel');
			$event = $model->getItem($ticket->event_id);
			if (empty($event)) {
				return;
			}
			$events = [$event];

			Log::add('Settig up the texts', Log::DEBUG, 'com_dpcalendar');

			$siteLanguage = ComponentHelper::getParams('com_languages')->get('site', $this->get('language', 'en-GB'));
			Factory::getApplication()->set('language', Factory::getUser($ticket->user_id)->getParam('language', $siteLanguage));
			Factory::$language = null;
			Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR .  '/components/com_dpcalendar');

			$subject = DPCalendarHelper::renderEvents($events, Text::_('COM_DPCALENDAR_BOOK_NOTIFICATION_EVENT_SUBJECT'), null);

			$variables = [
				'sitename' => Factory::getApplication()->get('sitename'),
				'user'     => Factory::getUser()->name
			];
			$variables['hasLocation'] = !empty($events[0]->locations);
			$body                     = DPCalendarHelper::renderEvents(
				$events,
				Text::_('COM_DPCALENDAR_BOOK_NOTIFICATION_EVENT_BODY'),
				null,
				$variables
			);

			Log::add('Sending the mail to ' . $ticket->email, Log::DEBUG, 'com_dpcalendar');
			$mailer = clone Factory::getMailer();
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->IsHTML(true);
			$mailer->AddAddress($ticket->email);
			$mailer->Send();

			$db = Factory::getDbo();

			Log::add('Setting the reminder send date to now', Log::DEBUG, 'com_dpcalendar');
			$query = $db->getQuery(true)->update('#__dpcalendar_tickets');
			$query->set('reminder_sent_date=' . $db->quote(DPCalendarHelper::getDate()->toSql()));
			$query->where('id=' . (int)$ticket->id);
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $e) {
			Log::add('Error sending mail! Exception is: ' . PHP_EOL . $e, Log::ERROR, 'com_dpcalendar');
		}
		Log::add('Finished to send out the notificaton for the booking with the id: ' . $ticket->id, Log::DEBUG, 'com_dpcalendar');
	}

	public function getCfg($varname, $default = null)
	{
		$config = Factory::getApplication();

		return $config->get('' . $varname, $default);
	}

	public static function getRouter($name = '', array $options = [])
	{
		try {
			return new Router($options);
		} catch (Exception $e) {
			return null;
		}
	}

	public function getMenu($name = 'DPCalendar', $options = [])
	{
		try {
			return JMenu::getInstance($name, $options);
		} catch (Exception $e) {
			return null;
		}
	}

	public function isClient($name)
	{
		return $name == 'site';
	}

	public function isSite()
	{
		return true;
	}

	public function isAdmin()
	{
		return false;
	}

	public function getLanguageFilter()
	{
		return false;
	}

	public function getParams()
	{
		return new Registry();
	}

	public function getUserState($key, $default = null)
	{
		$session  = Factory::getSession();
		$registry = $session->get('registry');

		if (!is_null($registry)) {
			return $registry->get($key, $default);
		}

		return $default;
	}

	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none')
	{
		$cur_state = $this->getUserState($key, $default);
		$new_state = $this->input->get($request, null, $type);

		// Save the new value only if it was set in this request.
		if ($new_state !== null) {
			$this->setUserState($key, $new_state);
		} else {
			$new_state = $cur_state;
		}

		return $new_state;
	}

	public function setUserState($key, $value)
	{
		$session  = Factory::getSession();
		$registry = $session->get('registry');

		if (!is_null($registry)) {
			return $registry->set($key, $value);
		}

		return null;
	}

	public function getTemplate($params = false)
	{
		return 'isis';
	}

	public function getClientId()
	{
		return 10000;
	}

	public function getName()
	{
		return 'DPCalendarEventNotifier';
	}
}

$app                  = CliApplication::getInstance('DPCalendarEventNotifier');
Factory::$application = $app;
$app->execute();
