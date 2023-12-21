<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Filesystem\Folder;

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

	public function update($parent)
	{
		$file = $parent->getParent()->getPath('source') . '/deleted.php';
		if (file_exists($file)) {
			require $file;
		}

		$path    = JPATH_ADMINISTRATOR . '/manifests/packages/pkg_dpcalendar.xml';
		$version = null;

		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = (string)$manifest->version;
		}

		if (empty($version) || $version == 'DP_DEPLOY_VERSION') {
			return;
		}

		if (version_compare($version, '8.16.0') == -1) {
			$this->run("update #__update_sites set location = 'https://joomla.digital-peak.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=43&ext=extension.xml' where location = 'https://joomla.digital-peak.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=44&ext=extension.xml'");

			$this->run("update #__extensions set package_id = 0
				where package_id = (select * from (select extension_id from #__extensions where element ='pkg_dpcalendar') as e)
				and name not in ('plg_actionlog_dpcalendar', 'plg_content_dpcalendar', 'plg_fields_dpcalendar', 'plg_installer_dpcalendar', 'plg_privacy_dpcalendar', 'plg_user_dpcalendar', 'com_dpcalendar')");

			$folders = Folder::folders(JPATH_ROOT, 'dpcalendar', true, true, ['api', 'cache', 'cli', 'images', 'layouts', 'libraries', 'media', 'templates', 'test']);
			$folders = array_merge($folders, Folder::folders(JPATH_PLUGINS . '/dpcalendar', '.', false, true));
			$folders = array_merge($folders, Folder::folders(JPATH_PLUGINS . '/dpcalendarpay', '.', false, true));
			foreach ($folders as $folder) {
				if (!is_dir($folder . '/language')) {
					continue;
				}

				foreach (Folder::files($folder . '/language', '.', true, true) as $file) {
					if (strpos(basename($file), basename(dirname($file))) === 0) {
						unlink($file);
					}
				}
			}
		}
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
