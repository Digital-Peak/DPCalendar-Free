<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
define('_JEXEC', 1);

$path = dirname(dirname(dirname(__FILE__)));
if (isset($_SERVER["SCRIPT_FILENAME"])) {
	$path = dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])));
}

define('JPATH_BASE', $path);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

JLoader::import('joomla.user.authentication');
JLoader::import('joomla.application.component.helper');

JLog::addLogger(['text_file' => 'com_dpcalendar.caldav.errors.php'], JLog::ALL, 'com_dpcalendar');

class DPCalendarCalDavServer extends JApplicationCms
{
	public function __construct(JInput $input = null, JRegistry $config = null, JApplicationWebClient $client = null)
	{
		if (!$config && Joomla\CMS\Version::MAJOR_VERSION == 4) {
			$config = Joomla\CMS\Factory::getContainer()->get('config');
		}
		if (!$config) {
			$config = Joomla\CMS\Factory::getConfig();
		}
		$config->set('caching', 0);
		$config->set('debug', false);

		// Register the application name
		$this->name = 'caldav';

		parent::__construct($input, $config, $client);
	}

	public function doExecute()
	{
		function exception_error_handler($errno, $errstr, $errfile, $errline)
		{
			// Ignore deprecated messages
			if ($errno == E_USER_DEPRECATED) {
				return;
			}

			JLog::add('Something crashed during a CalDAV request on ' . $errfile . ' ' . $errline . PHP_EOL . $errstr, JLog::ERROR, 'com_dpcalendar');
			if (JDEBUG) {
				$text = '';
				foreach (debug_backtrace() as $item) {
					if (empty($item['line'])) {
						continue;
					}
					$text .= PHP_EOL . $item['line'] . ' ' . $item['file'];
				}

				JLog::add('Here is the stack trace:' . $text, JLog::DEBUG, 'com_dpcalendar');
			}
			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		}

		set_error_handler("exception_error_handler");

		try {
			if (Joomla\CMS\Version::MAJOR_VERSION == 4) {
				$this->setContainer(Joomla\CMS\Factory::getContainer());
				$this->setSession(Joomla\CMS\Factory::getContainer()->get('session.web.site'));
				$this->setDispatcher(Joomla\CMS\Factory::getContainer()->get('dispatcher'));
			}

			JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

			$config = JFactory::getApplication();

			// Load the right language
			$siteLanguage = \JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
			$config->set('language', $siteLanguage);

			$this->initialiseApp();

			$pdo = new \PDO(
				'mysql:host=' . $config->get('host') . ';dbname=' . $config->get('db') . ';charset=utf8',
				$config->get('user'),
				$config->get('password')
			);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			JFactory::getApplication()->input->set('format', 'raw');

			$authBackend = new \DPCalendar\Sabre\DAV\Auth\Backend\Joomla(JFactory::getDbo());
			$authBackend->setRealm('');
			$calendarBackend                                 = new \DPCalendar\Sabre\CalDAV\Backend\DPCalendar($pdo);
			$calendarBackend->calendarTableName              = $config->get('dbprefix') . 'dpcalendar_caldav_calendars';
			$calendarBackend->calendarObjectTableName        = $config->get('dbprefix') . 'dpcalendar_caldav_calendarobjects';
			$calendarBackend->calendarChangesTableName       = $config->get('dbprefix') . 'dpcalendar_caldav_calendarchanges';
			$calendarBackend->calendarInstancesTableName     = $config->get('dbprefix') . 'dpcalendar_caldav_calendarinstances';
			$calendarBackend->calendarSubscriptionsTableName = $config->get('dbprefix') . 'dpcalendar_caldav_calendarsubscriptions';
			$calendarBackend->schedulingObjectTableName      = $config->get('dbprefix') . 'dpcalendar_caldav_schedulingobjects';
			$principalBackend                                = new \Sabre\DAVACL\PrincipalBackend\PDO($pdo);
			$principalBackend->tableName                     = $config->get('dbprefix') . 'dpcalendar_caldav_principals';
			$principalBackend->groupMembersTableName         = $config->get('dbprefix') . 'dpcalendar_caldav_groupmembers';

			$tree = [
				new \Sabre\CalDAV\Principal\Collection($principalBackend),
				new \Sabre\CalDAV\CalendarRoot($principalBackend, $calendarBackend)
			];

			\Sabre\DAV\Server::$exposeVersion = false;

			$server                  = new \Sabre\DAV\Server($tree);
			$server->debugExceptions = JDEBUG;

			$uri = trim(JUri::root(true), '/');
			if (strpos($uri, 'components/com_dpcalendar') === false) {
				$uri .= '/components/com_dpcalendar/';
			}
			$uri = '/' . trim($uri, '/') . '/' . 'caldav.php';
			$server->setBaseUri($uri);

			$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, 'SabreDAV'));
			$server->addPlugin(new \DPCalendar\Sabre\DAVACL\Joomla());
			$server->addPlugin(new \Sabre\CalDAV\Plugin());
			$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
			$server->addPlugin(new \Sabre\CalDAV\Schedule\Plugin());

			$server->addPlugin(new \Sabre\DAV\Browser\Plugin());
			$server->start();
		} catch (Exception $e) {
			$message = 'Something crashed during a CalDAV request: ' . PHP_EOL . $e;
			JLog::add($message, JLog::ERROR, 'com_dpcalendar');

			$DOM               = new \DOMDocument('1.0', 'utf-8');
			$DOM->formatOutput = true;

			$error = $DOM->createElementNS('DAV:', 'd:error');
			$error->setAttribute('xmlns:s', \Sabre\DAV\Server::NS_SABREDAV);
			$DOM->appendChild($error);

			$error->appendChild($DOM->createElement('s:exception', htmlspecialchars(get_class($e), ENT_NOQUOTES, 'UTF-8')));
			$error->appendChild($DOM->createElement('s:message', htmlspecialchars($e->getMessage(), ENT_NOQUOTES, 'UTF-8')));

			header('Content-Type:application/xml; charset=utf-8');
			http_response_code(500);
			echo $DOM->saveXML();
		}
	}

	public function enqueueMessage($msg, $type = 'message')
	{
		JLog::add('A message was thrown of type ' . $type . ' during a CalDAV request: ' . PHP_EOL . $msg, JLog::ERROR, 'com_dpcalendar');
	}

	public function getCfg($varname, $default = null)
	{
		$config = JFactory::getApplication();

		return $config->get('' . $varname, $default);
	}

	public static function getRouter($name = '', array $options = [])
	{
		JLoader::import('joomla.application.router');

		try {
			return new JRouter($options);
		} catch (Exception $e) {
			return null;
		}
	}

	public function getMenu($name = 'site', $options = [])
	{
		try {
			return JMenu::getInstance($name, $options);
		} catch (Exception $e) {
			return null;
		}
	}

	public function getClientId()
	{
		return 0;
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
}

$app                   = JApplicationWeb::getInstance('DPCalendarCalDavServer');
JFactory::$application = $app;
$app->execute();
