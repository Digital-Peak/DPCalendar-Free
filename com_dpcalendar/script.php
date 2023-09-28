<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

JLoader::register('DPCalendarHelper', __DIR__ . '/admin/helpers/dpcalendar.php');

class Com_DPCalendarInstallerScript extends InstallerScript
{
	protected $minimumPhp      = '7.4.0';
	protected $minimumJoomla   = '3.10.5';
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

		$db = Factory::getDbo();

		if (version_compare($version, '7.0.0') == -1) {
			// Defaulting some params which have changed
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set('sef_advanced', 0);
			$params->set('map_provider', 'google');

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');

			// Disable template overrides
			$rootPath = JPATH_SITE . '/templates';
			foreach (Folder::folders($rootPath, '.', true, true) as $path) {
				if (strpos($path, 'dpcalendar') === false || strpos($path, '-v6') !== false || !file_exists($path)) {
					continue;
				}
				Folder::move($path, $path . '-v6');
				Factory::getApplication()->enqueueMessage(
					'We have disabled the template overrides in ' . $path . '. They do not work with version 7 anymore!',
					'warning'
				);
			}
		}

		if (version_compare($version, '7.2.7') == -1) {
			$this->run('alter table `#__dpcalendar_events` CHANGE `plugintype` `plugintype` text');
		}

		if (version_compare($version, '7.2.7') == -1) {
			// Defaulting some params which have changed
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set('list_filter_featured', $params->get('list_filter_featured', '2') == '2' ? 0 : 1);

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');

			// Map was never shown, so disable it
			$this->run('update #__modules set params = replace(params, \'"show_map":"1"\', \'"show_map":"0"\') where `module` like "mod_dpcalendar_mini"');
			$this->run('update #__modules set params = replace(params, \'"show_map":"2"\', \'"show_map":"0"\') where `module` like "mod_dpcalendar_mini"');
		}

		if (version_compare($version, '7.3.4') == -1) {
			// Defaulting some params which have changed
			$params = ComponentHelper::getParams('com_dpcalendar');
			$params->set('description_length', $params->get('description_length', 100));

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');

			$this->run('update #__modules set params = replace(params, \'"description_length":""\', \'"description_length":"100"\') where `module` like "mod_dpcalendar_mini"');
		}

		if (version_compare($version, '7.4.0') == -1) {
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
			foreach (Folder::folders($rootPath, '.', true, true) as $path) {
				if (strpos($path, 'dpcalendar') === false || strpos($path, '-v7') !== false || !file_exists($path)) {
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
			if (version_compare(4, JVERSION, '>')) {
				// Force standard routing on Joomla 4
				$params = ComponentHelper::getParams('com_dpcalendar');
				$params->set('sef_advanced', 1);

				$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
			}
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
					$params->get(
						'map_api_openstreetmap_geocode_url',
						'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&q={address}'
					)
				)
			);

			$this->run('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"');
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
		if (!empty($version) && $version != 'DP_DEPLOY_VERSION' && version_compare($version, '7.0.0') < 0) {
			Factory::getApplication()->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. For this version is no automatic update available anymore, you need to have at least version 7.0.0 running. Please install the latest release from version 7 first.',
				'error'
			);
			Factory::getApplication()->redirect('index.php?option=com_installer&view=install');

			return false;
		}
	}

	public function postflight($type, $parent)
	{
		if ($parent->getElement() != 'com_dpcalendar') {
			return;
		}

		if (Folder::exists(JPATH_ADMINISTRATOR . '/components/com_falang/contentelements')) {
			File::copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/libraries/falang/dpcalendar_events.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_events.xml'
			);
			File::copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/libraries/falang/dpcalendar_locations.xml',
				JPATH_ADMINISTRATOR . '/components/com_falang/contentelements/dpcalendar_locations.xml'
			);
		}

		if ($type == 'install' || $type == 'discover_install') {
			// Create default calendar
			Table::addIncludePath(JPATH_LIBRARIES . '/joomla/database/table');
			$category              = Table::getInstance('Category');
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
