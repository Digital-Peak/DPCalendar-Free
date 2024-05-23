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
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Router;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;

$path = dirname(__FILE__, 5);
if (isset($_SERVER["SCRIPT_FILENAME"])) {
	$path = dirname((string)$_SERVER["SCRIPT_FILENAME"], 5);
}

define('JPATH_BASE', $path);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);

// @phpstan-ignore-next-line
class DPCalendarEventSync extends CliApplication
{
	private Registry $session = new Registry();

	protected function doExecute(): void
	{
		Log::addLogger(['text_file' => 'com_dpcalendars.cli.eventsync.errors.php'], Log::ERROR, ['com_dpcalendar']);
		Log::addLogger(['text_file' => 'com_dpcalendars.cli.eventsync.php'], Log::NOTICE, ['com_dpcalendar']);

		set_error_handler(static function (int $errorLevel, string $errorMessage, string $errorFile, int $errorLine): bool {
			// Ignore deprecated messages
			if ($errorLevel == E_DEPRECATED || $errorLevel === E_USER_DEPRECATED) {
				return true;
			}
			Log::add(
				'Fatal Error during event sync! Exception is in file ' . $errorFile . ' on line ' . $errorLine . ': ' . PHP_EOL . $errorMessage,
				Log::ERROR,
				'com_dpcalendar'
			);
			return true;
		});

		Log::add('Starting with the DPCalendar event sync', Log::DEBUG, 'com_dpcalendar');

		// Disabling session handling otherwise it will result in an error
		$this->set('session_handler', 'none');

		// Setting HOST
		if (empty($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = $this->get('live_site');
		}

		// Run as super admin
		$user        = new User();
		$user->guest = 0;
		$reflection  = new ReflectionClass($user);
		$property    = $reflection->getProperty('isRoot');
		$property->setAccessible(true);
		$property->setValue($user, true);

		$this->session->set('user', $user);

		if (($ids = $this->input->getString('calids', '')) !== '' && ($ids = $this->input->getString('calids', '')) !== '0') {
			$ids = explode(',', $ids);
		}

		try {
			PluginHelper::importPlugin('dpcalendar');
			$this->triggerEvent('onEventsSync', [null, $ids]);

			Log::add('Finished with the DPCalendar event sync', Log::DEBUG, 'com_dpcalendar');
		} catch (Exception $exception) {
			Log::add('Error during event sync! Exception is: ' . PHP_EOL . $exception, Log::ERROR, 'com_dpcalendar');
		}
	}

	public function enqueueMessage($msg, $type = 'message'): void
	{
		Log::add($msg, Log::ERROR, 'com_dpcalendar');
	}

	public function getCfg(string $varname, mixed $default = null): string
	{
		return $this->get('' . $varname, $default);
	}

	public static function getRouter(): ?Router
	{
		return new Router();
	}

	public function getMenu(string $name = 'DPCalendar', array $options = []): ?AbstractMenu
	{
		try {
			// @phpstan-ignore-next-line
			return AbstractMenu::getInstance($name, $options);
		} catch (Exception) {
			return null;
		}
	}

	public function isClient($name): bool
	{
		return $name == 'site';
	}

	public function isSite(): bool
	{
		return true;
	}

	public function isAdmin(): bool
	{
		return false;
	}

	public function getName(): string
	{
		return 'eventsync';
	}

	public function getLanguageFilter(): bool
	{
		return false;
	}

	public function getParams(): Registry
	{
		return new Registry();
	}

	public function getUserState(string $key, mixed $default = null): mixed
	{
		$this->session = $this->session->get('registry');
		if (!is_null($this->session)) {
			return $this->session->get($key, $default);
		}

		return $default;
	}

	public function getUserStateFromRequest(string $key, string $request, mixed $default = null, string $type = 'none'): mixed
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

	public function setUserState(string $key, mixed $value): mixed
	{
		return $this->session->set($key, $value);
	}

	public function getTemplate(): string
	{
		return 'atumn';
	}
}

$app                  = new DPCalendarEventSync();
Factory::$application = $app;
$app->execute();
