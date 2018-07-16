<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);

$path = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
if (isset($_SERVER["SCRIPT_FILENAME"])) {
	$path = dirname(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))));
}

define('JPATH_BASE', $path);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

JLog::addLogger(array(
	'text_file' => 'com_dpcalendars.cli.notify.errors.php'
), JLog::ERROR, 'com_dpcalendar');

JLog::addLogger(array(
	'text_file' => 'com_dpcalendars.cli.notify.php'
), JLog::NOTICE, 'com_dpcalendar');

error_reporting(E_ALL);
ini_set('display_errors', 1);

set_error_handler("DPErrorHandler");

function DPErrorHandler($error_level, $error_message, $error_file, $error_line, $error_context)
{
	JLog::add('Fatal Error during fetch! Exception is in file ' . $error_file . ' on line ' . $error_line . ': ' . PHP_EOL . $error_message,
		JLog::ERROR, 'com_dpcalendar');
}

JLog::add('Starting with the DPCalendar notification', JLog::DEBUG, 'com_dpcalendar');

class DPCalendarEventNotifier extends JApplicationCli
{

	public function doExecute()
	{
		try {
			JLog::add('Loading the database configuration', JLog::DEBUG, 'com_dpcalendar');
			$config = JFactory::getConfig();

			// Disabling session handling otherwise it will result in an error
			$config->set('session_handler', 'none');

			// Setting HOST
			$_SERVER['HTTP_HOST'] = $config->get('live_site');

			$db  = JFactory::getDbo();
			$now = $db->quote(DPCalendarHelper::getDate()->format('Y-m-d H:i:00'));

			// $now = "'2014-07-17 06:00:00'";

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
       		end) > 0");
			$db->setQuery($query);

			JLog::add('Loading the events to notify which should be notified for ' . $now, JLog::DEBUG, 'com_dpcalendar');

			$result = $db->loadObjectList();

			JLog::add('Found ' . count($result) . ' bookings to notify', JLog::DEBUG, 'com_dpcalendar');

			foreach ($result as $ticket) {
				$this->send($ticket);
			}

			JLog::add('Finished to send out the notification for ' . count($result) . ' bookings', JLog::DEBUG, 'com_dpcalendar');
		} catch (Exception $e) {
			JLog::add('Error checking notifications! Exception is: ' . PHP_EOL . $e, JLog::ERROR, 'com_dpcalendar');
		}
	}

	private function send($ticket)
	{
		try {
			JLog::add('Starting to send out the notificaton for the booking with the id: ' . $ticket->id, JLog::DEBUG, 'com_dpcalendar');
			JLog::add('Loading the event with the id: ' . $ticket->event_id, JLog::DEBUG, 'com_dpcalendar');

			JLoader::register('DPCalendarTableEvent', JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables/event.php');
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');
			$model = JModelLegacy::getInstance('Event', 'DPCalendarModel');
			$event = $model->getItem($ticket->event_id);
			if (empty($event)) {
				return;
			}
			$events = array(
				$event
			);

			JLog::add('Settig up the texts', JLog::DEBUG, 'com_dpcalendar');

			$siteLanguage = JComponentHelper::getParams('com_languages')->get('site', $this->get('language', 'en-GB'));
			JFactory::getConfig()->set('language', JUser::getInstance($ticket->user_id)->getParam('language', $siteLanguage));
			JFactory::$language = null;
			JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_dpcalendar');

			$subject = DPCalendarHelper::renderEvents($events, JText::_('COM_DPCALENDAR_BOOK_NOTIFICATION_EVENT_SUBJECT'), null);

			$variables                = array(
				'sitename' => JFactory::getConfig()->get('sitename'),
				'user'     => JFactory::getUser()->name
			);
			$variables['hasLocation'] = !empty($events[0]->locations);
			$body                     = DPCalendarHelper::renderEvents($events, JText::_('COM_DPCALENDAR_BOOK_NOTIFICATION_EVENT_BODY'), null,
				$variables);

			JLog::add('Sending the mail to ' . $ticket->email, JLog::DEBUG, 'com_dpcalendar');
			$mailer = JFactory::getMailer();
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->IsHTML(true);
			$mailer->AddAddress($ticket->email);
			$mailer->Send();

			$db = JFactory::getDbo();

			JLog::add('Setting the reminder send date to now', JLog::DEBUG, 'com_dpcalendar');
			$query = $db->getQuery(true)->update('#__dpcalendar_tickets');
			$query->set('reminder_sent_date=' . $db->quote(DPCalendarHelper::getDate()->toSql()));
			$query->where('id=' . (int)$ticket->id);
			$db->setQuery($query);
			$db->query();
		} catch (Exception $e) {
			JLog::add('Error sending mail! Exception is: ' . PHP_EOL . $e, JLog::ERROR, 'com_dpcalendar');
		}
		JLog::add('Finished to send out the notificaton for the booking with the id: ' . $ticket->id, JLog::DEBUG, 'com_dpcalendar');
	}

	public function getCfg($varname, $default = null)
	{
		$config = JFactory::getConfig();

		return $config->get('' . $varname, $default);
	}

	public static function getRouter($name = '', array $options = array())
	{
		JLoader::import('joomla.application.router');

		try {
			return new JRouter($options);
		} catch (Exception $e) {
			return null;
		}
	}

	public function getMenu($name = 'DPCalendar', $options = array())
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
		return new JRegistry();
	}

	public function getUserState($key, $default = null)
	{
		$session  = JFactory::getSession();
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
		$session  = JFactory::getSession();
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
}

$app                   = JApplicationCli::getInstance('DPCalendarEventNotifier');
JFactory::$application = $app;
$app->execute();
