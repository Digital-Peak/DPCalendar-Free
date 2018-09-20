<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::register('DPCalendarHelper', dirname(__FILE__) . '/admin/helpers/dpcalendar.php');

class Com_DPCalendarInstallerScript
{

	public function install($parent)
	{
	}

	public function update($parent)
	{
		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = (string)$manifest->version;
		}
		if (empty($version)) {
			return;
		}

		$db = JFactory::getDbo();
		if (version_compare($version, '6.0.0') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('titleformat_week', null);
			$params->set('titleformat_day', null);
			$params->set('timeformat_month', null);
			$params->set('timeformat_week', null);
			$params->set('timeformat_day', null);
			$params->set('timeformat_list', null);
			$params->set('axisformat', null);
			$params->set('week_mode', 'variable');
			$params->set('show_event_as_popup', '0');

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');

			// Upgrade SabreDAV
			$db->setQuery("select id, calendardata from `#__dpcalendar_caldav_calendarobjects`");
			$events = $db->loadObjectList();
			JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);
			foreach ($events as $event) {
				try {
					$vobj = Sabre\VObject\Reader::read($event->calendardata);
				} catch (Exception $e) {
					JFactory::getApplication()->enqueueMessage("Warning! Item with id $event->id could not be parsed!", 'error');
					continue;
				}
				$uid  = null;
				$item = $vobj->getBaseComponent();
				if (!isset($item->UID)) {
					JFactory::getApplication()->enqueueMessage("Warning! Item with id $event->id does NOT have a UID property and this is required!",
						'error');
					continue;
				}
				$uid = (string)$item->UID;

				$this->run('update #__dpcalendar_caldav_calendarobjects set uid = ' . $db->quote($uid) . ' where id = ' . $event->id);
			}

			// Rename DPCalendar plugins from dpcalendar_foo to foo
			$rootPath = JPATH_PLUGINS . '/dpcalendar/';
			foreach (JFolder::folders($rootPath) as $oldName) {
				$newName = str_replace('dpcalendar_', '', $oldName);
				if ($newName == $oldName) {
					continue;
				}
				JFile::delete($rootPath . $oldName . '/' . $oldName . '.xml');
				JFile::delete($rootPath . $oldName . '/' . $oldName . '.php');
				JFile::move($rootPath . $oldName, $rootPath . $newName);

				$this->run(
					"update `#__extensions` set 
					element = REPLACE(element, '" . $oldName . "', '" . $newName . "'), 
					manifest_cache = REPLACE(manifest_cache, '\"filename\":\"" . $oldName . "', '\"filename\":\"" . $newName . "') 
					where element = '" . $oldName . "'"
				);
			}
		}

		if (version_compare($version, '6.0.3') == -1) {
			JFile::delete(JPATH_SITE . '/components/com_dpcalendar/models/forms/event.xml');
			JFile::delete(JPATH_SITE . '/components/com_dpcalendar/models/forms/location.xml');
		}

		if (version_compare($version, '6.0.9') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('event_create_form', $params->get('event_edit_popup', 1));

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '6.0.10') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('fixed_week_count', $params->get('week_mode', 'variable') == 'variable' ? 1 : 0);

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '6.1.2') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('show_map', $params->get('show_map', '1') == '2' ? 0 : 1);

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '6.2.0') == -1) {
			$db->setQuery(
				"select id,rooms from `#__dpcalendar_locations` where rooms is not null and rooms != ''");
			foreach ($db->loadObjectList() as $index => $loc) {
				$rooms = json_encode(array('rooms0' => array('id' => $index + 1, 'title' => $loc->rooms)));
				$this->run('UPDATE `#__dpcalendar_locations` SET rooms = ' . $db->quote($rooms) . ' where id = ' . $loc->id);
			}

			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('list_show_map', $params->get('list_show_map', '1') == '2' ? 0 : 1);

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '7.0.0') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('sef_advanced', 0);
			$params->set('map_provider', 'google');

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');

			// Disable template overrides
			$rootPath = JPATH_SITE . '/templates';
			foreach (JFolder::folders($rootPath, '.', true, true) as $path) {
				if (strpos($path, 'dpcalendar') === false || strpos($path, '-v6') !== false || !file_exists($path)) {
					continue;
				}
				JFolder::move($path, $path . '-v6');
				JFactory::getApplication()->enqueueMessage('We have disabled the template overrides in ' . $path . '. They do not work with version 7 anymore!',
					'warning');
			}

			// Cleanup files
			foreach (JFolder::files(JPATH_ROOT, '.', true, true) as $path) {
				if (strpos($path, 'dpcalendar') === false || !file_exists($path)) {
					continue;
				}

				if (strpos($path, '/tmpl/edit') === false
					&& strpos($path, '/administrator/components/com_dpcalendar/models/event.php') === false) {
					continue;
				}

				JFile::delete($path);
			}
		}
		if (version_compare($version, '7.0.6') == -1) {
			JFile::delete(JPATH_ROOT . '/components/com_dpcalendar/views/ticketform/tmpl/default.xml');
		}
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

		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = (string)$manifest->version;
		}
		if (!empty($version) && version_compare($version, '6.0.0') < 0) {
			JFactory::getApplication()->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 6.0.0 running. Please get in touch with our support.',
				'error');
			JFactory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}
	}

	public function postflight($type, $parent)
	{
		JLoader::import('joomla.filesystem.folder');
		if (JFolder::exists(JPATH_ADMINISTRATOR . '/components/com_falang/contentelements')) {
			JFile::copy(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/libraries/falang/dpcalendar_events.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_events.xml');
			JFile::copy(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/libraries/falang/dpcalendar_locations.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_locations.xml');
		}

		if ($type == 'install') {
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element = 'dpcalendar'");
			$this->run("update `#__extensions` set enabled=1 where type = 'plugin' and element = 'manual'");

			$this->run(
				"insert into `#__modules_menu` (menuid, moduleid) select 0 as menuid, id as moduleid from `#__modules` where module like 'mod_dpcalendar%'");

			// Create default calendar
			JTable::addIncludePath(JPATH_LIBRARIES . '/joomla/database/table');
			$category              = JTable::getInstance('Category');
			$category->extension   = 'com_dpcalendar';
			$category->title       = 'Uncategorised';
			$category->alias       = 'uncategorised';
			$category->description = '';
			$category->published   = 1;
			$category->access      = 1;
			$category->params      = '{"category_layout":"","image":"","color":"3366CC"}';
			$category->metadata    = '{"author":"","robots":""}';
			$category->language    = '*';
			$category->setLocation(1, 'last-child');
			$category->store(true);
			$category->rebuildPath($category->id);
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
