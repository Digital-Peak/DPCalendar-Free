<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Helper;

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Field\DpcfieldsField;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Form\FormFactoryAwareTrait;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Registry\Registry;

trait ExportTrait
{
	use UserFactoryAwareTrait;
	use FormFactoryAwareTrait;

	public function getEventData(array $events, Registry $config): array
	{
		$parser = function ($name, $event) use ($config) {
			switch ($name) {
				case 'catid':
					$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);
					return $calendar instanceof CalendarInterface ? $calendar->getTitle() : $event->catid;
				case 'state':
					return Booking::getStatusLabel($event);
				case 'location_ids':
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
					return $config->get('strip_html') ? strip_tags((string)$event->description) : $event->description;
				default:
					return $event->$name ?? '';
			}
		};

		return $this->getData('event', $parser, $events, $config);
	}

	public function getBookingsData(array $bookings, Registry $config): array
	{
		$parser = function ($name, $booking) {
			switch ($name) {
				case 'state':
					return Booking::getStatusLabel($booking);
				case 'book_date':
					return DPCalendarHelper::getDate($booking->$name)->format('Y-m-d H:i:s', true);
				case 'country':
					return $booking->country_code_value ?? '';
				case 'options':
					if (empty($booking->tickets) || empty($booking->options)) {
						return '';
					}

					$buffer = [];
					foreach (explode(',', (string)$booking->options) as $o) {
						[$eventId, $type, $amount] = explode('-', $o);
						foreach ($booking->tickets as $ticket) {
							if ($ticket->event_id != $eventId) {
								continue;
							}

							$options = json_decode((string)$ticket->event_options);
							if (empty($options) || empty($options->{'booking_options' . $type})) {
								continue;
							}

							$buffer[] = $options->{'booking_options' . $type}->label . ': ' . $amount;
							break;
						}
					}

					return implode(', ', $buffer);
				case 'tickets_count':
					return $booking->amount_tickets;
				case 'event_id':
					$events = [];
					foreach ($booking->tickets as $ticket) {
						$events[] = $ticket->event_title;
					}

					return implode(', ', array_unique($events));
				case 'event_author':
					$authors = [];
					foreach ($booking->tickets as $ticket) {
						if (empty($ticket->event_author)) {
							continue;
						}

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
				case 'user_id':
					if (!$booking->user_id) {
						return '';
					}

					return $booking->user_name . ' [' . $this->getUserFactory()->loadUserById($booking->user_id)->username . ']';
				default:
					return $booking->$name ?? '';
			}
		};

		return $this->getData('booking', $parser, $bookings, $config);
	}

	public function getTicketsData(array $tickets, Registry $config): array
	{
		$parser = function ($name, $ticket) {
			switch ($name) {
				case 'state':
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
					if (!$prices || empty($prices->{'prices' . $ticket->type})) {
						return '';
					}

					return $prices->{'prices' . $ticket->type}->label;
				case 'country':
					return $ticket->country_code_value ?? '';
				case 'timezone':
					return DPCalendarHelper::getDate()->getTimezone()->getName();
				case 'user_id':
					if (!$ticket->user_id) {
						return '';
					}

					return $ticket->user_name . ' [' . $this->getUserFactory()->loadUserById($ticket->user_id)->username . ']';
				default:
					return $ticket->$name ?? '';
			}
		};

		return $this->getData('ticket', $parser, $tickets, $config);
	}

	public function getLocationData(array $locations, Registry $config): array
	{
		$parser = function ($name, $location) use ($config) {
			switch ($name) {
				case 'rooms':
					return implode(', ', array_map(static fn ($room) => $room->title, (array)$location->rooms));
				case 'state':
					return Booking::getStatusLabel($location);
				case 'created':
				case 'modified':
					if ($location->$name == '0000-00-00 00:00:00') {
						return '';
					}

					return DPCalendarHelper::getDate($location->$name)->format('Y-m-d H:i:s', true);
				case 'description':
					return $config->get('strip_html') ? strip_tags((string)$location->description) : $location->description;
				default:
					return $location->$name ?? '';
			}
		};

		return $this->getData('location', $parser, $locations, $config);
	}

	private function getData(string $name, callable $valueParser, array $items, Registry $config): array
	{
		$app = $this->getApplication();
		// @phpstan-ignore-next-line
		if (!$app instanceof CMSApplicationInterface) {
			return [];
		}

		$name = strtolower($name);

		if ($items === []) {
			/** @var ListModel $model */
			$model = $this->getDPCalendar()->getMVCFactory()->createModel(ucfirst($name) . 's', 'Administrator', ['ignore_request' => false]);
			$model->setState('list.limit', 1000);
			$items = $model->getItems();
		}

		// Load the plugin form
		$form = $this->getFormFactory()->createForm($this->_name);

		if ($this instanceof CMSPlugin) {
			$form->loadFile(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/' . $this->_name . '.xml', false, '//config');

			// Get the config field
			$formField = $form->getField('export_configurations', 'params');
			if (!$formField instanceof SubformField) {
				return [];
			}

			$form = $formField->loadSubForm();
		}

		if ($this instanceof BaseView) {
			$form->loadFile(JPATH_SITE . '/components/com_dpcalendar/tmpl/' . $this->getName() . '/default.xml', false, '//metadata');
		}

		// Get the available options
		$formField = $form->getField($name . 's_fields', $this instanceof CMSPlugin ? '' : 'params');
		if (!$formField instanceof SubformField) {
			return [];
		}

		$formField = $formField->loadSubForm()->getField('field');
		if (!$formField instanceof DpcfieldsField) {
			return [];
		}

		// The selected fields from the params where the field value is reduced
		$formFields = $formField->getFields();
		$fields     = [];
		foreach (array_column((array)$config->get($name . 's_fields', []), 'field') as $fieldName) {
			foreach ($formFields as $field) {
				if ($field->value !== $fieldName) {
					continue;
				}

				$fields[] = $field;
				break;
			}
		}

		// Define the data array with the labels as first column
		$data = [array_map(static fn ($field) => $field->text ?? $field->label, $fields)];

		// Loop over the array
		foreach ($items as $item) {
			// Normalize for prepare event
			if (empty($item->text)) {
				$item->text = $item->description ?? '';
			}

			$app->triggerEvent('onContentPrepare', ['com_dpcalendar.' . $name, &$item, &$item->params, 0]);

			// The line array
			$line = [];

			// Loop over the fields
			foreach ($fields as $field) {
				$customField = array_filter($item->jcfields ?? [], fn ($f): bool => $f->name === $field->value);

				// Check if it is a custom field
				if ($customField === []) {
					// Add the value to the cell
					$line[] = html_entity_decode($valueParser($field->value, $item) ?? '');
					continue;
				}

				// Get either the value or raw one
				$value = reset($customField)->{$config->get('value_type', 'value') === 'value' ? 'value' : 'rawvalue'};

				// Implode the array to have only one value
				if (\is_array($value)) {
					$value = implode(',', $value);
				}

				// Create the cell, either with or without tags
				$line[] = html_entity_decode(trim($config->get('strip_html') ? strip_tags((string)$value) : $value));
			}

			// Set a property as well
			$item->preparedData = $line;

			// Add the line to the data
			$data[] = $line;
		}

		// Return the data
		return $data;
	}
}
