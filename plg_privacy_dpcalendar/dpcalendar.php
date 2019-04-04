<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
	return;
}

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');

class PlgPrivacyDPCalendar extends PrivacyPlugin
{
	public function onPrivacyExportRequest(PrivacyTableRequest $request, JUser $user = null)
	{
		if (!$user) {
			return array();
		}

		$domains   = array();

		// Event data
		$domain    = $this->createDomain('user_dpcalendar_event', 'joomla_user_dpcalendar_event_data');
		$domains[] = $domain;

		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__dpcalendar_events'))
			->where($this->db->quoteName('created_by') . ' = ' . (int)$user->id)
			->order($this->db->quoteName('start_date') . ' ASC');

		$items = $this->db->setQuery($query)->loadObjectList();

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

		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__dpcalendar_bookings'))
			->where($this->db->quoteName('user_id') . ' = ' . (int)$user->id)
			->order($this->db->quoteName('book_date') . ' ASC');

		$items = $this->db->setQuery($query)->loadObjectList();

		foreach ($items as $item) {
			$domain->addItem($this->createItemFromArray((array)$item));
		}

		$domains[] = $this->createCustomFieldsDomain('com_dpcalendar.booking', $items);

		// Tickets data
		$domain    = $this->createDomain('user_dpcalendar_ticket', 'joomla_user_dpcalendar_ticket_data');
		$domains[] = $domain;

		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__dpcalendar_tickets'))
			->where($this->db->quoteName('user_id') . ' = ' . (int)$user->id)
			->order($this->db->quoteName('created') . ' ASC');

		$items = $this->db->setQuery($query)->loadObjectList();

		foreach ($items as $item) {
			$domain->addItem($this->createItemFromArray((array)$item));
		}

		$domains[] = $this->createCustomFieldsDomain('com_dpcalendar.ticket', $items);

		// Locations data
		$domain    = $this->createDomain('user_dpcalendar_location', 'joomla_user_dpcalendar_location_data');
		$domains[] = $domain;

		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__dpcalendar_locations'))
			->where($this->db->quoteName('created_by') . ' = ' . (int)$user->id)
			->order($this->db->quoteName('created') . ' ASC');

		$items = $this->db->setQuery($query)->loadObjectList();

		foreach ($items as $item) {
			$domain->addItem($this->createItemFromArray((array)$item));
		}

		$domains[] = $this->createCustomFieldsDomain('com_dpcalendar.location', $items);

		return $domains;
	}
}
