<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

class Com_DPCalendarInstallerScript extends InstallerScript implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	protected $minimumPhp      = '8.1.0';
	protected $minimumJoomla   = '4.4.4';
	protected $allowDowngrades = true;

	public function update(): void
	{
		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;

		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = $manifest instanceof SimpleXMLElement ? (string)$manifest->version : '';
		}

		if ($version === null || $version === '' || $version === '0' || $version === 'DP_DEPLOY_VERSION') {
			return;
		}

		$db = $this->getDatabase();

		if (version_compare($version, '10.0.0') == -1) {
			// Disable template overrides
			$rootPath = JPATH_SITE . '/templates';
			foreach (Folder::folders($rootPath, '.', true, true) as $path) {
				if (!str_contains((string)$path, 'dpcalendar') || str_contains((string)$path, '-v9') || !file_exists($path)) {
					continue;
				}

				Folder::move($path, $path . '-v9');
				Factory::getApplication()->enqueueMessage(
					'We have disabled the template overrides in ' . $path . '. They do not work with version 10 anymore!',
					'warning'
				);
			}

			$db->setQuery("select * from #__extensions where name = 'plg_dpcalendar_csv' OR name = 'plg_dpcalendar_spreadsheet'");
			foreach ($db->loadObjectList() as $plugin) {
				$data = [
					'export_events_fields' => [
						'export_events_fields0'  => ['field' => 'id'],
						'export_events_fields1'  => ['field' => 'title'],
						'export_events_fields2'  => ['field' => 'catid'],
						'export_events_fields3'  => ['field' => 'color'],
						'export_events_fields4'  => ['field' => 'url'],
						'export_events_fields5'  => ['field' => 'start_date'],
						'export_events_fields6'  => ['field' => 'end_date'],
						'export_events_fields7'  => ['field' => 'all_day'],
						'export_events_fields8'  => ['field' => 'rrule'],
						'export_events_fields9'  => ['field' => 'description'],
						'export_events_fields10' => ['field' => 'location_ids'],
						'export_events_fields11' => ['field' => 'featured'],
						'export_events_fields12' => ['field' => 'state'],
						'export_events_fields13' => ['field' => 'access'],
						'export_events_fields14' => ['field' => 'access_content'],
						'export_events_fields15' => ['field' => 'language'],
						'export_events_fields16' => ['field' => 'created'],
						'export_events_fields17' => ['field' => 'created_by'],
						'export_events_fields18' => ['field' => 'modified'],
						'export_events_fields19' => ['field' => 'modified_by'],
						'export_events_fields20' => ['field' => 'uid'],
						'export_events_fields21' => ['field' => 'timezone']
					],
					'export_bookings_fields' => [
						'export_bookings_fields0'  => ['field' => 'uid'],
						'export_bookings_fields1'  => ['field' => 'state'],
						'export_bookings_fields2'  => ['field' => 'first_name'],
						'export_bookings_fields3'  => ['field' => 'name'],
						'export_bookings_fields4'  => ['field' => 'email'],
						'export_bookings_fields5'  => ['field' => 'telephone'],
						'export_bookings_fields6'  => ['field' => 'country'],
						'export_bookings_fields7'  => ['field' => 'province'],
						'export_bookings_fields8'  => ['field' => 'city'],
						'export_bookings_fields9'  => ['field' => 'zip'],
						'export_bookings_fields10' => ['field' => 'street'],
						'export_bookings_fields11' => ['field' => 'number'],
						'export_bookings_fields12' => ['field' => 'price'],
						'export_bookings_fields13' => ['field' => 'options'],
						'export_bookings_fields14' => ['field' => 'net_amount'],
						'export_bookings_fields15' => ['field' => 'payment_provider'],
						'export_bookings_fields16' => ['field' => 'user_id'],
						'export_bookings_fields17' => ['field' => 'book_date'],
						'export_bookings_fields18' => ['field' => 'event_id'],
						'export_bookings_fields19' => ['field' => 'event_author'],
						'export_bookings_fields20' => ['field' => 'event_calid'],
						'export_bookings_fields21' => ['field' => 'timezone']
					],
					'export_tickets_fields' => [
						'export_tickets_fields0'  => ['field' => 'uid'],
						'export_tickets_fields1'  => ['field' => 'state'],
						'export_tickets_fields2'  => ['field' => 'first_name'],
						'export_tickets_fields3'  => ['field' => 'name'],
						'export_tickets_fields4'  => ['field' => 'event_title'],
						'export_tickets_fields5'  => ['field' => 'start_date'],
						'export_tickets_fields6'  => ['field' => 'end_date'],
						'export_tickets_fields7'  => ['field' => 'email'],
						'export_tickets_fields8'  => ['field' => 'telephone'],
						'export_tickets_fields9'  => ['field' => 'country'],
						'export_tickets_fields10' => ['field' => 'province'],
						'export_tickets_fields11' => ['field' => 'city'],
						'export_tickets_fields12' => ['field' => 'zip'],
						'export_tickets_fields13' => ['field' => 'street'],
						'export_tickets_fields14' => ['field' => 'number'],
						'export_tickets_fields15' => ['field' => 'price'],
						'export_tickets_fields16' => ['field' => 'user_id'],
						'export_tickets_fields17' => ['field' => 'created'],
						'export_tickets_fields18' => ['field' => 'type'],
						'export_tickets_fields19' => ['field' => 'event_calid'],
						'export_tickets_fields20' => ['field' => 'timezone']
					],
					'export_locations_fields' => [
						'export_locations_fields0'  => ['field' => 'id'],
						'export_locations_fields1'  => ['field' => 'title'],
						'export_locations_fields2'  => ['field' => 'alias'],
						'export_locations_fields3'  => ['field' => 'country'],
						'export_locations_fields4'  => ['field' => 'province'],
						'export_locations_fields5'  => ['field' => 'city'],
						'export_locations_fields6'  => ['field' => 'zip'],
						'export_locations_fields7'  => ['field' => 'street'],
						'export_locations_fields8'  => ['field' => 'number'],
						'export_locations_fields9'  => ['field' => 'rooms'],
						'export_locations_fields10' => ['field' => 'latitude'],
						'export_locations_fields11' => ['field' => 'longitude'],
						'export_locations_fields12' => ['field' => 'url'],
						'export_locations_fields13' => ['field' => 'description'],
						'export_locations_fields14' => ['field' => 'color'],
						'export_locations_fields15' => ['field' => 'state'],
						'export_locations_fields16' => ['field' => 'language'],
						'export_locations_fields17' => ['field' => 'created'],
						'export_locations_fields18' => ['field' => 'created_by'],
						'export_locations_fields19' => ['field' => 'modified'],
						'export_locations_fields20' => ['field' => 'modified_by'],
						'export_locations_fields21' => ['field' => 'xreference']
					]
				];

				$params = json_decode((string)$plugin->params);
				if (!empty($params->export_events_fields_hide)) {
					foreach ($data['export_events_fields'] as $key => $field) {
						if (\in_array($field['field'], $params->export_events_fields_hide)) {
							unset($data['export_events_fields'][$key]);
						}
					}
				}

				if (!empty($params->export_bookings_fields_hide)) {
					foreach ($data['export_bookings_fields'] as $key => $field) {
						if (\in_array($field['field'], $params->export_bookings_fields_hide)) {
							unset($data['export_bookings_fields'][$key]);
						}
					}
				}

				if (!empty($params->export_tickets_fields_hide)) {
					foreach ($data['export_tickets_fields'] as $key => $field) {
						if (\in_array($field['field'], $params->export_tickets_fields_hide)) {
							unset($data['export_tickets_fields'][$key]);
						}
					}
				}

				$params->export_events_fields    = $data['export_events_fields'];
				$params->export_bookings_fields  = $data['export_bookings_fields'];
				$params->export_tickets_fields   = $data['export_tickets_fields'];
				$params->export_locations_fields = $data['export_locations_fields'];

				$db->setQuery('update #__extensions set params = ' . $db->quote(json_encode($params) ?: '{}') . ' where extension_id = ' . $plugin->extension_id);
				$db->execute();
			}

			$params = ComponentHelper::getParams('com_dpcalendar');
			if (!$params->get('bookingsys_currencies')) {
				$params->set('bookingsys_currencies', ['currencies0' => [
					'currency'            => $params->get('currency', 'EUR'),
					'symbol'              => $params->get('currency_symbol', '€'),
					'separator'           => $params->get('currency_separator', '.'),
					'thousands_separator' => $params->get('currency_thousands_separator', "'")
				]]);
			}

			$db->setQuery('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"')->execute();

			$fields = $db->getTableColumns('#__dpcalendar_events');
			if (!\array_key_exists('events_discount', $fields)) {
				$db->setQuery('ALTER TABLE `#__dpcalendar_events` ADD `events_discount` TEXT NULL AFTER `user_discount`')->execute();
			}
			if (!\array_key_exists('tickets_discount', $fields)) {
				$db->setQuery('ALTER TABLE `#__dpcalendar_events` ADD `tickets_discount` TEXT NULL AFTER `events_discount`')->execute();
			}
			if (!\array_key_exists('earlybird_discount', $fields)) {
				$db->setQuery('ALTER TABLE `#__dpcalendar_events` CHANGE `earlybird` `earlybird_discount` TEXT NULL DEFAULT NULL')->execute();
			}
			if (!\array_key_exists('prices', $fields)) {
				$db->setQuery('ALTER TABLE `#__dpcalendar_events` CHANGE `price` `prices` TEXT NULL DEFAULT NULL')->execute();
			}

			$fields = $db->getTableColumns('#__dpcalendar_bookings');
			if (!\array_key_exists('events_discount', $fields)) {
				$db->setQuery('ALTER TABLE `#__dpcalendar_bookings` ADD `events_discount` DECIMAL(10, 5) DEFAULT NULL AFTER `coupon_rate`')->execute();
			}
			if (!\array_key_exists('events_discount', $fields)) {
				$db->setQuery('ALTER TABLE `#__dpcalendar_bookings` ADD `tickets_discount` DECIMAL(10, 5) DEFAULT NULL AFTER `events_discount`')->execute();
			}

			$db->setQuery("update #__dpcalendar_events set prices = null where prices = '' or prices = '{}' or prices = '[]' or prices like '%\"value\":[]%'")->execute();
			foreach ($db->setQuery('select prices from #__dpcalendar_events where prices like \'%"value":["%\' group by prices')->loadAssocList() as $event) {
				$old = json_decode((string)$event['prices']);
				if (empty($old->value)) {
					continue;
				}

				$new = [];
				foreach ($old->value as $index => $value) {
					$new['prices' . $index] = [
						'value'       => $value,
						'label'       => $old->label[$index] ?? '',
						'currency'    => $params->get('currency', 'EUR'),
						'description' => $old->description[$index] ?? ''
					];
				}

				$new = json_encode($new);
				$db->setQuery('update #__dpcalendar_events set prices = ' . ($new ? $db->quote($new) : 'null') . ' where prices = ' . $db->quote($event['prices']))->execute();
			}

			$db->setQuery("update #__dpcalendar_events set booking_options = null where booking_options = '' or booking_options = '{}' or booking_options = '[]'")->execute();
			foreach ($db->setQuery('select booking_options from #__dpcalendar_events where booking_options is not null group by booking_options')->loadAssocList() as $event) {
				$old = json_decode((string)$event['booking_options']);
				$new = [];
				foreach ($old as $index => $option) {
					$option->currency = $params->get('currency', 'EUR');
					$option->value    = $option->price ?? $option->value;
					unset($option->price);

					$new[$index] = $option;
				}

				$new = json_encode($new);
				$db->setQuery('update #__dpcalendar_events set booking_options = ' . ($new ? $db->quote($new) : 'null') . ' where booking_options = ' . $db->quote($event['booking_options']))->execute();
			}

			$db->setQuery("update #__dpcalendar_events set earlybird_discount = null where earlybird_discount = '' or earlybird_discount = '{}' or earlybird_discount = '[]' or earlybird_discount like '%\"value\":[]%'")->execute();
			foreach ($db->setQuery('select earlybird_discount from #__dpcalendar_events where earlybird_discount like \'%"value":["%\' group by earlybird_discount')->loadAssocList() as $event) {
				$old = json_decode((string)$event['earlybird_discount']);
				if (empty($old->value)) {
					continue;
				}

				$new = [];
				foreach ($old->value as $index => $value) {
					$new['earlybird_discount' . $index] = [
						'value'       => $value,
						'type'        => $old->type[$index] ?? '',
						'date'        => $old->date[$index] ?? '',
						'label'       => $old->label[$index] ?? '',
						'description' => $old->description[$index] ?? ''
					];
				}

				$new = json_encode($new);
				$db->setQuery('update #__dpcalendar_events set earlybird_discount = ' . ($new ? $db->quote($new) : 'null') . ' where earlybird_discount = ' . $db->quote($event['earlybird_discount']))->execute();
			}

			$db->setQuery("update #__dpcalendar_events set user_discount = null where user_discount = '' or user_discount = '{}' or user_discount = '[]' or user_discount like '%\"value\":[]%'")->execute();
			foreach ($db->setQuery('select user_discount from #__dpcalendar_events where user_discount like \'%"value":["%\' group by user_discount')->loadAssocList() as $event) {
				$old = json_decode((string)$event['user_discount']);
				if (empty($old->value)) {
					continue;
				}

				$new = [];
				foreach ($old->value as $index => $value) {
					$new['user_discount' . $index] = [
						'value'       => $value,
						'type'        => $old->type[$index] ?? '',
						'groups'      => $old->discount_groups[$index] ?? '',
						'label'       => $old->label[$index] ?? '',
						'description' => $old->description[$index] ?? ''
					];
				}

				$new = json_encode($new);
				$db->setQuery('update #__dpcalendar_events set user_discount = ' . ($new ? $db->quote($new) : 'null') . ' where user_discount = ' . $db->quote($event['user_discount']))->execute();
			}
		}

		if (version_compare($version, '10.3.0') == -1) {
			$db->setQuery("select * from #__extensions where name = 'plg_dpcalendar_csv' OR name = 'plg_dpcalendar_spreadsheet'");
			foreach ($db->loadObjectList() as $plugin) {
				$params = json_decode((string)$plugin->params);
				if (!empty($params->export_configurations)) {
					continue;
				}

				$params->export_configurations = ['export_configurations0' => [
					'title'            => 'PLG_DPCALENDAR_' . strtoupper((string)$plugin->element) . '_FIELD_EXPORT_TITLE_DEFAULT',
					'value_type'       => $params->export_value_type,
					'strip_html'       => $params->export_strip_html,
					'separator'        => $params->export_separator ?? '',
					'events_fields'    => $params->export_events_fields,
					'bookings_fields'  => $params->export_bookings_fields,
					'tickets_fields'   => $params->export_tickets_fields,
					'locations_fields' => $params->export_locations_fields
				]];

				unset(
					$params->export_value_type,
					$params->export_strip_html,
					$params->export_separator,
					$params->export_events_fields,
					$params->export_bookings_fields,
					$params->export_tickets_fields,
					$params->export_locations_fields
				);

				$db->setQuery('update #__extensions set params = ' . $db->quote(json_encode($params) ?: '{}') . ' where extension_id = ' . $plugin->extension_id);
				$db->execute();
			}

			if (!\array_key_exists('first_name', $db->getTableColumns('#__dpcalendar_bookings'))) {
				$this->run('ALTER TABLE `#__dpcalendar_bookings` ADD `first_name` VARCHAR(255) NULL AFTER `longitude`');
			}

			$db->setQuery("select * from #__dpcalendar_bookings where name like '% %' and first_name is null");
			foreach ($db->loadObjectList() as $booking) {
				[$first_name, $name] = explode(' ', (string)$booking->name, 2);
				$this->run('update #__dpcalendar_bookings set first_name = ' . $db->quote($first_name) . ', name = ' . $db->quote($name) . ' where id = ' . $booking->id);
			}

			if (!\array_key_exists('first_name', $db->getTableColumns('#__dpcalendar_tickets'))) {
				$this->run('ALTER TABLE `#__dpcalendar_tickets` ADD `first_name` VARCHAR(255) NULL AFTER `email`');
			}

			$db->setQuery("select * from #__dpcalendar_tickets where name like '% %' and first_name is null");
			foreach ($db->loadObjectList() as $ticket) {
				[$first_name, $name] = explode(' ', (string)$ticket->name, 2);
				$this->run('update #__dpcalendar_tickets set first_name = ' . $db->quote($first_name) . ', name = ' . $db->quote($name) . ' where id = ' . $ticket->id);
			}

			if ($id = ComponentHelper::getParams('com_dpcalendar')->get('downloadid')) {
				$this->run('update #__update_sites set extra_query = ' . $db->quote('dlid=' . $id) . " where name = 'DPCalendar Core'");
			}

			if (!\array_key_exists('payment_provider_fee', $db->getTableColumns('#__dpcalendar_bookings'))) {
				$this->run('ALTER TABLE `#__dpcalendar_bookings` ADD `payment_provider_fee` DECIMAL(10,5) NULL DEFAULT NULL');
			}
		}
	}

	public function preflight($type, $parent): bool
	{
		if (!parent::preflight($type, $parent)) {
			return false;
		}

		// Check for the minimum Joomla 5 version before continuing
		if (version_compare(JVERSION, '5.0.0', '>=') && version_compare(JVERSION, '5.1.0', '<')) {
			Log::add(Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', '5.1.0'), Log::WARNING, 'jerror');

			return false;
		}

		$app = Factory::getApplication();

		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;
		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = $manifest instanceof SimpleXMLElement ? (string)$manifest->version : '';
		}

		if ($version !== null && $version !== '' && $version !== '0' && $version !== 'DP_DEPLOY_VERSION' && version_compare($version, '9.0.0') < 0) {
			$app->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 9.0.0 running. Please install the latest release from version 9 first.',
				'error'
			);

			if ($app instanceof CMSWebApplicationInterface) {
				$app->redirect('index.php?option=com_installer&view=install');
			}

			return false;
		}

		return true;
	}

	public function postflight(string $type, InstallerAdapter $parent): void
	{
		if ($parent->getElement() != 'com_dpcalendar') {
			return;
		}

		if (is_dir(JPATH_ADMINISTRATOR . '/components/com_falang/contentelements')) {
			File::copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/config/falang/dpcalendar_events.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_events.xml'
			);
			File::copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/config/falang/dpcalendar_locations.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_locations.xml'
			);
		}

		if ($type === 'install' || $type === 'discover_install') {
			// Create default calendar
			$category              = Factory::getApplication()->bootComponent('categories')->getMVCFactory()->createTable('Category', 'Administrator');
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

			$this->run('update #__extensions set params = \'{"providers":{"providers0":{"id":1,"title":"Default","description":"PLG_DPCALENDARPAY_MANUAL_PAY_BUTTON_DESC","booking_state": 4,"payment_statement":"PLG_DPCALENDARPAY_MANUAL_PAYMENT_STATEMENT_TEXT"}}}\' where name like \'plg_dpcalendarpay_manual\'');
		}
	}

	private function run(string $query): void
	{
		try {
			$db = $this->getDatabase();
			$db->setQuery($query);
			$db->execute();
		} catch (Exception $exception) {
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'error');
		}
	}
}
