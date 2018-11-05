<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
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

	public function __construct(\JApplicationCms $application, \JUser $user)
	{
		$this->application = $application;
		$this->user        = $user;
	}

	public function __invoke($payload)
	{
		$amountTickets = 0;

		// Default some data
		$payload->data['price']    = 0;
		$payload->data['id']       = 0;
		$payload->data['currency'] = DPCalendarHelper::getComponentParameter('currency', 'USD');

		// On front we force the user id to the logged in user
		if ($this->application->isClient('site') && !$payload->invite) {
			$payload->data['user_id'] = $this->user->id;
		}

		foreach ($payload->events as $event) {
			// Flag if a payment is required
			$paymentRequired = false;

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

			// Publish if we don't know the state and no payment is required
			if (!isset($payload->data['state']) && !$paymentRequired) {
				$payload->data['state'] = 1;
			}

			if ($newTickets) {
				$payload->eventsWithTickets[] = $event;
			}
		}

		if ($amountTickets == 0) {
			throw new \Exception(\JText::_('COM_DPCALENDAR_BOOK_ERROR_NEEDS_TICKETS'));
		}

		return $payload;
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
				\JText::sprintf('COM_DPCALENDAR_BOOK_ERROR_CAPACITY_EXHAUSTED_USER', $event->price ? $event->price->label[$index] : '',
					$event->title),
				'warning'
			);
		}

		return $amountTickets;
	}
}
