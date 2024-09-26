<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die();

class FieldsOrderModel extends BaseModel
{
	public function getBookingFields(\stdClass $booking, Registry $params, CMSApplicationInterface $app): array
	{
		if (!isset($booking->jcfields)) {
			$booking->text = '';

			if (empty($booking->catid) && !empty($booking->tickets)) {
				$booking->catid = $booking->tickets[0]->event_calid;
			}

			$app->triggerEvent('onContentPrepare', ['com_dpcalendar.booking', &$booking, &$params, 0]);
		}

		// Set up the fields
		$bookingFields   = [];
		$bookingFields[] = (object)['id' => 'name', 'name' => 'name'];
		$bookingFields[] = (object)['id' => 'email', 'name' => 'email'];
		$bookingFields[] = (object)['id' => 'telephone', 'name' => 'telephone'];
		$bookingFields[] = (object)[
			'id'    => 'country',
			'name'  => 'country' . (empty($booking->country_code_value) ? '' : '_code_value'),
			'label' => 'COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'
		];
		$bookingFields[] = (object)[
			'id'    => 'province',
			'name'  => 'province',
			'label' => 'COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL'
		];
		$bookingFields[] = (object)['id' => 'city', 'name' => 'city', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL'];
		$bookingFields[] = (object)['id' => 'zip', 'name' => 'zip', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL'];
		$bookingFields[] = (object)['id' => 'street', 'name' => 'street', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL'];
		$bookingFields[] = (object)['id' => 'number', 'name' => 'number', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL'];

		$bookingFields = array_merge($bookingFields, $booking->jcfields ?? []);

		// Adjust country when it is the value
		$order = (object)$params->get('booking_fields_order', new \stdClass());
		// @phpstan-ignore-next-line
		foreach ($order as $index => $field) {
			$field = (object)$field;
			if ($field->field == 'country') {
				$order->{$index}->field = 'country' . (empty($booking->country_code_value) ? '' : '_code_value');
			}
		}

		// Sort the fields
		DPCalendarHelper::sortFields($bookingFields, $order);

		// Prepare the booking fields
		foreach ($bookingFields as $key => $field) {
			if (!$params->get('booking_show_' . $field->name, 1)) {
				unset($bookingFields[$key]);
				continue;
			}

			$label = 'COM_DPCALENDAR_BOOKING_FIELD_' . strtoupper((string)$field->name) . '_LABEL';

			if (isset($field->label)) {
				$label = $field->label;
			}

			$content = '';
			if (property_exists($booking, $field->name)) {
				$content = $booking->{$field->name};
			}
			if (property_exists($field, 'value')) {
				$content = $field->value;
			}

			if (!$content) {
				unset($bookingFields[$key]);
				continue;
			}

			$field->dpDisplayLabel   = $label;
			$field->dpDisplayContent = $content;
		}

		return $bookingFields;
	}


	public function getTicketFields(\stdClass $ticket, Registry $params, CMSApplicationInterface $app): array
	{
		if (!isset($ticket->jcfields)) {
			$ticket->text = '';
			$app->triggerEvent('onContentPrepare', ['com_dpcalendar.ticket', &$ticket, &$params, 0]);
		}

		$ticketFields   = [];
		$ticketFields[] = (object)['id' => 'name', 'name' => 'name', 'label' => 'COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL'];
		$ticketFields[] = (object)['id' => 'email', 'name' => 'email'];
		$ticketFields[] = (object)[
			'id'    => 'country',
			'name'  => 'country' . (empty($ticket->country_code_value) ? '' : '_code_value'),
			'label' => 'COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'
		];
		$ticketFields[] = (object)[
			'id'    => 'province',
			'name'  => 'province',
			'label' => 'COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL'
		];
		$ticketFields[] = (object)['id' => 'city', 'name' => 'city', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL'];
		$ticketFields[] = (object)['id' => 'zip', 'name' => 'zip', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL'];
		$ticketFields[] = (object)['id' => 'street', 'name' => 'street', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL'];
		$ticketFields[] = (object)['id' => 'number', 'name' => 'number', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL'];
		$ticketFields[] = (object)['id' => 'telephone', 'name' => 'telephone'];

		$ticketFields = array_merge($ticketFields, $ticket->jcfields ?? []);

		// Adjust country when it it the value
		$order = (object)$params->get('ticket_fields_order', new \stdClass());
		// @phpstan-ignore-next-line
		foreach ($order as $index => $field) {
			$field = (object)$field;
			if ($field->field == 'country') {
				$order->{$index}->field = 'country' . (empty($ticket->country_code_value) ? '' : '_code_value');
			}
		}

		DPCalendarHelper::sortFields($ticketFields, $order);

		foreach ($ticketFields as $key => $field) {
			if (!$params->get('ticket_show_' . $field->name, 1)) {
				unset($ticketFields[$key]);
				continue;
			}

			$label = 'COM_DPCALENDAR_BOOKING_FIELD_' . strtoupper((string)$field->name) . '_LABEL';

			if (isset($field->label)) {
				$label = $field->label;
			}

			$content = '';
			if (property_exists($ticket, $field->name)) {
				$content = $ticket->{$field->name};
			}
			if (property_exists($field, 'value')) {
				$content = $field->value;
			}

			if (!$content) {
				unset($ticketFields[$key]);
				continue;
			}

			$field->dpDisplayLabel   = $label;
			$field->dpDisplayContent = $content;
		}

		return $ticketFields;
	}
}
