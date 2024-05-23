<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Helper;

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

trait ExportTrait
{
	public function getEventData(array $events = []): array
	{
		$fields = [
			(object)['id' => 'id', 'name' => 'id', 'label' => Text::_('JGRID_HEADING_ID')],
			(object)['id' => 'title', 'name' => 'title', 'label' => Text::_('JGLOBAL_TITLE')],
			(object)['id' => 'calendar', 'name' => 'calendar', 'label' => Text::_('COM_DPCALENDAR_CALENDAR')],
			(object)['id' => 'color', 'name' => 'color', 'label' => Text::_('COM_DPCALENDAR_FIELD_COLOR_LABEL')],
			(object)['id' => 'url', 'name' => 'url', 'label' => Text::_('COM_DPCALENDAR_FIELD_URL_LABEL')],
			(object)['id' => 'start_date', 'name' => 'start_date', 'label' => Text::_('COM_DPCALENDAR_FIELD_START_DATE_LABEL')],
			(object)['id' => 'end_date', 'name' => 'end_date', 'label' => Text::_('COM_DPCALENDAR_FIELD_END_DATE_LABEL')],
			(object)['id' => 'all_day', 'name' => 'all_day', 'label' => Text::_('COM_DPCALENDAR_FIELD_ALL_DAY_LABEL')],
			(object)['id' => 'rrule', 'name' => 'rrule', 'label' => Text::_('COM_DPCALENDAR_FIELD_SCHEDULING_RRULE_LABEL')],
			(object)['id' => 'description', 'name' => 'description', 'label' => Text::_('JGLOBAL_DESCRIPTION')],
			(object)['id' => 'locations', 'name' => 'locations', 'label' => Text::_('COM_DPCALENDAR_LOCATIONS')],
			(object)['id' => 'alias', 'name' => 'alias', 'label' => Text::_('JFIELD_ALIAS_LABEL')],
			(object)['id' => 'featured', 'name' => 'featured', 'label' => Text::_('JFEATURED')],
			(object)['id' => 'status', 'name' => 'status', 'label' => Text::_('JSTATUS')],
			(object)['id' => 'access', 'name' => 'access', 'label' => Text::_('JFIELD_ACCESS_LABEL')],
			(object)['id' => 'access_content', 'name' => 'access_content', 'label' => Text::_('COM_DPCALENDAR_FIELD_ACCESS_CONTENT_LABEL')],
			(object)['id' => 'language', 'name' => 'language', 'label' => Text::_('JFIELD_LANGUAGE_LABEL')],
			(object)['id' => 'created', 'name' => 'created', 'label' => Text::_('JGLOBAL_FIELD_CREATED_LABEL')],
			(object)['id' => 'created_by', 'name' => 'created_by', 'label' => Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL')],
			(object)['id' => 'modified', 'name' => 'modified', 'label' => Text::_('JGLOBAL_FIELD_MODIFIED_LABEL')],
			(object)['id' => 'modified_by', 'name' => 'modified_by', 'label' => Text::_('JGLOBAL_FIELD_MODIFIED_BY_LABEL')],
			(object)['id' => 'uid', 'name' => 'uid', 'label' => Text::_('COM_DPCALENDAR_UID')],
			(object)['id' => 'timezone', 'name' => 'timezone', 'label' => Text::_('COM_DPCALENDAR_TIMEZONE')]
		];

		$parser = function ($name, $event) {
			switch ($name) {
				case 'calendar':
					$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);
					return $calendar instanceof CalendarInterface ? $calendar->getTitle() : $event->catid;
				case 'status':
					return Booking::getStatusLabel($event);
				case 'locations':
					if (empty($event->locations)) {
						return '';
					}

					return $this->getDPCalendar()->getMVCFactory()->createModel('Geo', 'Administrator')->format($event->locations);
				case 'start_date':
				case 'end_date':
					return DPCalendarHelper::getDate($event->$name)->format($event->all_day ? 'Y-m-d' : 'Y-m-d H:i:s', true);
				case 'created':
				case 'modified':
					if (!$event->$name) {
						return '';
					}

					return DPCalendarHelper::getDate($event->$name)->format('Y-m-d H:i:s', true);
				case 'timezone':
					return DPCalendarHelper::getDate()->getTimezone()->getName();
				case 'description':
					return $this->params->get('export_strip_html') ? strip_tags((string)$event->description) : $event->description;
				default:
					return $event->$name ?? '';
			}
		};

		return $this->getData('event', $fields, $parser, $events);
	}

	public function getBookingsData(array $bookings = []): array
	{
		$fields = [
			(object)['id' => 'uid', 'name' => 'uid', 'label' => Text::_('JGRID_HEADING_ID')],
			(object)['id' => 'status', 'name' => 'status', 'label' => Text::_('JSTATUS')],
			(object)['id' => 'name', 'name' => 'name', 'label' => Text::_('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL')],
			(object)['id' => 'email', 'name' => 'email', 'label' => Text::_('COM_DPCALENDAR_BOOKING_FIELD_EMAIL_LABEL')],
			(object)['id' => 'telephone', 'name' => 'telephone', 'label' => Text::_('COM_DPCALENDAR_BOOKING_FIELD_TELEPHONE_LABEL')],
			(object)['id' => 'country', 'name' => 'country_code_value', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL')],
			(object)['id' => 'province', 'name' => 'province', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL')],
			(object)['id' => 'city', 'name' => 'city', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL')],
			(object)['id' => 'zip', 'name' => 'zip', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL')],
			(object)['id' => 'street', 'name' => 'street', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL')],
			(object)['id' => 'number', 'name' => 'number', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL')],
			(object)['id' => 'price', 'name' => 'price', 'label' => Text::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL')],
			(object)['id' => 'net_amount', 'name' => 'net_amount', 'label' => Text::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL')],
			(object)['id' => 'processor', 'name' => 'processor', 'label' => Text::_('COM_DPCALENDAR_BOOKING_FIELD_PAYMENT_PROVIDER_LABEL')],
			(object)['id' => 'user_name', 'name' => 'user_name', 'label' => Text::_('JGLOBAL_USERNAME')],
			(object)['id' => 'book_date', 'name' => 'book_date', 'label' => Text::_('COM_DPCALENDAR_CREATED_DATE')],
			(object)['id' => 'event', 'name' => 'event', 'label' => Text::_('COM_DPCALENDAR_EVENT')],
			(object)['id' => 'event_author', 'name' => 'event_author', 'label' => Text::_('COM_DPCALENDAR_FIELD_AUTHOR_LABEL')],
			(object)['id' => 'event_calid', 'name' => 'event_calid', 'label' => Text::_('COM_DPCALENDAR_CALENDAR')],
			(object)['id' => 'timezone', 'name' => 'timezone', 'label' => Text::_('COM_DPCALENDAR_TIMEZONE')]
		];

		$parser = function ($name, $booking) {
			switch ($name) {
				case 'status':
					return Booking::getStatusLabel($booking);
				case 'book_date':
					return DPCalendarHelper::getDate($booking->$name)->format('Y-m-d H:i:s', true);
				case 'event':
					$events = [];
					foreach ($booking->tickets as $ticket) {
						$events[] = $ticket->event_title;
					}

					return implode(', ', array_unique($events));
				case 'event_author':
					$authors = [];
					foreach ($booking->tickets as $ticket) {
						$authors[] = $this->getUserFactory()->loadUserById($ticket->event_author)->name;
					}

					return implode(', ', array_unique($authors));
				case 'event_calid':
					$calendars = [];
					foreach ($booking->tickets as $ticket) {
						$calendars[] = $ticket->event_calid;
					}

					return implode(', ', array_unique($calendars));
				case 'timezone':
					return DPCalendarHelper::getDate()->getTimezone()->getName();
				case 'user_name':
					if (!$booking->user_id) {
						return '';
					}

					return $booking->user_name . ' [' . $this->getUserFactory()->loadUserById($booking->user_id)->username . ']';
				default:
					return $booking->$name ?? '';
			}
		};

		return $this->getData('booking', $fields, $parser, $bookings);
	}

	public function getTicketsData(array $tickets = []): array
	{
		$fields = [
			(object)['id' => 'uid', 'name' => 'uid', 'label' => Text::_('JGRID_HEADING_ID')],
			(object)['id' => 'status', 'name' => 'status', 'label' => Text::_('JSTATUS')],
			(object)['id' => 'name', 'name' => 'name', 'label' => Text::_('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL')],
			(object)['id' => 'event_title', 'name' => 'event_title', 'label' => Text::_('COM_DPCALENDAR_EVENT')],
			(object)['id' => 'start_date', 'name' => 'start_date', 'label' => Text::_('COM_DPCALENDAR_FIELD_START_DATE_LABEL')],
			(object)['id' => 'end_date', 'name' => 'end_date', 'label' => Text::_('COM_DPCALENDAR_FIELD_END_DATE_LABEL')],
			(object)['id' => 'email', 'name' => 'email', 'label' => Text::_('COM_DPCALENDAR_BOOKING_FIELD_EMAIL_LABEL')],
			(object)['id' => 'telephone', 'name' => 'telephone', 'label' => Text::_('COM_DPCALENDAR_BOOKING_FIELD_TELEPHONE_LABEL')],
			(object)['id' => 'country', 'name' => 'country_code_value', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL')],
			(object)['id' => 'province', 'name' => 'province', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL')],
			(object)['id' => 'city', 'name' => 'city', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL')],
			(object)['id' => 'zip', 'name' => 'zip', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL')],
			(object)['id' => 'street', 'name' => 'street', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL')],
			(object)['id' => 'number', 'name' => 'number', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL')],
			(object)['id' => 'price', 'name' => 'price', 'label' => Text::_('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL')],
			(object)['id' => 'user_name', 'name' => 'user_name', 'label' => Text::_('JGLOBAL_USERNAME')],
			(object)['id' => 'created', 'name' => 'created', 'label' => Text::_('COM_DPCALENDAR_CREATED_DATE')],
			(object)['id' => 'type', 'name' => 'type', 'label' => Text::_('COM_DPCALENDAR_TICKET_FIELD_TYPE_LABEL')],
			(object)['id' => 'event_calid', 'name' => 'event_calid', 'label' => Text::_('COM_DPCALENDAR_CALENDAR')],
			(object)['id' => 'timezone', 'name' => 'timezone', 'label' => Text::_('COM_DPCALENDAR_TIMEZONE')]
		];

		$parser = function ($name, $ticket) {
			switch ($name) {
				case 'status':
					return Booking::getStatusLabel($ticket);
				case 'created':
					return DPCalendarHelper::getDate($ticket->$name)->format('c');
				case 'start_date':
				case 'end_date':
					return DPCalendarHelper::getDate($ticket->$name)->format($ticket->all_day ? 'Y-m-d' : 'Y-m-d H:i:s', true);
				case 'type':
					if (!$ticket->event_prices) {
						return '';
					}

					$prices = json_decode((string)$ticket->event_prices);
					if (!$prices || !array_key_exists($ticket->type, $prices->label)) {
						return '';
					}

					return $prices->label[$ticket->type];
				case 'timezone':
					return DPCalendarHelper::getDate()->getTimezone()->getName();
				case 'user_name':
					if (!$ticket->user_id) {
						return '';
					}

					return $ticket->user_name . ' [' . $this->getUserFactory()->loadUserById($ticket->user_id)->username . ']';
				default:
					return $ticket->$name ?? '';
			}
		};

		return $this->getData('ticket', $fields, $parser, $tickets);
	}

	public function getLocationData(array $locations = []): array
	{
		$fields = [
			(object)['id' => 'id', 'name' => 'id', 'label' => Text::_('JGRID_HEADING_ID')],
			(object)['id' => 'title', 'name' => 'title', 'label' => Text::_('JGLOBAL_TITLE')],
			(object)['id' => 'alias', 'name' => 'alias', 'label' => Text::_('JFIELD_ALIAS_LABEL')],
			(object)['id' => 'country', 'name' => 'country_code_value', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL')],
			(object)['id' => 'province', 'name' => 'province', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL')],
			(object)['id' => 'city', 'name' => 'city', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL')],
			(object)['id' => 'zip', 'name' => 'zip', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL')],
			(object)['id' => 'street', 'name' => 'street', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL')],
			(object)['id' => 'number', 'name' => 'number', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL')],
			(object)['id' => 'rooms', 'name' => 'rooms', 'label' => Text::_('COM_DPCALENDAR_ROOMS')],
			(object)['id' => 'latitude', 'name' => 'number', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_LATITUDE_LABEL')],
			(object)['id' => 'longitude', 'name' => 'number', 'label' => Text::_('COM_DPCALENDAR_LOCATION_FIELD_LONGITUDE_LABEL')],
			(object)['id' => 'url', 'name' => 'url', 'label' => Text::_('COM_DPCALENDAR_FIELD_URL_LABEL')],
			(object)['id' => 'description', 'name' => 'description', 'label' => Text::_('JGLOBAL_DESCRIPTION')],
			(object)['id' => 'color', 'name' => 'color', 'label' => Text::_('COM_DPCALENDAR_FIELD_COLOR_LABEL')],
			(object)['id' => 'state', 'name' => 'status', 'label' => Text::_('JSTATUS')],
			(object)['id' => 'language', 'name' => 'language', 'label' => Text::_('JFIELD_LANGUAGE_LABEL')],
			(object)['id' => 'created', 'name' => 'created', 'label' => Text::_('JGLOBAL_FIELD_CREATED_LABEL')],
			(object)['id' => 'created_by', 'name' => 'created_by', 'label' => Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL')],
			(object)['id' => 'modified', 'name' => 'modified', 'label' => Text::_('JGLOBAL_FIELD_MODIFIED_LABEL')],
			(object)['id' => 'modified_by', 'name' => 'modified_by', 'label' => Text::_('JGLOBAL_FIELD_MODIFIED_BY_LABEL')],
			(object)['id' => 'xreference', 'name' => 'xreference', 'label' => Text::_('COM_DPCALENDAR_FIELD_XREFERENCE_LABEL')]
		];

		$parser = function ($name, $location) {
			switch ($name) {
				case 'rooms':
					return implode(', ', array_map(static fn ($room) => $room->title, (array)$location->rooms));
				case 'status':
					return Booking::getStatusLabel($location);
				case 'created':
				case 'modified':
					if ($location->$name == '0000-00-00 00:00:00') {
						return '';
					}

					return DPCalendarHelper::getDate($location->$name)->format('Y-m-d H:i:s', true);
				case 'description':
					return $this->params->get('export_strip_html') ? strip_tags((string)$location->description) : $location->description;
				default:
					return $location->$name ?? '';
			}
		};

		return $this->getData('location', $fields, $parser, $locations);
	}

	private function getData(string $name, array $fields, callable $valueParser, array $items): array
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return [];
		}

		$name = strtolower($name);

		if ($items === []) {
			/** @var ListModel $model */
			$model = $this->getDPCalendar()->getMVCFactory()->createModel(ucfirst($name) . 's', 'Administrator', ['ignore_request' => false]);
			$model->setState('list.limit', 1000);
			$items = $model->getItems();
			if (!$items) {
				return [];
			}
		}

		$order = $this->params->get('export_' . $name . 's_order', new \stdClass());
		foreach ($order as $index => $field) {
			if ($field->field == 'country') {
				$order->{$index}->field = 'country_code_value';
			}
		}

		$fields = array_merge($fields, FieldsHelper::getFields('com_dpcalendar.' . $name));
		DPCalendarHelper::sortFields($fields, $order);
		$fields = array_filter($fields, fn ($field): bool => !in_array($field->name, $this->params->get('export_' . $name . 's_fields_hide', [])));

		$data   = [];
		$data[] = array_map(static fn ($field) => $field->label, $fields);

		foreach ($items as $item) {
			if (empty($item->text)) {
				$item->text = $item->description ?? '';
			}

			$app->triggerEvent('onContentPrepare', ['com_dpcalendar.' . $name, &$item, &$item->params, 0]);
			$line = [];
			foreach ($fields as $field) {
				if (!isset($item->jcfields) || !array_key_exists($field->id, $item->jcfields)) {
					$line[] = html_entity_decode($valueParser($field->name, $item) ?? '');
					continue;
				}

				$value = $item->jcfields[$field->id]->{$this->params->get('export_value_type', 'value') === 'value' ? 'value' : 'rawvalue'};
				if (is_array($value)) {
					$value = implode(',', $value);
				}

				$line[] = html_entity_decode(trim($this->params->get('export_strip_html') ? strip_tags((string)$value) : $value));
			}

			$data[] = $line;
		}

		return $data;
	}
}
