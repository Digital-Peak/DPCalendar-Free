<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;

class Pkg_DPCalendarInstallerScript extends InstallerScript implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	protected $minimumPhp = '8.1.0';

	protected $minimumJoomla = '4.4.4';

	protected $allowDowngrades = true;

	public function preflight($type, $parent): bool
	{
		if (!parent::preflight($type, $parent)) {
			return false;
		}

		// Check for the minimum Joomla 5 version before continuing
		if (version_compare(JVERSION, '5.0.0', '>=') && version_compare(JVERSION, '5.1.0', '<')) {
			Log::add(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', '5.1.0'), Log::WARNING, 'jerror');

			return false;
		}

		$version = null;
		$this->run("select * from `#__extensions` where element = 'pkg_dpcalendar'");
		$package = $this->getDatabase()->loadObject();
		if ($package) {
			$info = json_decode((string)$package->manifest_cache);
			if (isset($info->version)) {
				$version = $info->version;
			}
		}

		if (!\in_array($version, [null, '', '0', 'DP_DEPLOY_VERSION'], true) && version_compare($version, '10.6.0', '<')) {
			$app = Factory::getApplication();
			$app->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. Please install version 10.6.0 first before you upgrade to this package.',
				'error'
			);

			if ($app instanceof CMSWebApplicationInterface) {
				$app->redirect('index.php?option=com_installer&view=install');
			}

			return false;
		}

		return true;
	}

	public function update(InstallerAdapter $parent): void
	{
		$file = $parent->getParent()->getPath('source') . '/deleted.php';
		if (file_exists($file)) {
			require $file;
		}

		$path    = JPATH_ADMINISTRATOR . '/manifests/packages/pkg_dpcalendar.xml';
		$version = null;

		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = $manifest instanceof SimpleXMLElement ? (string)$manifest->version : null;
		}

		if (\in_array($version, [null, '', '0', 'DP_DEPLOY_VERSION'], true)) {
			return;
		}

		if (version_compare($version, '10.6.1', '<')) {
			if (PHP_OS_FAMILY !== 'Windows' && file_exists(JPATH_ROOT . '/administrator/manifests/packages/pkg_DPCalendar.xml')) {
				unlink(JPATH_ROOT . '/administrator/manifests/packages/pkg_DPCalendar.xml');
			}

			$this->run("update `#__extensions` set element = 'pkg_dpcalendar' where name = 'DPCalendar' and type = 'package'");
		}
	}

	public function postflight(string $type): void
	{
		// Perform some post install tasks
		if ($type === 'install') {
			$this->run("update `#__extensions` set enabled = 1 where type = 'plugin' and element = 'dpcalendar'");
			$this->run("update `#__extensions` set enabled = 1 where type = 'plugin' and element = 'manual'");

			$this->run(
				"insert ignore into `#__modules_menu` (menuid, moduleid) select 0 as menuid, id as moduleid from `#__modules` where module like 'mod_dpcalendar%'"
			);
		}

		// Make sure the installer plugin is enabled
		$this->run("update `#__extensions` set enabled = 1 where name = 'plg_installer_dpcalendar'");

		// Ensure DPCalendar update sites are enabled
		$this->run("update `#__update_sites` set enabled = 1 where name like '%DPCalendar%'");
	}

	private function run(string $query): void
	{
		try {
			$db = $this->getDatabase();
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $exception) {
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'error');
		}
	}
}
