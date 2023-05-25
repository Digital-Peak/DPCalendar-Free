<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

define('_JEXEC', 1);

use Joomla\CMS\Application\CliApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
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

class DPCalendarEventSync extends CliApplication
{
	public function doExecute()
	{
		Log::addLogger(['text_file' => 'com_dpcalendars.cli.eventsync.errors.php'], Log::ERROR, 'com_dpcalendar');
		Log::addLogger(['text_file' => 'com_dpcalendars.cli.eventsync.php'], Log::NOTICE, 'com_dpcalendar');

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

		Log::add('Starting with the DPCalendar event sync', Log::DEBUG, 'com_dpcalendar');

		// Disabling session handling otherwise it will result in an error
		Factory::getApplication()->set('session_handler', 'none');

		// Setting HOST
		if (empty($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = Factory::getApplication()->get('live_site');
		}

		// Run as super admin
		$user        = Factory::getUser();
		$user->guest = false;
		$reflection  = new ReflectionClass($user);
		$property    = $reflection->getProperty('isRoot');
		$property->setAccessible(true);
		$property->setValue($user, true);
		Factory::getSession()->set('user', $user);

		if ($ids = $this->input->getString('calids', [])) {
			$ids = explode(',', $ids);
		}

		try {
			PluginHelper::importPlugin('dpcalendar');
			Factory::getApplication()->triggerEvent('onEventsSync', [null, $ids]);

			Log::add('Finished with the DPCalendar event sync', Log::DEBUG, 'com_dpcalendar');
		} catch (Exception $e) {
			Log::add('Error during event sync! Exception is: ' . PHP_EOL . $e, Log::ERROR, 'com_dpcalendar');
		}
	}

	public function enqueueMessage($msg, $type = 'message')
	{
		Log::add($msg, Log::ERROR, 'com_dpcalendar');
	}

	public function getCfg($varname, $default = null)
	{
		return Factory::getApplication()->get('' . $varname, $default);
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

	public function getName()
	{
		return 'eventsync';
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
		$registry = Factory::getSession()->get('registry');
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
		$registry = Factory::getSession()->get('registry');
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

$app                  = CliApplication::getInstance('DPCalendarEventSync');
Factory::$application = $app;
$app->execute();
