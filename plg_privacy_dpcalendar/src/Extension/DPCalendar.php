<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Privacy\DPCalendar\Extension;

defined('_JEXEC') or die();

use Joomla\CMS\User\User;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Table\RequestTable;
use Joomla\Database\DatabaseAwareTrait;

class DPCalendar extends PrivacyPlugin
{
	use DatabaseAwareTrait;

	/**
	 * @return mixed[]
	 */
	public function onPrivacyExportRequest(RequestTable $request, ?User $user = null): array
	{
		if (!$user instanceof User) {
			return [];
		}

		$domains = [];
		$db      = $this->getDatabase();

		// Event data
		$domain    = $this->createDomain('user_dpcalendar_event', 'joomla_user_dpcalendar_event_data');
		$domains[] = $domain;

		$query = $db->getQuery(true)
			->select('*')
			->from('#__dpcalendar_events')
			->where('created_by = ' . (int)$user->id)
			->order('start_date ASC');

		$items = $db->setQuery($query)->loadObjectList();

		foreach ($items as $item) {
			$data                = (array)$item;
			$data['calendar_id'] = $data['catid'];
			unset($data['catid']);
			$domain->addItem($this->createItemFromArray($data));
		}

		$domains[] = $this->createCustomFieldsDomain('com_dpcalendar.event', $items);

		// Booking data
		$domain    = $this->createDomain('user_dpcalendar_booking', 'joomla_user_dpcalendar_booking_data');
		$domains[] = $domain;

		$query = $db->getQuery(true)
			->select('*')
			->from('#__dpcalendar_bookings')
			->where('user_id = ' . (int)$user->id)
			->order('book_date ASC');

		$items = $db->setQuery($query)->loadObjectList();

		foreach ($items as $item) {
			$domain->addItem($this->createItemFromArray((array)$item));
		}

		$domains[] = $this->createCustomFieldsDomain('com_dpcalendar.booking', $items);

		// Tickets data
		$domain    = $this->createDomain('user_dpcalendar_ticket', 'joomla_user_dpcalendar_ticket_data');
		$domains[] = $domain;

		$query = $db->getQuery(true)
			->select('*')
			->from('#__dpcalendar_tickets')
			->where('user_id = ' . (int)$user->id)
			->order('created ASC');

		$items = $db->setQuery($query)->loadObjectList();

		foreach ($items as $item) {
			$domain->addItem($this->createItemFromArray((array)$item));
		}

		$domains[] = $this->createCustomFieldsDomain('com_dpcalendar.ticket', $items);

		// Locations data
		$domain    = $this->createDomain('user_dpcalendar_location', 'joomla_user_dpcalendar_location_data');
		$domains[] = $domain;

		$query = $db->getQuery(true)
			->select('*')
			->from('#__dpcalendar_locations')
			->where('created_by = ' . (int)$user->id)
			->order('created ASC');

		$items = $db->setQuery($query)->loadObjectList();

		foreach ($items as $item) {
			$domain->addItem($this->createItemFromArray((array)$item));
		}

		$domains[] = $this->createCustomFieldsDomain('com_dpcalendar.location', $items);

		return $domains;
	}
}
