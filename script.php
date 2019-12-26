<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class Pkg_DPCalendarInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
	protected $minimumPhp = '5.6.0';
	protected $minimumJoomla = '3.9.0';

	public function preflight($type, $parent)
	{
		if (!parent::preflight($type, $parent)) {
			return false;
		}

		$version = null;
		$this->run("select * from `#__extensions` where element = 'pkg_dpcalendar'");
		$package = JFactory::getDbo()->loadObject();
		if ($package) {
			$info = json_decode($package->manifest_cache);
			if (isset($info->version)) {
				$version = $info->version;
			}
		}

		if ($version && $version != 'DP_DEPLOY_VERSION' && version_compare($version, '6.0.0') < 0) {
			JFactory::getApplication()->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 6.0.0 running. Please install the latest release from version 6 first.',
				'error'
			);
			JFactory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}

		// The system plugin needs to be disabled during upgrade
		$this->run("update `#__extensions` set enabled = 0 where type = 'plugin' and element = 'dpcalendar' and folder = 'system'");


		// Delete existing update sites, necessary if upgrading eg. free to pro
		$this->run(
			"delete from #__update_sites_extensions where extension_id in (select extension_id from #__extensions where element = 'pkg_dpcalendar')"
		);
		$this->run("delete from #__update_sites where name like 'DPCalendar%'");

		return true;
	}

	public function postflight($type, $parent)
	{
		// The system plugin needs to be disabled during upgrade
		$this->run("update `#__extensions` set enabled = 1 where type = 'plugin' and element = 'dpcalendar' and folder = 'system'");

		// Perform some post install tasks
		if ($type == 'install') {
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element = 'dpcalendar'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element = 'manual'");

			$this->run(
				"insert into `#__modules_menu` (menuid, moduleid) select 0 as menuid, id as moduleid from `#__modules` where module like 'mod_dpcalendar%'"
			);
		}
	}

	private function run($query)
	{
		try {
			$db = JFactory::getDBO();
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $e) {
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}
}
