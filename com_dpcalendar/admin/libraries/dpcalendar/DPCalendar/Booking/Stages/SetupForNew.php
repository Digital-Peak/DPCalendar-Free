<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
	private $taxRateModel = null;

	/**
	 * @var \DPCalendarModelCoupon
	 */
	private $couponModel = null;

	public function __construct(
		\JApplicationCms $application,
		\JUser $user,
		\DPCalendarModelTaxrate $taxRateModel,
		\DPCalendarModelCoupon $couponModel
	) {
		$this->application  = $application;
		$this->user         = $user;
		$this->taxRateModel = $taxRateModel;
		$this->couponModel  = $couponModel;
	}

	public function __invoke($payload)
	{
		// Default some data
		$payload->data['price']    = 0;
		$payload->data['tax']      = 0.00;
		$payload->data['tax_rate'] = 0;
		$payload->data['id']       = 0;
		$payload->data['currency'] = DPCalendarHelper::getComponentParameter('currency', 'USD');

		// Do not force state when not on front and is available
		if (!$this->application->isClient('administrator') || empty($payload->data['state'])) {
			$payload->data['state'] = !$payload->invite ? 0 : 5;
		}

		// On front we force the user id to the logged in user
		if ($this->application->isClient('site') && !$payload->invite) {
			$payload->data['user_id'] = $this->user->id;
		}

		$amountTickets            = 0;
		$payload->data['options'] = [];
		foreach ($payload->events as $event) {
			$this->handleOptions($payload, $event);
			$amountTickets += $this->handleTickets($payload, $event, $amountTickets);
		}
		$payload->data['options'] = implode(',', $payload->data['options']);

		if ($amountTickets == 0) {
			throw new \Exception(\JText::_('COM_DPCALENDAR_BOOK_ERROR_NEEDS_TICKETS'));
		}

		$coupon = $this->couponModel->getItemByCode(
			empty($payload->data['coupon_id']) ? null : $payload->data['coupon_id'],
			$payload->data['price'],
			$event->catid,
			$payload->data['email'],
			$payload->data['user_id']
		);
		if ($coupon && $coupon->id) {
			$payload->data['coupon_id']   = $coupon->id;
			$payload->data['coupon_rate'] = $coupon->discount_value;
			$payload->data['price']       = $payload->data['price'] - $coupon->discount_value;
		}

		$taxRate = $this->taxRateModel->getItemByCountry($payload->data['country']);
		if ($taxRate) {
			$payload->data['tax']      = ($payload->data['price'] / 100) * $taxRate->rate;
			$payload->data['price']    = $payload->data['price'] + (!$taxRate->inclusive ? $payload->data['tax'] : 0);
			$payload->data['tax_rate'] = $taxRate->rate;
		}

		return $payload;
	}

	private function handleOptions($payload, $event)
	{
		// The booking options
		if (empty($payload->data['event_id'][$event->id]['options'])) {
			return;
		}

		$amount = $payload->data['event_id'][$event->id]['options'];
		foreach ($event->booking_options as $key => $option) {
			$key = preg_replace('/\D/', '', $key);
			if (!array_key_exists($key, $amount) || empty($amount[$key])) {
				continue;
			}

			$payload->data['options'][] = $event->id . '-' . $key . '-' . $amount[$key];
			$payload->data['price']     += $amount[$key] * $option->price;
		}
	}

	private function handleTickets($payload, $event, $amountTickets)
	{
		// The tickets to process
		$amount = $payload->data['event_id'][$event->id]['tickets'];

		$amountTickets = 0;
		$newTickets    = false;
		if (!$event->price) {
			// Free event
			$event->amount_tickets[0] = $this->getAmountTickets($event, $payload, $amount, 0, $amountTickets);
			$amountTickets            += $event->amount_tickets[0];

			if (!$newTickets && $event->amount_tickets[0]) {
				$newTickets = true;
			}
		} else {
			foreach ($event->price->value as $index => $value) {
				$event->amount_tickets[$index] = $this->getAmountTickets($event, $payload, $amount, $index, $amountTickets);
				$amountTickets                 += $event->amount_tickets[$index];

				// Determine the price
				$paymentRequired = Booking::paymentRequired($event);
				if ($event->amount_tickets[$index] && $paymentRequired) {
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

	private function getAmountTickets($event, $payload, $amount, $index, $alreadyCollected)
	{
		// Check if the user or email address has already tickets booked
		$bookedTickets = 0;
		foreach ($event->tickets as $ticket) {
			if (($ticket->email !== $payload->data['email'] && ($this->user->guest || $ticket->user_id != $payload->data['user_id'])) || $ticket->type != $index) {
				continue;
			}
			$bookedTickets++;
		}

		// Make sure booked tickets is correct
		if ($bookedTickets > $event->max_tickets) {
			$bookedTickets = $event->max_tickets;
		}

		// If there are already booked tickets and the limit is hit, reduce the amount
		$amountTickets = $amount[$index] > ($event->max_tickets - $bookedTickets) ? $event->max_tickets - $bookedTickets : $amount[$index];

		// If the amount is bigger than the available space, reduce it
		if ($event->capacity !== null && $amountTickets > ($event->capacity - $event->capacity_used - $alreadyCollected)) {
			$amountTickets = $event->capacity - $event->capacity_used - $alreadyCollected;
		}

		// If the amount of tickets is 0 raise a warning
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
