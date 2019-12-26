<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use DPCalendar\Helper\DPCalendarHelper;
use League\Pipeline\StageInterface;

class SetupForNew implements StageInterface
{
	/**
	 * @var \JApplicationCms
	 */
	private $application = null;

	/**
	 * @var \JUser
	 */
	private $user = null;

	/**
	 * @var \DPCalendarModelTaxrate
	 */
	private $model = null;

	public function __construct(\JApplicationCms $application, \JUser $user, \DPCalendarModelTaxrate $model)
	{
		$this->application = $application;
		$this->user        = $user;
		$this->model       = $model;
	}

	public function __invoke($payload)
	{
		// Default some data
		$payload->data['price']    = 0;
		$payload->data['tax']      = 0.00;
		$payload->data['id']       = 0;
		$payload->data['currency'] = DPCalendarHelper::getComponentParameter('currency', 'USD');

		$taxRate                   = $this->model->getItemByCountry($payload->data['country']);
		$payload->data['tax_rate'] = $taxRate ? $taxRate->rate : 0;

		// On front we force the user id to the logged in user
		if ($this->application->isClient('site') && !$payload->invite) {
			$payload->data['user_id'] = $this->user->id;
		}

		$amountTickets = 0;
		foreach ($payload->events as $event) {
			$this->handleOptions($payload, $event);
			$amountTickets = $this->handleTickets($payload, $event, $amountTickets);
		}

		if ($amountTickets == 0) {
			throw new \Exception(\JText::_('COM_DPCALENDAR_BOOK_ERROR_NEEDS_TICKETS'));
		}

		// Publish if the price is 0
		if (!$payload->data['price']) {
			$payload->data['state'] = 1;
		}

		if ($taxRate) {
			$payload->data['tax']   = ($payload->data['price'] / 100) * $taxRate->rate;
			$payload->data['price'] = $payload->data['price'] + (!$taxRate->inclusive ? $payload->data['tax'] : 0);
		}

		return $payload;
	}

	private function handleOptions($payload, $event)
	{
		// The booking options
		$payload->data['options'] = [];
		if (!empty($payload->data['event_id'][$event->id]['options'])) {
			$amount = $payload->data['event_id'][$event->id]['options'];

			foreach ($event->booking_options as $key => $option) {
				$key = preg_replace('/\D/', '', $key);

				if (!array_key_exists($key, $amount) || empty($amount[$key])) {
					continue;
				}

				$payload->data['options'][] = $event->id . '-' . $key . '-' . $amount[$key];

				$payload->data['price'] += $amount[$key] * $option->price;
				$payload->data['state'] = 3;
			}
		}
		$payload->data['options'] = implode(',', $payload->data['options']);
	}

	private function handleTickets($payload, $event, $amountTickets)
	{
		// Flag if a payment is required
		$paymentRequired = false;

		// The tickets to process
		$amount = $payload->data['event_id'][$event->id]['tickets'];

		$newTickets = false;
		if (!$event->price) {
			// Free event
			$event->amount_tickets[0] = $this->getAmountTickets($event, $payload, $amount, 0);
			$amountTickets            += $event->amount_tickets[0];

			if (!$newTickets && $event->amount_tickets[0]) {
				$newTickets = true;
			}
		} else {
			foreach ($event->price->value as $index => $value) {
				$event->amount_tickets[$index] = $this->getAmountTickets($event, $payload, $amount, $index);
				$amountTickets                 += $event->amount_tickets[$index];

				// Determine the price
				$paymentRequired = Booking::paymentRequired($event);
				if ($event->amount_tickets[$index] && $paymentRequired) {
					// Set state to payment required
					$payload->data['state'] = 3;

					// Determine the price based on the amount of tickets
					$payload->data['price'] += Booking::getPriceWithDiscount($value, $event) * $event->amount_tickets[$index];
				}

				if (!$newTickets && $event->amount_tickets[$index]) {
					$newTickets = true;
				}
			}
		}

		if ($newTickets) {
			$payload->eventsWithTickets[] = $event;
		}

		return $amountTickets;
	}

	private function getAmountTickets($event, $payload, $amount, $index)
	{
		// Check if the user or email address has already tickets booked
		$bookedTickets = 0;
		foreach ($event->tickets as $ticket) {
			if (($ticket->email !== $payload->data['email'] && ($this->user->guest || $ticket->user_id != $payload->data['user_id'])) || $ticket->type != $index) {
				continue;
			}
			$bookedTickets++;
		}
		if ($bookedTickets > $event->max_tickets) {
			$bookedTickets = $event->max_tickets;
		}
		$amountTickets = $amount[$index] > ($event->max_tickets - $bookedTickets) ? $event->max_tickets - $bookedTickets : $amount[$index];

		if ($event->capacity !== null && $amountTickets > ($event->capacity - $event->capacity_used)) {
			$amountTickets = $event->capacity - $event->capacity_used;
		}

		if ($amountTickets < 1 && $amount[$index] > 0) {
			$amountTickets = 0;
			$this->application->enqueueMessage(
				\JText::sprintf(
					'COM_DPCALENDAR_BOOK_ERROR_CAPACITY_EXHAUSTED_USER',
					$event->price ? $event->price->label[$index] : '',
					$event->title
				),
				'warning'
			);
		}

		return $amountTickets;
	}
}
