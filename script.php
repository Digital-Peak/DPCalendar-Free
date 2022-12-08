<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class Pkg_DPCalendarInstallerScript extends InstallerScript
{
	protected $minimumPhp      = '7.4.0';
	protected $minimumJoomla   = '3.10.5';
	protected $allowDowngrades = true;

	public function preflight($type, $parent)
	{
		if (!parent::preflight($type, $parent)) {
			return false;
		}

		$version = null;
		$this->run("select * from `#__extensions` where element = 'pkg_dpcalendar'");
		$package = Factory::getDbo()->loadObject();
		if ($package) {
			$info = json_decode($package->manifest_cache);
			if (isset($info->version)) {
				$version = $info->version;
			}
		}

		if ($version && $version != 'DP_DEPLOY_VERSION' && version_compare($version, '7.0.0') < 0) {
			Factory::getApplication()->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 7.0.0 running. Please install the latest release from version 7 first.',
				'error'
			);
			Factory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}

		return true;
	}

	public function postflight($type, $parent)
	{
		// Perform some post install tasks
		if ($type == 'install') {
			$this->run("update `#__extensions` set enabled = 1 where type = 'plugin' and element = 'dpcalendar'");
			$this->run("update `#__extensions` set enabled = 1 where type = 'plugin' and element = 'manual'");

			$this->run(
				"insert ignore into `#__modules_menu` (menuid, moduleid) select 0 as menuid, id as moduleid from `#__modules` where module like 'mod_dpcalendar%'"
			);
		}

		// Make sure the installer plugin is enabled
		$this->run("update `#__extensions` set enabled = 1 where name = 'plg_installer_dpcalendar'");
	}

	private function run($query)
	{
		try {
			$db = Factory::getDBO();
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}
}
