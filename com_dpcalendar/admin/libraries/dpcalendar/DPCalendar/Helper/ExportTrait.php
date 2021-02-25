<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

trait ExportTrait
{
	public function getEventData()
	{
		$fields                   = [];
		$fields['id']             = Text::_('JGRID_HEADING_ID');
		$fields['title']          = Text::_('JGLOBAL_TITLE');
		$fields['calendar']       = Text::_('COM_DPCALENDAR_CALENDAR');
		$fields['color']          = Text::_('COM_DPCALENDAR_FIELD_COLOR_LABEL');
		$fields['url']            = Text::_('COM_DPCALENDAR_FIELD_URL_LABEL');
		$fields['start_date']     = Text::_('COM_DPCALENDAR_FIELD_START_DATE_LABEL');
		$fields['end_date']       = Text::_('COM_DPCALENDAR_FIELD_END_DATE_LABEL');
		$fields['all_day']        = Text::_('COM_DPCALENDAR_FIELD_ALL_DAY_LABEL');
		$fields['rrule']          = Text::_('COM_DPCALENDAR_FIELD_SCHEDULING_RRULE_LABEL');
		$fields['description']    = Text::_('JGLOBAL_DESCRIPTION');
		$fields['locations']      = Text::_('COM_DPCALENDAR_LOCATIONS');
		$fields['alias']          = Text::_('JFIELD_ALIAS_LABEL');
		$fields['featured']       = Text::_('JFEATURED');
		$fields['status']         = Text::_('JSTATUS');
		$fields['access']         = Text::_('JFIELD_ACCESS_LABEL');
		$fields['access_content'] = Text::_('COM_DPCALENDAR_FIELD_ACCESS_CONTENT_LABEL');
		$fields['language']       = Text::_('JFIELD_LANGUAGE_LABEL');
		$fields['created']        = Text::_('JGLOBAL_FIELD_CREATED_LABEL');
		$fields['created_by']     = Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL');
		$fields['modified']       = Text::_('JGLOBAL_FIELD_MODIFIED_LABEL');
		$fields['modified_by']    = Text::_('JGLOBAL_FIELD_MODIFIED_BY_LABEL');
		$fields['uid']            = Text::_('COM_DPCALENDAR_UID');
		$fields['timezone']       = Text::_('COM_DPCALENDAR_TIMEZONE');

		$parser = function ($name, $event) {
			switch ($name) {
				case 'calendar':
					return DPCalendarHelper::getCalendar($event->catid)->title;
				case 'status':
					return Booking::getStatusLabel($event);
				case 'locations':
					if (empty($event->locations)) {
						return '';
					}

					return Location::format($event->locations);
				case 'start_date':
				case 'end_date':
					return DPCalendarHelper::getDate($event->$name)->format($event->all_day ? 'Y-m-d' : 'Y-m-d H:i:s', true);
				case 'created':
				case 'modified':
					if ($event->$name == '0000-00-00 00:00:00') {
						return '';
					}

					return DPCalendarHelper::getDate($event->$name)->format('Y-m-d H:i:s', true);
				case 'timezone':
					return DPCalendarHelper::getDate()->getTimezone()->getName();
				default:
					return $event->$name;
			}
		};

		return $this->getData('adminevent', $fields, $parser);
	}

	public function getBookingsData()
	{
		$fields                 = [];
		$fields['uid']          = Text::_('JGRID_HEADING_ID');
		$fields['status']       = Text::_('JSTATUS');
		$fields['name']         = Text::_('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL');
		$fields['email']        = Text::_('COM_DPCALENDAR_BOOKING_FIELD_EMAIL_LABEL');
		$fields['telephone']    = Text::_('COM_DPCALENDAR_BOOKING_FIELD_TELEPHONE_LABEL');
		$fields['country']      = Text::_('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL');
		$fields['province']     = Text::_('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL');
		$fields['city']         = Text::_('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL');
		$fields['zip']          = Text::_('COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL');
		$fields['street']       = Text::_('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL');
		$fields['number']       = Text::_('COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL');
		$fields['price']        = Text::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL');
		$fields['net_amount']   = Text::_('COM_DPCALENDAR_BOOKING_FIELD_NET_PRICE_LABEL');
		$fields['processor']    = Text::_('COM_DPCALENDAR_BOOKING_FIELD_PAYMENT_PROVIDER_LABEL');
		$fields['user_name']    = Text::_('JGLOBAL_USERNAME');
		$fields['book_date']    = Text::_('JGLOBAL_CREATED');
		$fields['event']        = Text::_('COM_DPCALENDAR_EVENT');
		$fields['event_author'] = Text::_('COM_DPCALENDAR_FIELD_AUTHOR_LABEL');
		$fields['event_calid']  = Text::_('COM_DPCALENDAR_CALENDAR');

		$parser = function ($name, $booking) {
			switch ($name) {
				case 'status':
					return Booking::getStatusLabel($booking);
				case 'book_date':
					return DPCalendarHelper::getDate($booking->$name)->format('c');
				case 'event':
					$events = [];
					foreach ($booking->tickets as $ticket) {
						$events[] = $ticket->event_title;
					}

					return implode(', ', array_unique($events));
				case 'event_author':
					$authors = [];
					foreach ($booking->tickets as $ticket) {
						$authors[] = Factory::getUser($ticket->event_author)->name;
					}

					return implode(', ', array_unique($authors));
				case 'event_calid':
					$calendars = [];
					foreach ($booking->tickets as $ticket) {
						$calendars[] = $ticket->event_calid;
					}

					return implode(', ', array_unique($calendars));
				default:
					return $booking->$name;
			}
		};

		return $this->getData('booking', $fields, $parser);
	}

	public function getTicketsData()
	{
		$fields                = [];
		$fields['uid']         = Text::_('JGRID_HEADING_ID');
		$fields['status']      = Text::_('JSTATUS');
		$fields['name']        = Text::_('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL');
		$fields['event_title'] = Text::_('COM_DPCALENDAR_EVENT');
		$fields['start_date']  = Text::_('COM_DPCALENDAR_FIELD_START_DATE_LABEL');
		$fields['end_date']    = Text::_('COM_DPCALENDAR_FIELD_END_DATE_LABEL');
		$fields['email']       = Text::_('COM_DPCALENDAR_BOOKING_FIELD_EMAIL_LABEL');
		$fields['telephone']   = Text::_('COM_DPCALENDAR_BOOKING_FIELD_TELEPHONE_LABEL');
		$fields['country']     = Text::_('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL');
		$fields['province']    = Text::_('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL');
		$fields['city']        = Text::_('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL');
		$fields['zip']         = Text::_('COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL');
		$fields['street']      = Text::_('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL');
		$fields['number']      = Text::_('COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL');
		$fields['price']       = Text::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL');
		$fields['user_name']   = Text::_('JGLOBAL_USERNAME');
		$fields['created']     = Text::_('JGLOBAL_CREATED');
		$fields['type']        = Text::_('COM_DPCALENDAR_TICKET_FIELD_TYPE_LABEL');
		$fields['event_calid'] = Text::_('COM_DPCALENDAR_CALENDAR');

		$parser = function ($name, $ticket) {
			switch ($name) {
				case 'status':
					return Booking::getStatusLabel($ticket);
				case 'created':
					return DPCalendarHelper::getDate($ticket->$name)->format('c');
				case 'type':
					if (!BaseDatabaseModel::getInstance('Booking', 'DPCalendarModel')->getEvent($ticket->event_id)->price) {
						return '';
					}

					return BaseDatabaseModel::getInstance('Booking', 'DPCalendarModel')->getEvent($ticket->event_id)->price->label[$ticket->type];
				default:
					return $ticket->$name;
			}
		};

		return $this->getData('ticket', $fields, $parser);
	}

	private function getData($name, $fieldsToLabels, $valueParser)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');

		$name     = strtolower($name);
		$realName = str_replace('admin', '', $name);
		$model    = BaseDatabaseModel::getInstance(ucfirst($name) . 's', 'DPCalendarModel', ['ignore_request' => false]);
		$model->setState('list.limit', 1000);
		$items = $model->getItems();

		$data = [];

		$fields = [];
		if ($items) {
			$line = [];
			foreach ($fieldsToLabels as $fieldLabel) {
				$line[] = $fieldLabel;
			}
			$fields = array_merge($fields, \FieldsHelper::getFields('com_dpcalendar.' . $realName));
			foreach ($fields as $field) {
				$line[] = $field->label;
			}

			$data[] = $line;
		}

		foreach ($items as $item) {
			Factory::getApplication()->triggerEvent('onContentPrepare', ['com_dpcalendar.' . $realName, &$item, &$item->params, 0]);
			$line = [];
			foreach ($fieldsToLabels as $fieldName => $fieldLabel) {
				$line[] = $valueParser($fieldName, $item);
			}

			if ($fields) {
				foreach ($fields as $field) {
					if (isset($item->jcfields) && key_exists($field->id, $item->jcfields)) {
						$line[] = html_entity_decode(strip_tags($item->jcfields[$field->id]->value));
					}
				}
			}
			$data[] = $line;
		}

		return $data;
	}
}
