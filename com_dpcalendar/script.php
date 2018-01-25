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
		if (version_compare($version, '2.0.0') == -1) {
			$this->run("select * from `#__dpcalendar_events` where original_id = -1");
			$events = $db->loadObjectList();
			foreach ($events as $event) {
				$rule = '';
				switch ($event->scheduling) {
					case 1:
						if ($event->scheduling_daily_weekdays == 1) {
							$rule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
						} else {
							$rule = 'FREQ=DAILY';
						}
						break;
					case 2:
						$rule     = 'FREQ=WEEKLY';
						$registry = new JRegistry();
						$registry->loadString($event->scheduling_weekly_days);
						$weeklyDays = $registry->toArray();
						if (count($weeklyDays) > 0) {
							$rule .= ';BYDAY=';
						}
						$map = array(
							1 => 'MO',
							2 => 'TU',
							3 => 'WE',
							4 => 'TH',
							5 => 'FR',
							6 => 'SA',
							7 => 'SU'
						);
						foreach ($weeklyDays as $day) {
							$rule .= $map[$day] . ',';
						}
						$rule = trim($rule, ',');
						break;
					case 3:
						$rule     = 'FREQ=MONTHLY';
						$registry = new JRegistry();
						$registry->loadString($event->scheduling_monthly_days);
						$monthlyDays = $registry->toArray();
						if (count($monthlyDays) > 0) {
							$rule .= ';BYMONTHDAY=' . implode(',', $monthlyDays);
						}
						break;
					case 4:
						$rule = 'FREQ=YEARLY';
						break;
				}
				if (!empty($event->scheduling_end_date)) {
					$rule .= ';UNTIL=' . str_replace('-', '', substr($event->scheduling_end_date, 0, 10)) . '235959Z';
				}

				$this->run("update `#__dpcalendar_events` set rrule='" . $rule . "' where id =" . $event->id);
			}
			$this->run("ALTER TABLE `#__dpcalendar_events` drop `scheduling_start_date`");
			$this->run("ALTER TABLE `#__dpcalendar_events` drop `scheduling_end_date`");
			$this->run("ALTER TABLE `#__dpcalendar_events` drop `scheduling_daily_weekdays`");
			$this->run("ALTER TABLE `#__dpcalendar_events` drop `scheduling_weekly_days`");
			$this->run("ALTER TABLE `#__dpcalendar_events` drop `scheduling_monthly_days`");

			foreach (JFolder::files(JPATH_ADMINISTRATOR . '/language', '.*dpcalendar.*', true, true) as $file) {
				JFile::delete($file);
			}
			foreach (JFolder::files(JPATH_SITE . '/language', '.*dpcalendar.*', true, true) as $file) {
				JFile::delete($file);
			}
		}
		if (version_compare($version, '2.2.0') == -1) {
			$db->setQuery(
				"select id,location,latitude,longitude from `#__dpcalendar_events` where location is not null and location != '' group by location");
			$locations = $db->loadObjectList();
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
			foreach ($locations as $loc) {
				$data = array();
				if ($loc->latitude != 0 && $loc->longitude != 0) {
					$data['latitude']  = $loc->latitude;
					$data['longitude'] = $loc->longitude;
					$data['title']     = $loc->location;
					$data['country']   = $loc->location;
				} else {
					$content = DPCalendarHelper::fetchContent('http://maps.google.com/maps/api/geocode/json?address=' . urlencode($loc->location));
					if (!empty($content)) {
						$tmp = json_decode($content);

						if ($tmp) {
							if ($tmp->status == 'OK') {
								if (!empty($tmp->results)) {
									foreach ($tmp->results[0]->address_components as $part) {
										switch ($part->types[0]) {
											case 'country':
												$data['country'] = $part->long_name;
												break;
											case 'administrative_area_level_1':
												$data['province'] = $part->long_name;
												break;
											case 'locality':
												$data['city'] = $part->long_name;
												break;
											case 'postal_code':
												$data['zip'] = $part->long_name;
												break;
											case 'route':
												$data['street'] = $part->long_name;
												break;
											case 'street_number':
												$data['number'] = $part->long_name;
												break;
										}
									}

									$data['latitude']  = $tmp->results[0]->geometry->location->lat;
									$data['longitude'] = $tmp->results[0]->geometry->location->lng;

									$data['title'] = $tmp->results[0]->formatted_address;
								}
							}
						}
					}
				}

				if (!empty($data)) {
					$data['state']    = 1;
					$data['language'] = '*';
					$table            = JTable::getInstance('Location', 'DPCalendarTable');
					$table->save($data);

					if ($table->id) {
						$this->run(
							'insert into #__dpcalendar_events_location (event_id, location_id) select id as event_id, ' . $table->id .
							" as location_id from #__dpcalendar_events where location='" . $loc->location . "'");
					}
				}
			}
			$this->run("ALTER TABLE `#__dpcalendar_events` drop `location`");
			$this->run("ALTER TABLE `#__dpcalendar_events` drop `latitude`");
			$this->run("ALTER TABLE `#__dpcalendar_events` drop `longitude`");
		}

		if (version_compare($version, '4.0.1') == -1) {
			if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/events.php')) {
				JFile::delete(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/events.php');
			}
		}
		if (version_compare($version, '4.0.5') == -1) {
			$db->setQuery("select * from #__dpcalendar_extcalendars where plugin = 'google' or plugin = 'caldav'");

			foreach ($db->loadObjectList() as $cal) {
				$params = new JRegistry();
				$params->loadString($cal->params);
				$params->set('action-create', true);
				$params->set('action-edit', true);
				$params->set('action-delete', true);

				$this->run('update #__dpcalendar_extcalendars set params = ' . $db->q($params->toString()) . ' where id = ' . (int)$cal->id);
			}
		}
		if (version_compare($version, '4.2.5') == -1) {
			$db->setQuery("select * from #__dpcalendar_extcalendars where plugin = 'caldav'");

			foreach ($db->loadObjectList() as $cal) {
				$params = new JRegistry();
				$params->loadString($cal->params);
				$params->set('calendar', '/calendars/' . trim($params->get('calendar'), '/'));

				$this->run('update #__dpcalendar_extcalendars set params = ' . $db->q($params->toString()) . ' where id = ' . (int)$cal->id);
			}
		}

		if (version_compare($version, '5.0.0') == -1) {
			// Clearing the location cache
			$cache = JFactory::getCache('com_dpcalendar_location', '');
			$cache->cache->clean('com_dpcalendar_location', '');

			$db->setQuery("select id, original_id from `#__dpcalendar_events` where original_id = 0 or original_id = '-1'");
			$events = $db->loadObjectList();
			JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);
			foreach ($events as $event) {
				$uid = strtoupper(Sabre\VObject\UUIDUtil::getUUID());

				$childs = '';
				if ($event->original_id == '-1') {
					$childs = ' or original_id = ' . $event->id;
				}

				$this->run('update #__dpcalendar_events set uid = ' . $db->quote($uid) . ' where id = ' . $event->id . $childs);
			}
		}
		if (version_compare($version, '5.3.0') == -1) {
			// Update the UID's
			JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);

			$db->setQuery("select id from `#__dpcalendar_bookings`");
			$bookings = $db->loadObjectList();
			foreach ($bookings as $booking) {
				$uid = strtoupper(Sabre\VObject\UUIDUtil::getUUID());

				$db->setQuery('update #__dpcalendar_bookings set uid = ' . $db->quote($uid) . ' where id = ' . $booking->id);
				$db->query();
			}

			// Create one ticket per booking
			$this->run(
				"insert into #__dpcalendar_tickets(booking_id, event_id, user_id, uid, name, email, remind_time, remind_type, reminder_sent_date, public, created, state, price)
select id, event_id, user_id, uid, name, email, remind_time, remind_type, reminder_sent_date, public, book_date, state, price
from #__dpcalendar_bookings");

			// Remove obsolete fields
			$this->run(
				"ALTER TABLE `#__dpcalendar_bookings` DROP `event_id`, DROP `remind_time`, DROP `remind_type`, DROP `reminder_sent_date`, DROP `public`;");
			$this->run("ALTER TABLE `#__dpcalendar_bookings` ADD INDEX `state` (`state`)");
			$this->run("ALTER TABLE `#__dpcalendar_bookings` ADD INDEX `user_id` (`user_id`)");

			// Renaming payment plugins to new structure
			JLoader::import('joomla.filesystem.folder');
			JLoader::import('joomla.filesystem.file');

			$rootPath = JPATH_PLUGINS . '/dpcalendarpay/';
			foreach (JFolder::folders($rootPath) as $oldName) {
				$newName = str_replace('dpcalendar_', '', $oldName);
				if ($newName == $oldName) {
					continue;
				}
				JFile::delete($rootPath . $oldName . '/' . $oldName . '.xml');
				JFile::delete($rootPath . $oldName . '/' . $oldName . '.php');
				JFile::move($rootPath . $oldName, $rootPath . $newName);

				$this->run(
					"update `#__extensions` set name = REPLACE(name, '" . $oldName . "', 'dpcalendarpay_" . $newName .
					"'), element = REPLACE(element, '" . $oldName . "', '" . $newName . "'), manifest_cache = REPLACE(manifest_cache, '" .
					$oldName . "', '" . $newName . "') where element = '" . $oldName . "'");
			}
		}
		if (version_compare($version, '5.5.0') == -1) {
			$db->setQuery("select id, price from `#__dpcalendar_events` where price is not null");
			$events = $db->loadObjectList();
			foreach ($events as $event) {
				$data = array(
					'value'       => array(
						$event->price
					),
					'label'       => array(
						''
					),
					'description' => array(
						''
					)
				);
				$this->run('update #__dpcalendar_events set price = ' . $db->quote(json_encode($data)) . ' where id = ' . $event->id);
			}
		}

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
	}

	public function uninstall($parent)
	{
	}

	public function preflight($type, $parent)
	{
		// Delete existing update sites, neccessary if upgrading eg. free to pro
		$this->run(
			"delete from #__update_sites_extensions where extension_id in (select extension_id from #__extensions where element = 'pkg_dpcalendar')");
		$this->run("delete from #__update_sites where name like 'DPCalendar%'");

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

		// On upgrades, we probably need to update the schema table when we are
		// prior 5.6.2
		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = (string)$manifest->version;
		}
		if (!empty($version)) {
			$db = JFactory::getDbo();
			$db->setQuery("select * from #__schemas where extension_id in (select extension_id from #__extensions where element = 'com_dpcalendar')");
			if (!$db->loadAssoc()) {
				$db->setQuery(
					"insert into #__schemas (extension_id, version_id)
				select extension_id," . $db->quote($version) .
					" from #__extensions where element = 'com_dpcalendar'");
				$db->execute();
			}
		}
	}

	public function postflight($type, $parent)
	{
		if (JFile::exists(JPATH_SITE . '/components/com_jcomments/jcomments.php')) {
			JFile::copy(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/libraries/jcomments/com_dpcalendar.plugin.php',
				JPATH_SITE . '/components/com_jcomments/plugins/com_dpcalendar.plugin.php');
		}

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
