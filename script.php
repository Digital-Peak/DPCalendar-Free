<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class Pkg_DPCalendarInstallerScript
{

	public function install($parent)
	{
	}

	public function update($parent)
	{
	}

	public function uninstall($parent)
	{
	}

	public function preflight($type, $parent)
	{
		// Check if the local Joomla version does fit the minimum requirement
		if (version_compare(JVERSION, '3.7') == -1) {
			JFactory::getApplication()->enqueueMessage(
				'This DPCalendar version does only run on Joomla 3.7 and above, please upgrade your Joomla version first and then try again.',
				'error');
			JFactory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}

		if (version_compare(PHP_VERSION, '5.5.9') < 0) {
			JFactory::getApplication()->enqueueMessage(
				'You have PHP with version ' . PHP_VERSION . ' installed. Please upgrade your PHP version to at least 5.5.9. DPCalendar can not run on this version.',
				'error');
			JFactory::getApplication()->redirect('index.php?option=com_installer&view=install');

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

		if ($version && version_compare($version, '6.0.0') < 0) {
			JFactory::getApplication()->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 6.0.0 running. Please get in touch with our support.',
				'error');
			JFactory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}

		// The system plugin needs to be disabled during upgrade
		$this->run("update `#__extensions` set enabled = 0 where type = 'plugin' and element = 'dpcalendar' and folder = 'system'");


		// Delete existing update sites, necessary if upgrading eg. free to pro
		$this->run(
			"delete from #__update_sites_extensions where extension_id in (select extension_id from #__extensions where element = 'pkg_dpcalendar')");
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
				"insert into `#__modules_menu` (menuid, moduleid) select 0 as menuid, id as moduleid from `#__modules` where module like 'mod_dpcalendar%'");
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
