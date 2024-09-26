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
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;

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

		if (version_compare($version, '8.2.0') == -1) {
			// Defaulting some params which have changed
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set('booking_registration', $params->get('booking_show_registration', 100));

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '8.0.0') == -1) {
			// Defaulting some params which have changed
			$params = ComponentHelper::getParams('com_dpcalendar');
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
				if (\array_key_exists($ticket->booking_id . '-' . $ticket->type, $bookings)) {
					$ticketsToDelete[] = $ticket->id;
					continue;
				}
				$price = 0;
				$types = json_decode((string)$ticket->price);
				if ($types && \array_key_exists($ticket->type, $types->value)) {
					$price = $types->value[$ticket->type];
				}
				$db->setQuery("update #__dpcalendar_tickets set event_id = " . $ticket->original_id . ", price = '" . $price . "' where id = " . $ticket->id);
				$db->execute();

				$bookings[$ticket->booking_id . '-' . $ticket->type] = true;
			}
			if ($ticketsToDelete !== []) {
				$db->setQuery('delete from #__dpcalendar_tickets where id in (' . implode(',', $ticketsToDelete) . ')');
				$db->execute();
			}

			$db->setQuery("select * from #__extensions where folder like 'dpcalendarpay'");
			foreach ($db->loadObjectList() as $plugin) {
				if (!$plugin->params || $plugin->params == '[]' || $plugin->params == '{}' || str_contains((string)$plugin->params, 'providers0')) {
					continue;
				}

				$params = '{"providers":{"providers0":{"id":1,"title":"Default","description":"",';
				$params .= trim((string)$plugin->params, '{}');
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
			foreach (Folder::folders($rootPath, '.', true, true) as $path) {
				if (!str_contains((string)$path, 'dpcalendar') || str_contains((string)$path, '-v7') || !file_exists($path)) {
					continue;
				}

				Folder::move($path, $path . '-v7');
				Factory::getApplication()->enqueueMessage(
					'We have disabled the template overrides in ' . $path . '. They do not work with version 8 anymore!',
					'warning'
				);
			}
		}

		if (version_compare($version, '8.2.0') == -1) {
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set('calendar_filter_author', $params->get('show_my_only_calendar', '0') == '1' ? '-1' : '0');
			$params->set('list_filter_author', $params->get('show_my_only_list', '0') == '1' ? '-1' : '0');
			$params->set('map_filter_author', $params->get('map_view_show_my_only', '0') == '1' ? '-1' : '0');
			$params->set('locations_filter_author', $params->get('locations_show_my_only', '0') == '1' ? '-1' : '0');

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '8.6.0') == -1) {
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set('booking_include_receipt', $params->get('booking_include_invoice', '1'));
			$params->set('pdf_address', $params->get('invoice_address', ''));
			$params->set('pdf_logo', $params->get('invoice_logo', ''));
			$params->set('pdf_header', $params->get('invoice_header', ''));
			$params->set('pdf_content_top', $params->get('invoice_content_top', ''));
			$params->set('pdf_content_bottom', $params->get('invoice_content_bottom', ''));
			$params->set('receipt_include_tickets', $params->get('invoice_include_tickets', ''));

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '8.14.0') == -1) {
			// Force standard routing on Joomla 4
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set('sef_advanced', 1);
			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '8.7.0') == -1) {
			foreach ($db->setQuery("select * from #__extensions where folder = 'dpcalendarpay';")->loadObjectList() as $plugin) {
				if ($plugin->params === '[]') {
					continue;
				}

				$params    = new Registry($plugin->params);
				$providers = $params->get('providers');
				if (!$providers) {
					continue;
				}

				/** @var stdClass $provider */
				foreach ($providers as $provider) {
					if (isset($provider->state) && !isset($provider->booking_state)) {
						$provider->booking_state = $provider->state;
						unset($provider->state);
					}

					if (!isset($provider->state)) {
						$provider->state = 1;
					}
				}

				$params->set('providers', $providers);

				$this->run('update #__extensions set params = ' . $db->quote($params->toString()) . ' where extension_id = ' . $plugin->extension_id);
			}
		}

		if (version_compare($version, '8.14.2') == -1) {
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set(
				'map_api_openstreetmap_geocode_url',
				str_replace(
					'/search/?',
					'/search?',
					(string)$params->get(
						'map_api_openstreetmap_geocode_url',
						'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&q={address}'
					)
				)
			);

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '8.15.0') == -1) {
			$params = ComponentHelper::getParams('com_dpcalendar');
			$uri    = Uri::getInstance($params->get(
				'map_api_openstreetmap_geocode_url',
				'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&q={address}'
			));
			$params->set('map_api_openstreetmap_geocode_url', $uri->toString(['scheme', 'user', 'pass', 'host', 'port']));

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '8.17.0') == -1) {
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set('list_date_start', $params->get('date_start', ''));

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
		}

		if (version_compare($version, '9.3.0') == -1) {
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
						'export_bookings_fields2'  => ['field' => 'name'],
						'export_bookings_fields3'  => ['field' => 'email'],
						'export_bookings_fields4'  => ['field' => 'telephone'],
						'export_bookings_fields5'  => ['field' => 'country'],
						'export_bookings_fields6'  => ['field' => 'province'],
						'export_bookings_fields7'  => ['field' => 'city'],
						'export_bookings_fields8'  => ['field' => 'zip'],
						'export_bookings_fields9'  => ['field' => 'street'],
						'export_bookings_fields10' => ['field' => 'number'],
						'export_bookings_fields11' => ['field' => 'price'],
						'export_bookings_fields12' => ['field' => 'options'],
						'export_bookings_fields13' => ['field' => 'net_amount'],
						'export_bookings_fields14' => ['field' => 'processor'],
						'export_bookings_fields15' => ['field' => 'user_id'],
						'export_bookings_fields16' => ['field' => 'book_date'],
						'export_bookings_fields17' => ['field' => 'event_id'],
						'export_bookings_fields18' => ['field' => 'event_author'],
						'export_bookings_fields19' => ['field' => 'event_calid'],
						'export_bookings_fields20' => ['field' => 'timezone']
					],
					'export_tickets_fields' => [
						'export_tickets_fields0'  => ['field' => 'uid'],
						'export_tickets_fields1'  => ['field' => 'state'],
						'export_tickets_fields2'  => ['field' => 'name'],
						'export_tickets_fields3'  => ['field' => 'event_title'],
						'export_tickets_fields4'  => ['field' => 'start_date'],
						'export_tickets_fields5'  => ['field' => 'end_date'],
						'export_tickets_fields6'  => ['field' => 'email'],
						'export_tickets_fields7'  => ['field' => 'telephone'],
						'export_tickets_fields8'  => ['field' => 'country'],
						'export_tickets_fields9'  => ['field' => 'province'],
						'export_tickets_fields10' => ['field' => 'city'],
						'export_tickets_fields11' => ['field' => 'zip'],
						'export_tickets_fields12' => ['field' => 'street'],
						'export_tickets_fields13' => ['field' => 'number'],
						'export_tickets_fields14' => ['field' => 'price'],
						'export_tickets_fields15' => ['field' => 'user_id'],
						'export_tickets_fields16' => ['field' => 'created'],
						'export_tickets_fields17' => ['field' => 'type'],
						'export_tickets_fields18' => ['field' => 'event_calid'],
						'export_tickets_fields19' => ['field' => 'timezone']
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

				$params = json_decode($plugin->params);
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

				$db->setQuery('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"')->execute();
			}

			$db->setQuery("update #__dpcalendar_events set price = null where price = '' or price = '{}' or price = '[]' or price like '%\"value\":[]%'")->execute();
			foreach ($db->setQuery('select price from #__dpcalendar_events where price like \'%"value":["%\' group by price')->loadAssocList() as $event) {
				$old = json_decode((string)$event['price']);
				if (empty($old->value)) {
					continue;
				}

				$new = [];
				foreach ($old->value as $index => $value) {
					$new['price' . $index] = [
						'value'       => $value,
						'label'       => $old->label[$index] ?? '',
						'currency'    => $params->get('currency', 'EUR'),
						'description' => $old->description[$index] ?? ''
					];
				}

				$new = json_encode($new);
				$db->setQuery('update #__dpcalendar_events set price = ' . ($new ? $db->quote($new) : 'null') . ' where price = ' . $db->quote($event['price']))->execute();
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

			$db->setQuery("update #__dpcalendar_events set earlybird = null where earlybird = '' or earlybird = '{}' or earlybird = '[]' or earlybird like '%\"value\":[]%'")->execute();
			foreach ($db->setQuery('select earlybird from #__dpcalendar_events where earlybird like \'%"value":["%\' group by earlybird')->loadAssocList() as $event) {
				$old = json_decode((string)$event['earlybird']);
				if (empty($old->value)) {
					continue;
				}

				$new = [];
				foreach ($old->value as $index => $value) {
					$new['earlybird' . $index] = [
						'value'       => $value,
						'type'        => $old->type[$index] ?? '',
						'date'        => $old->date[$index] ?? '',
						'label'       => $old->label[$index] ?? '',
						'description' => $old->description[$index] ?? ''
					];
				}

				$new = json_encode($new);
				$db->setQuery('update #__dpcalendar_events set earlybird = ' . ($new ? $db->quote($new) : 'null') . ' where earlybird = ' . $db->quote($event['earlybird']))->execute();
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

			foreach ([
				'/com_dpcalendar/tmpl/bookingform',
				'/com_dpcalendar/tmpl/booking/confirm_tickets.php',
				'/com_dpcalendar/tmpl/booking/default_tickets.php',
				'/com_dpcalendar/tmpl/event/default.php',
				'/com_dpcalendar/tmpl/event/default_bookings_options.php',
				'/com_dpcalendar/tmpl/event/default_bookings_prices.php',
				'/mod_dpcalendar_upcoming/tmpl/default.php',
				'/mod_dpcalendar_upcoming/tmpl/horizontal.php',
				'/mod_dpcalendar_upcoming/tmpl/icon.php',
				'/mod_dpcalendar_upcoming/tmpl/panel.php',
				'/mod_dpcalendar_upcoming/tmpl/simple.php',
				'/mod_dpcalendar_upcoming/tmpl/timeline.php',
				'/plg_dpcalendarpay_manual/tmpl/invoice.php',
				'/plg_dpcalendarpay_qr/tmpl/invoice.php',
				'/com_dpcalendar/layouts/block/timezone.php',
				'/com_dpcalendar/layouts/booking/details.php',
				'/com_dpcalendar/layouts/booking/receipt.php',
				'/com_dpcalendar/layouts/format/price.php',
				'/com_dpcalendar/layouts/schema/offer.php',
				'/com_dpcalendar/layouts/ticket/details.php'] as $file) {
				$path = str_replace(['/tmpl', '/layouts'], ['', ''], $file);

				foreach (Folder::folders(JPATH_SITE . '/templates', '.', false, true) as $template) {
					if (str_contains($file, 'tmpl') && is_dir($template . '/html/' . $path)) {
						Folder::delete($template . '/html/' . $path);
						continue;
					}

					if (str_contains($file, 'tmpl') && file_exists($template . '/html/' . $path)) {
						rename($template . '/html/' . $path, $template . '/html/' . $path . '-old.php');
						Factory::getApplication()->enqueueMessage(
							'We have disabled the template override in ' . $template . '/html' . $path . '. They do not work with version 9.3.0 anymore!',
							'warning'
						);
					}
					if (str_contains($file, 'layouts') && file_exists($template . '/html/layouts/' . $path)) {
						rename($template . '/html/layouts/' . $path, $template . '/html/layouts/' . $path . '-old.php');
						Factory::getApplication()->enqueueMessage(
							'We have disabled the layout override in ' . $template . '/html/layouts' . $path . '. They do not work with version 9.3.0 anymore!',
							'warning'
						);
					}
				}
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

		if ($version !== null && $version !== '' && $version !== '0' && $version !== 'DP_DEPLOY_VERSION' && version_compare($version, '8.0.0') < 0) {
			$app->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 8.0.0 running. Please install the latest release from version 8 first.',
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
