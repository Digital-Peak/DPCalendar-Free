<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::register('DPCalendarHelper', dirname(__FILE__) . '/admin/helpers/dpcalendar.php');

class Com_DPCalendarInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
	protected $minimumPhp = '7.4.0';
	protected $minimumJoomla = '3.9.0';
	protected $allowDowngrades = true;

	public function update($parent)
	{
		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;

		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = (string)$manifest->version;
		}

		if (empty($version) || $version == 'DP_DEPLOY_VERSION') {
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
			JLoader::import('components.com_dpcalendar.vendor.autoload', JPATH_ADMINISTRATOR);
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
					JFactory::getApplication()->enqueueMessage(
						"Warning! Item with id $event->id does NOT have a UID property and this is required!",
						'error'
					);
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
				"select id,rooms from `#__dpcalendar_locations` where rooms is not null and rooms != ''"
			);
			foreach ($db->loadObjectList() as $index => $loc) {
				$rooms = json_encode(['rooms0' => ['id' => $index + 1, 'title' => $loc->rooms]]);
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
				JFactory::getApplication()->enqueueMessage(
					'We have disabled the template overrides in ' . $path . '. They do not work with version 7 anymore!',
					'warning'
				);
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

		if (version_compare($version, '7.2.7') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('list_filter_featured', $params->get('list_filter_featured', '2') == '2' ? 0 : 1);

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');

			// Map was never shown, so disable it
			$this->run('update #__modules set params = replace(params, \'"show_map":"1"\', \'"show_map":"0"\') where `module` like "mod_dpcalendar_mini"');
			$this->run('update #__modules set params = replace(params, \'"show_map":"2"\', \'"show_map":"0"\') where `module` like "mod_dpcalendar_mini"');
		}

		if (version_compare($version, '7.3.3') == -1) {
			JFolder::delete(JPATH_PLUGINS . '/system/dpcalendar');
		}

		if (version_compare($version, '7.3.4') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('description_length', $params->get('description_length', 100));

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');

			$this->run('update #__modules set params = replace(params, \'"description_length":""\', \'"description_length":"100"\') where `module` like "mod_dpcalendar_mini"');
		}

		if (version_compare($version, '7.4.0') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('booking_registration', $params->get('booking_show_registration', 100));

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '8.0.0') == -1) {
			// Defaulting some params which have changed
			$params = JComponentHelper::getParams('com_dpcalendar');
			$params->set('canceltext', 'COM_DPCALENDAR_FIELD_CONFIG_BOOKINGSYS_CANCEL_TEXT');
			$params->set('cancelpaidtext', 'COM_DPCALENDAR_FIELD_CONFIG_BOOKINGSYS_CANCEL_PAID_TEXT');
			$params->set('ordertext', 'COM_DPCALENDAR_FIELD_CONFIG_BOOKINGSYS_ORDER_TEXT');

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');

			// Cleanup tickets for original events
			$db->setQuery('select t.id,t.type,t.booking_id,e.original_id,e.price
from #__dpcalendar_tickets as t 
inner join #__dpcalendar_events as e on t.event_id = e.id and e.original_id > 0 and e.booking_series = 1
left join #__dpcalendar_bookings as b on t.booking_id = b.id');
			$bookings        = [];
			$ticketsToDelete = [];
			foreach ($db->loadObjectList() as $ticket) {
				if (array_key_exists($ticket->booking_id . '-' . $ticket->type, $bookings)) {
					$ticketsToDelete[] = $ticket->id;
					continue;
				}
				$price = 0;
				$types = json_decode($ticket->price);
				if ($types && array_key_exists($ticket->type, $types->value)) {
					$price = $types->value[$ticket->type];
				}
				$db->setQuery("update #__dpcalendar_tickets set event_id = " . $ticket->original_id . ", price = '" . $price . "' where id = " . $ticket->id);
				$db->execute();

				$bookings[$ticket->booking_id . '-' . $ticket->type] = true;
			}
			if ($ticketsToDelete) {
				$db->setQuery('delete from #__dpcalendar_tickets where id in (' . implode(',', $ticketsToDelete) . ')');
				$db->execute();
			}

			$db->setQuery("select * from #__extensions where folder like 'dpcalendarpay'");
			foreach ($db->loadObjectList() as $plugin) {
				if (!$plugin->params || $plugin->params == '[]' || $plugin->params == '{}' || strpos($plugin->params, 'providers0') !== false) {
					continue;
				}

				$params = '{"providers":{"providers0":{"id":1,"title":"Default","description":"",';
				$params .= trim($plugin->params, '{}');
				$params .= '}}}';

				if ($plugin->element == 'stripe') {
					$params = str_replace('data-skey', 'secret_key', $params);
					$params = str_replace('data-pkey', 'public_key', $params);
				}

				$db->setQuery("update #__extensions set params = " . $db->quote($params) . " where extension_id = " . $plugin->extension_id);
				$db->execute();
			}

			// Disable template overrides
			$rootPath = JPATH_SITE . '/templates';
			foreach (JFolder::folders($rootPath, '.', true, true) as $path) {
				if (strpos($path, 'dpcalendar') === false || strpos($path, '-v7') !== false || !file_exists($path)) {
					continue;
				}
				JFolder::move($path, $path . '-v7');
				JFactory::getApplication()->enqueueMessage(
					'We have disabled the template overrides in ' . $path . '. They do not work with version 8 anymore!',
					'warning'
				);
			}
		}
		if (version_compare($version, '8.0.2') == -1) {
			if (is_dir(JPATH_PLUGINS . '/dpcalendarpay/2checkout')) {
				JFolder::delete(JPATH_PLUGINS . '/dpcalendarpay/2checkout');
			}
		}
	}

	public function preflight($type, $parent)
	{
		if (!parent::preflight($type, $parent)) {
			return false;
		}

		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = (string)$manifest->version;
		}
		if (!empty($version) && $version != 'DP_DEPLOY_VERSION' && version_compare($version, '6.0.0') < 0) {
			JFactory::getApplication()->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 6.0.0 running. Please install the latest release from version 6 first.',
				'error'
			);
			JFactory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}
	}

	public function postflight($type, $parent)
	{
		if ($parent->getElement() != 'com_dpcalendar') {
			return;
		}

		JLoader::import('joomla.filesystem.folder');
		if (JFolder::exists(JPATH_ADMINISTRATOR . '/components/com_falang/contentelements')) {
			JFile::copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/libraries/falang/dpcalendar_events.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_events.xml'
			);
			JFile::copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/libraries/falang/dpcalendar_locations.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_locations.xml'
			);
		}

		if ($type == 'install' || $type == 'discover_install') {
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

			\Joomla\CMS\Factory::getDbo()->setQuery('update #__extensions set params = \'{"providers":{"providers0":{"id":1,"title":"Default","description":"PLG_DPCALENDARPAY_MANUAL_PAY_BUTTON_DESC","state": 4,"payment_statement":"PLG_DPCALENDARPAY_MANUAL_PAYMENT_STATEMENT_TEXT"}}}\' where name like \'plg_dpcalendarpay_manual\'');
			\Joomla\CMS\Factory::getDbo()->execute();
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
