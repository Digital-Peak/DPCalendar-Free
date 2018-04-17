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
				'This DPCalendar version does only run on Joomla 3.7 and above, please upgrade your Joomla version or install an older version of DPCalendar!',
				'error');
			JFactory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}

		if (version_compare(PHP_VERSION, '5.5.9') < 0) {
			JFactory::getApplication()->enqueueMessage(
				'You have PHP version ' . PHP_VERSION . ' installed. Please upgrade your PHP version to at least 5.5.9. DPCalendar can not run on this version.',
				'error');
			JFactory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}

		// The system plugin needs to be disabled during upgrade
		$this->run("update `#__extensions` set enabled = 0 where type = 'plugin' and element = 'dpcalendar' and folder = 'system'");

		return true;
	}

	public function postflight($type, $parent)
	{
		// The system plugin needs to be disabled during upgrade
		$this->run("update `#__extensions` set enabled = 1 where type = 'plugin' and element = 'dpcalendar' and folder = 'system'");

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
