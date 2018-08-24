<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class Mod_DPCalendar_UpcomingInstallerScript
{

	public function install($parent)
	{
	}

	public function update($parent)
	{
		$path    = JPATH_SITE . '/modules/mod_dpcalendar_upcoming/mod_dpcalendar_upcoming.xml';
		$version = null;
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = (string)$manifest->version;
		}
		if (empty($version)) {
			return;
		}

		if (version_compare($version, '7.0.3') == -1) {
			// Cleanup old layouts
			$validFiles = ['_scripts.php', 'blog.php', 'default.php', 'horizontal.php', 'icon.php', 'panel.php', 'simple.php', 'timeline.php'];
			foreach (JFolder::files(JPATH_SITE . '/modules/mod_dpcalendar_upcoming/tmpl', '.', true, true) as $path) {
				if (in_array(basename($path), $validFiles)) {
					continue;
				}

				JFile::delete($path);
			}
		}
	}

	public function uninstall($parent)
	{
	}

	public function preflight($type, $parent)
	{
	}

	public function postflight($type, $parent)
	{
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
