<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class Pkg_DPCalendarInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
	protected $minimumPhp      = '7.4.0';
	protected $minimumJoomla   = '3.9.0';
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

		// Delete existing update sites, necessary if upgrading eg. free to pro
		$this->run(
			"delete from #__update_sites_extensions where extension_id in (select extension_id from #__extensions where element = 'pkg_dpcalendar')"
		);
		$this->run("delete from #__update_sites where name like 'DPCalendar%'");

		return true;
	}

	public function postflight($type, $parent)
	{
		// Perform some post install tasks
		if ($type == 'install') {
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element = 'dpcalendar'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element = 'manual'");

			$this->run(
				"insert ignore into `#__modules_menu` (menuid, moduleid) select 0 as menuid, id as moduleid from `#__modules` where module like 'mod_dpcalendar%'"
			);
		}

		if ($type == 'update') {
			$updater = function ($event) {
				$installer = $event;
				if ($installer instanceof \Joomla\Event\Event) {
					$installer = $installer->getArgument('installer');
				}

				if ($installer->getManifest()->packagename != 'dpcalendar') {
					return;
				}

				// Set the download ID if available
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
				$model = BaseDatabaseModel::getInstance('Cpanel', 'DPCalendarModel');
				$model->refreshUpdateSite();
			};

			// Update sites are created in a plugin
			if (DPCalendarHelper::isJoomlaVersion(4, '>=')) {
				Factory::getApplication()->getDispatcher()->addListener('onExtensionAfterInstall', $updater);
				Factory::getApplication()->getDispatcher()->addListener('onExtensionAfterUpdate', $updater);
			} else {
				\JEventDispatcher::getInstance()->register('onExtensionAfterInstall', $updater);
				\JEventDispatcher::getInstance()->register('onExtensionAfterUpdate', $updater);
			}
		}
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
