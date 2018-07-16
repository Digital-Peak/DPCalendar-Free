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
	'text_file' => 'com_dpcalendars.cli.eventsync.errors.php'
), JLog::ERROR, 'com_dpcalendar');

JLog::addLogger(array(
	'text_file' => 'com_dpcalendars.cli.eventsync.php'
), JLog::NOTICE, 'com_dpcalendar');

error_reporting(E_ALL);
ini_set('display_errors', 1);

set_error_handler("DPErrorHandler");

function DPErrorHandler($error_level, $error_message, $error_file, $error_line, $error_context)
{
	JLog::add('Fatal Error during event sync! Exception is in file ' . $error_file . ' on line ' . $error_line . ': ' . PHP_EOL . $error_message,
		JLog::ERROR, 'com_dpcalendar');
}

JLog::add('Starting with the DPCalendar event sync', JLog::DEBUG, 'com_dpcalendar');

class DPCalendarEventSync extends JApplicationCli
{

	public function doExecute()
	{
		// Disabling session handling otherwise it will result in an error
		JFactory::getConfig()->set('session_handler', 'none');

		try {
			JPluginHelper::importPlugin('dpcalendar');
			JFactory::getApplication()->triggerEvent('onEventsSync');

			JLog::add('Finished with the DPCalendar event sync', JLog::DEBUG, 'com_dpcalendar');
		} catch (Exception $e) {
			JLog::add('Error during event sync! Exception is: ' . PHP_EOL . $e, JLog::ERROR, 'com_dpcalendar');
		}
	}

	public function enqueueMessage($msg, $type = 'message')
	{
		JLog::add($msg, JLog::ERROR, 'com_dpcalendar');
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
}

$app                   = JApplicationCli::getInstance('DPCalendarEventSync');
JFactory::$application = $app;
$app->execute();
