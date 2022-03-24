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
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use League\Pipeline\StageInterface;

class SetupForNew implements StageInterface
{
	/**
	 * @var CMSApplication
	 */
	private $application = null;

	/**
	 * @var User
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

	/**
	 * @var bool
	 */
	private $autoAssignUser = false;

	public function __construct(
		CMSApplication $application,
		User $user,
		\DPCalendarModelTaxrate $taxRateModel,
		\DPCalendarModelCoupon $couponModel,
		$autoAssignUser
	) {
		$this->application    = $application;
		$this->user           = $user;
		$this->taxRateModel   = $taxRateModel;
		$this->couponModel    = $couponModel;
		$this->autoAssignUser = $autoAssignUser;
	}

	public function __invoke($payload)
	{
		// Default some data
		$payload->data['price']    = 0;
		$payload->data['tax']      = 0.00;
		$payload->data['tax_rate'] = 0;
		$payload->data['id']       = 0;
		$payload->data['currency'] = DPCalendarHelper::getComponentParameter('currency', 'USD');

		// On front we force the user id to the logged in user
		if ($this->autoAssignUser) {
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
			throw new \Exception(Text::_('COM_DPCALENDAR_BOOK_ERROR_NEEDS_TICKETS'));
		}

		// Do not force state when not on front and is available
		if (!$this->application->isClient('administrator') || empty($payload->data['state'])) {
			if (!array_key_exists('state', $payload->data)) {
				$payload->data['state'] = 0;
			}

			// When skipping the review step always, set state to tickets reviewed
			if ($payload->data['state'] == 0
				&& \DPCalendar\Helper\DPCalendarHelper::getComponentParameter('booking_review_step', 2) == 0) {
				$payload->data['state'] = 2;
			}

			// When skipping to review step on one ticket, set state to tickets reviewed
			if ($payload->data['state'] == 0
				&& \DPCalendar\Helper\DPCalendarHelper::getComponentParameter('booking_review_step', 2) == 2
				&& $amountTickets == 1) {
				$payload->data['state'] = 2;
			}

			// When tickets are reviewed, capacity is not full and confirmation step should be skipped, set state to active
			if (!$payload->data['price']
				&& ($event->capacity === null || $event->capacity_used < $event->capacity)
				&& $payload->data['state'] == 2
				&& !\DPCalendar\Helper\DPCalendarHelper::getComponentParameter('booking_confirm_step', 1)) {
				$payload->data['state'] = 1;
			}

			// When tickets are reviewed and capacity is full and waiting list is active, put it on the waiting list
			$event = reset($payload->events);
			if ($payload->data['state'] == 2 && (count($payload->events) == 1 || $event->booking_series != 2)
				&& $event->capacity != null && $event->capacity_used >= $event->capacity && $event->booking_waiting_list) {
				$payload->data['state'] = 8;
			}
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
		} else {
			$payload->data['coupon_id'] = 0;
		}

		$taxRate = !empty($payload->data['country']) ? $this->taxRateModel->getItemByCountry($payload->data['country']) : null;
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
			$payload->data['price'] += $amount[$key] * $option->price;
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
			$amountTickets += $event->amount_tickets[0];

			if (!$newTickets && $event->amount_tickets[0]) {
				$newTickets = true;
			}
		} else {
			foreach ($event->price->value as $index => $value) {
				$event->amount_tickets[$index] = $this->getAmountTickets($event, $payload, $amount, $index, $amountTickets);
				$amountTickets += $event->amount_tickets[$index];

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

		// If the amount is bigger than the available space, reduce it when waiting list is not activated
		if ($event->capacity !== null && $amountTickets > ($event->capacity - $event->capacity_used - $alreadyCollected) && !$event->booking_waiting_list) {
			$amountTickets = $event->capacity - $event->capacity_used - $alreadyCollected;
		}

		// If the amount of tickets is 0 raise a warning
		if ($amountTickets < 1 && $amount[$index] > 0) {
			$amountTickets = 0;
			$this->application->enqueueMessage(
				Text::sprintf(
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
