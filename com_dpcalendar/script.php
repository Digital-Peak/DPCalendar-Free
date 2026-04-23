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

	protected $minimumPhp = '8.1.0';

	protected $minimumJoomla = '4.4.4';

	protected $allowDowngrades = true;

	public function update(): void
	{
		$path    = JPATH_ADMINISTRATOR . '/components/com_dpcalendar/dpcalendar.xml';
		$version = null;

		if (file_exists($path)) {
			$manifest = simplexml_load_file($path);
			$version  = $manifest instanceof SimpleXMLElement ? (string)$manifest->version : '';
		}

		if (\in_array($version, [null, '', '0', 'DP_DEPLOY_VERSION'], true)) {
			return;
		}

		$db = $this->getDatabase();

		if (version_compare($version, '10.8.0', '<')) {
			$db->setQuery("SELECT id, raw_data FROM #__dpcalendar_bookings WHERE payment_provider LIKE 'paypal%'");
			foreach ($db->loadObjectList() as $booking) {
				$data = json_decode((string)$booking->raw_data);
				$id   = $data?->purchase_units[0]?->payments?->captures[0]->id ?? null;
				if (!$id) {
					continue;
				}

				$db->setQuery('UPDATE #__dpcalendar_bookings SET transaction_id = ' . $db->quote($id) . ' WHERE id = ' . (int)$booking->id);
				$db->execute();
			}
		}

		if (version_compare($version, '10.9.0', '<')) {
			$db->setQuery(
				"INSERT INTO #__mail_templates (`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`) VALUES
('com_dpcalendar.event.create', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_CREATE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_CREATE_BODY', '', ''),
('com_dpcalendar.event.update', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_UPDATE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_UPDATE_BODY', '', ''),
('com_dpcalendar.event.update.ticket', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_UPDATE_TICKET_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_UPDATE_TICKET_BODY', '', ''),
('com_dpcalendar.event.delete', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_DELETE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_DELETE_BODY', '', ''),
('com_dpcalendar.event.author.update', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_UPDATE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_UPDATE_BODY', '', ''),
('com_dpcalendar.event.author.delete', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_DELETE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_DELETE_BODY', '', ''),
('com_dpcalendar.event.ticketholders', 'com_dpcalendar', '', '{{{subject}}}', '', '{{{body}}}', '', ''),
('com_dpcalendar.booking.create', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_BOOKING_CREATE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_BOOKING_CREATE_BODY', '', ''),
('com_dpcalendar.booking.update', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_BOOKING_UPDATE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_BOOKING_UPDATE_BODY', '', ''),
('com_dpcalendar.booking.delete', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_BOOKING_DELETE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_BOOKING_DELETE_BODY', '', ''),
('com_dpcalendar.booking.invoice', 'com_dpcalendar', '', 'COM_DPCALENDAR_BOOK_NOTIFICATION_SEND_SUBJECT', '', 'COM_DPCALENDAR_BOOK_NOTIFICATION_SEND_BODY', '', ''),
('com_dpcalendar.booking.user.new', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_BODY', '', ''),
('com_dpcalendar.booking.user.pay', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_PAYED_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_PAYED_BODY', '', ''),
('com_dpcalendar.booking.user.wait', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_WAITING_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_WAITING_BODY', '', ''),
('com_dpcalendar.booking.user.invite', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_INVITE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_INVITE_BODY', '', ''),
('com_dpcalendar.ticket.update', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_TICKET_UPDATE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_TICKET_UPDATE_BODY', '', ''),
('com_dpcalendar.ticket.checkin', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_TICKET_CHECKED_IN_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_TICKET_CHECKED_IN_BODY', '', ''),
('com_dpcalendar.ticket.receipt', 'com_dpcalendar', '', 'COM_DPCALENDAR_TICKET_NOTIFICATION_SEND_SUBJECT', '', 'COM_DPCALENDAR_TICKET_NOTIFICATION_SEND_BODY', '', ''),
('com_dpcalendar.location.create', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_LOCATION_CREATE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_LOCATION_CREATE_BODY', '', ''),
('com_dpcalendar.location.update', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_LOCATION_UPDATE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_LOCATION_UPDATE_BODY', '', ''),
('com_dpcalendar.location.delete', 'com_dpcalendar', '', 'COM_DPCALENDAR_NOTIFICATION_LOCATION_DELETE_SUBJECT', '', 'COM_DPCALENDAR_NOTIFICATION_LOCATION_DELETE_BODY', '', '')
ON DUPLICATE KEY UPDATE
`subject` = VALUES(`subject`), `htmlbody` = VALUES(`htmlbody`)"
			)->execute();

			if (file_exists(JPATH_PLUGINS . '/dpcalendarpay/manual/manual.xml')) {
				$db->setQuery(
					"INSERT INTO #__mail_templates (`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`) VALUES
('plg_dpcalendarpay_manual.invoice', 'plg_dpcalendarpay_manual', '', 'PLG_DPCALENDARPAY_MANUAL_INVOICE_SUBJECT_TEXT', '', 'PLG_DPCALENDARPAY_MANUAL_INVOICE_MESSAGE_TEXT', '', '')
ON DUPLICATE KEY UPDATE
`subject` = VALUES(`subject`), `htmlbody` = VALUES(`htmlbody`)"
				)->execute();
			}

			if (file_exists(JPATH_PLUGINS . '/dpcalendarpay/qr/qr.xml')) {
				$db->setQuery(
					"INSERT INTO #__mail_templates (`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`) VALUES
('plg_dpcalendarpay_qr.invoice.imageqr', 'plg_dpcalendarpay_qr', '', 'PLG_DPCALENDARPAY_QR_PAYMENT_PROVIDER_TYPE_IMAGEQR_INVOICE_SUBJECT_TEXT', '', 'PLG_DPCALENDARPAY_QR_PAYMENT_PROVIDER_TYPE_IMAGEQR_INVOICE_MESSAGE_TEXT', '', ''),
('plg_dpcalendarpay_qr.invoice.swissqr', 'plg_dpcalendarpay_qr', '', 'PLG_DPCALENDARPAY_QR_PAYMENT_PROVIDER_TYPE_SWISSQR_INVOICE_SUBJECT_TEXT', '', 'PLG_DPCALENDARPAY_QR_PAYMENT_PROVIDER_TYPE_SWISSQR_INVOICE_MESSAGE_TEXT', '', '')
ON DUPLICATE KEY UPDATE
`subject` = VALUES(`subject`), `htmlbody` = VALUES(`htmlbody`)"
				)->execute();
			}

			if (file_exists(JPATH_PLUGINS . '/task/dpcalendar/dpcalendar.xml')) {
				$db->setQuery(
					"INSERT INTO #__mail_templates (`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`) VALUES
('plg_task_dpcalendar.event.reminder.attendee', 'plg_task_dpcalendar', '', 'PLG_TASK_DPCALENDAR_TASK_EVENT_REMINDERS_ATTENDEE_MAIL_SUBJECT_CONTENT', '', 'PLG_TASK_DPCALENDAR_TASK_EVENT_REMINDERS_ATTENDEE_MAIL_MESSAGE_CONTENT', '', ''),
('plg_task_dpcalendar.event.reminder.author', 'plg_task_dpcalendar', '', 'PLG_TASK_DPCALENDAR_TASK_EVENT_REMINDERS_AUTHOR_MAIL_SUBJECT_CONTENT', '', 'PLG_TASK_DPCALENDAR_TASK_EVENT_REMINDERS_AUTHOR_MAIL_MESSAGE_CONTENT', '', '')
ON DUPLICATE KEY UPDATE
`subject` = VALUES(`subject`), `htmlbody` = VALUES(`htmlbody`)"
				)->execute();
			}

			$files = array_merge(
				Folder::files(JPATH_ADMINISTRATOR . '/language/overrides', '\.ini', true, true),
				Folder::files(JPATH_SITE . '/language/overrides', '\.ini', true, true),
			);

			foreach ($files as $file) {
				$content = file_get_contents($file) ?: '';
				foreach ([
					'COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT_CREATE'                 => 'COM_DPCALENDAR_NOTIFICATION_EVENT_CREATE_SUBJECT',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT_EDIT'                   => 'COM_DPCALENDAR_NOTIFICATION_EVENT_UPDATE_SUBJECT',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_EDIT_BODY'                      => 'COM_DPCALENDAR_NOTIFICATION_EVENT_UPDATE_BODY',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_DELETE_SUBJECT'                 => 'COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT_DELETE',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_EDIT_TICKETS_BODY'              => 'COM_DPCALENDAR_NOTIFICATION_EVENT_UPDATE_TICKET_BODY',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_SUBJECT_EDIT'            => 'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_UPDATE_SUBJECT',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_EDIT_BODY'               => 'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_UPDATE_BODY',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_SUBJECT_DELETE'          => 'COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_DELETE_SUBJECT',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_SUBJECT'                   => 'COM_DPCALENDAR_NOTIFICATION_BOOKING_CREATE_SUBJECT',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_BODY'                      => 'COM_DPCALENDAR_NOTIFICATION_BOOKING_CREATE_BODY',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_TICKET_BODY'                    => 'COM_DPCALENDAR_NOTIFICATION_EVENT_UPDATE_TICKET_BODY',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_TICKET_CHECKED_IN_SUBJECT' => 'COM_DPCALENDAR_NOTIFICATION_TICKET_CHECKED_IN_SUBJECT',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_TICKET_CHECKED_IN_BODY'    => 'COM_DPCALENDAR_NOTIFICATION_TICKET_CHECKED_IN_BODY',
					'COM_DPCALENDAR_NOTIFICATION_LOCATION_SUBJECT_CREATE'              => 'COM_DPCALENDAR_NOTIFICATION_LOCATION_CREATE_SUBJECT'
				] as $key => $replace) {
					$content = str_replace($key, $replace, $content);
				}
				file_put_contents($file, $content);
			}

			$params        = ComponentHelper::getParams('com_dpcalendar');
			$notifications = new stdClass();
			if ($groups = $params->get('notification_groups_create', [])) {
				$notifications->component_notifications0 = (object)['action' => 'com_dpcalendar.event.create', 'groups' => $groups];
				$params->remove('notification_groups_create');
			}
			if ($groups = $params->get('notification_groups_edit', [])) {
				$notifications->component_notifications1 = (object)['action' => 'com_dpcalendar.event.update', 'groups' => $groups, 'author' => $params->get('notification_author', 0)];
				$params->remove('notification_groups_edit');
			}
			if ($groups = $params->get('notification_groups_delete', [])) {
				$notifications->component_notifications2 = (object)['action' => 'com_dpcalendar.event.delete', 'groups' => $groups, 'author' => $params->get('notification_author', 0)];
				$params->remove('notification_groups_delete');
			}
			if ($groups = $params->get('notification_groups_book', [])) {
				$notifications->component_notifications3 = (object)['action' => 'com_dpcalendar.booking.update', 'groups' => $groups];
				$params->remove('notification_groups_book');
			}
			if ($groups = $params->get('notification_groups_location_create', [])) {
				$notifications->component_notifications4 = (object)['action' => 'com_dpcalendar.location.create', 'groups' => $groups];
				$params->remove('notification_groups_location_create');
			}

			if (!$params->get('component_notifications')) {
				$params->set('component_notifications', $notifications);
				$db->setQuery('update #__extensions set params = ' . $db->quote((string)$params) . ' where element = "com_dpcalendar"')->execute();
			}

			$db->setQuery("SELECT id, raw_data FROM #__dpcalendar_bookings WHERE payment_provider LIKE 'stripe%'");
			foreach ($db->loadObjectList() as $booking) {
				$data = json_decode((string)$booking->raw_data);
				$id   = $data?->charges?->data[0]->balance_transaction ?? null;
				if (!$id) {
					$id = $data?->transactionData?->charges?->data[0]->balance_transaction ?? null;
				}

				if (!$id) {
					$id = $data?->transactionData->balance_transaction ?? null;
				}

				if (!$id) {
					continue;
				}

				$db->setQuery('UPDATE #__dpcalendar_bookings SET transaction_id = ' . $db->quote($id) . ' WHERE id = ' . (int)$booking->id);
				$db->execute();
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

		if (!\in_array($version, [null, '', '0', 'DP_DEPLOY_VERSION'], true) && version_compare($version, '10.6.0', '<')) {
			$app->enqueueMessage(
				'You have DPCalendar version ' . $version . ' installed. Please install version 10.6.0 first before you upgrade to this package.',
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
