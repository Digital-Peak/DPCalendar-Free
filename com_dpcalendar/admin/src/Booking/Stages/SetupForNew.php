<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\CouponModel;
use DigitalPeak\Component\DPCalendar\Administrator\Model\TaxrateModel;
use DigitalPeak\Component\DPCalendar\Administrator\Table\EventTable;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use League\Pipeline\StageInterface;

class SetupForNew implements StageInterface
{
	public function __construct(
		private readonly CMSApplicationInterface $application,
		private readonly User $user,
		private readonly TaxrateModel $taxRateModel,
		private readonly CouponModel $couponModel,
		private readonly Registry $params,
		private readonly bool $autoAssignUser
	) {
	}

	public function __invoke($payload)
	{
		// Default some data
		$event                          = null;
		$payload->data['price']         = 0;
		$payload->data['price_details'] = [];
		$payload->data['price_tickets'] = 0.0;
		$payload->data['price_options'] = 0.0;
		$payload->data['tax']           = 0.00;
		$payload->data['tax_rate']      = 0;
		$payload->data['id']            = 0;
		$payload->data['currency']      = $this->params->get('currency', 'USD');

		// On front we force the user id to the logged in user
		if ($this->autoAssignUser) {
			$payload->data['user_id'] = $this->user->id;
		}

		// Collect the data
		$amountTickets            = 0;
		$payload->data['options'] = [];
		foreach ($payload->events as $event) {
			$this->handleOptions($payload, $event);
			$amountTickets += $this->handleTickets($payload, $event, $amountTickets);
		}
		$payload->data['options'] = implode(',', $payload->data['options']);

		// When no amount is found, then abort
		if ($amountTickets == 0) {
			throw new \Exception(Text::_('COM_DPCALENDAR_BOOK_ERROR_NEEDS_TICKETS'));
		}

		// The prices for the different
		$priceOptions = $payload->data['price_options'];
		$priceTickets = $payload->data['price_tickets'];

		// Load the coupon
		$coupon = $this->couponModel->getItemByCode(
			empty($payload->data['coupon_id']) ? '' : $payload->data['coupon_id'],
			$event->catid,
			$payload->data['email'],
			$payload->data['user_id']
		);

		// Set the coupon attributes
		$payload->data['coupon_id'] = 0;
		if ($coupon instanceof \stdClass && $coupon->id) {
			$payload->data['coupon_id']   = $coupon->id;
			$payload->data['coupon_area'] = $coupon->area;
		}

		// Tickets price
		if ($coupon instanceof \stdClass && $coupon->id && $coupon->area == 2) {
			$payload->data['price_tickets'] = $this->calculatePrice($coupon, $payload->data['price_tickets']);
		}

		// Options price
		if ($coupon instanceof \stdClass && $coupon->id && $coupon->area == 3) {
			$payload->data['price_options'] = $this->calculatePrice($coupon, $payload->data['price_options']);
		}

		// Set the price from the prices minus the coupon price
		$payload->data['price']       = $payload->data['price_tickets'] + $payload->data['price_options'];
		$payload->data['coupon_rate'] = $priceTickets + $priceOptions - $payload->data['price_tickets'] - $payload->data['price_options'];

		// Subtract from full price
		if ($coupon instanceof \stdClass && $coupon->id && $coupon->area == 1) {
			$payload->data['price']       = $this->calculatePrice($coupon, $payload->data['price']);
			$payload->data['coupon_rate'] = $priceTickets + $priceOptions - $payload->data['price'];
		}

		// Determine tax
		$taxRate = empty($payload->data['country']) ? null : $this->taxRateModel->getItemByCountry($payload->data['country']);
		if ($taxRate !== null) {
			$payload->data['tax'] = $taxRate->inclusive ? $payload->data['price'] - ($payload->data['price'] / (1 + ($taxRate->rate / 100))) : ($payload->data['price'] / 100) * $taxRate->rate;
			$payload->data['price'] += $taxRate->inclusive ? 0 : $payload->data['tax'];
			$payload->data['tax_rate']      = $taxRate->rate;
			$payload->data['tax_title']     = $taxRate->title;
			$payload->data['tax_inclusive'] = $taxRate->inclusive;
		}

		// Do not force state when not on front and is available
		if (!$this->application->isClient('administrator') || empty($payload->data['state'])) {
			if (!array_key_exists('state', $payload->data)) {
				$payload->data['state'] = 0;
			}

			// When skipping the review step always, set state to tickets reviewed
			if ($payload->data['state'] == 0
				&& $this->params->get('booking_review_step', 2) == 0) {
				$payload->data['state'] = 2;
			}

			// When skipping to review step on one ticket, set state to tickets reviewed
			if ($payload->data['state'] == 0
				&& $this->params->get('booking_review_step', 2) == 2
				&& $amountTickets == 1) {
				$payload->data['state'] = 2;
			}

			// When tickets are reviewed, capacity is not full and confirmation step should be skipped, set state to active
			if (!$payload->data['price']
				&& ($event->capacity === null || $event->capacity_used < $event->capacity)
				&& $payload->data['state'] == 2
				&& !$this->params->get('booking_confirm_step', 1)) {
				$payload->data['state'] = 1;
			}

			// When tickets are reviewed and capacity is full and waiting list is active, put it on the waiting list
			$event = reset($payload->events);
			if ($payload->data['state'] == 2 && ((is_countable($payload->events) ? count($payload->events) : 0) == 1 || $event->booking_series != 2)
				&& $event->capacity != null && $event->capacity_used >= $event->capacity && $event->booking_waiting_list) {
				$payload->data['state'] = 8;
			}

			// Ensure when tickets are on waiting list, that booking has the correct state
			if ((int)$event->waiting_list_count > 0) {
				$payload->data['state'] = 8;
			}
		}

		// Compute the token
		if ($this->params->get('bookingsys_enable_token')) {
			$payload->data['token'] = bin2hex(random_bytes(16));
		}

		// Return the payload
		return $payload;
	}

	/**
	 * Determine the price for the options of the event.
	 */
	private function handleOptions(\stdClass $payload, \stdClass|EventTable $event): void
	{
		// The booking options
		if (empty($payload->data['event_id'][$event->id]['options'])) {
			return;
		}

		$amount = $payload->data['event_id'][$event->id]['options'];
		foreach ($event->booking_options as $key => $option) {
			$key = preg_replace('/\D/', '', (string)$key);
			if (!array_key_exists($key !== '' && $key !== '0' && $key !== [] && $key !== null ? $key : '', $amount) || empty($amount[$key])) {
				continue;
			}

			$payload->data['options'][] = $event->id . '-' . $key . '-' . $amount[$key];

			$priceOriginal = $option->price * $amount[$key];
			$priceDiscount = $priceOriginal;

			$payload->data['price_details'][$event->id]['options'][$key] = [
				'discount' => DPCalendarHelper::renderPrice(number_format($priceDiscount, 2, '.', '')),
				'original' => DPCalendarHelper::renderPrice(number_format($priceOriginal, 2, '.', '')),
				'raw'      => number_format($priceOriginal, 2, '.', '')
			];

			$payload->data['price_options'] += $priceDiscount;
		}
	}

	/**
	 * Determine the price for the options of the event.
	 */
	private function handleTickets(\stdClass $payload, \stdClass|EventTable $event, int $amountTickets): int
	{
		// The tickets to process
		$amount = $payload->data['event_id'][$event->id]['tickets'];

		// Free event
		if (!$event->price) {
			$event->amount_tickets[0] = $this->getAmountTickets($event, $payload, $amount, 0, 0);

			// Add the event to the list of events with tickets
			if ($event->amount_tickets[0] !== 0) {
				$payload->eventsWithTickets[] = $event;
			}

			// Return the amount of new tickets
			return $event->amount_tickets[0];
		}

		$amountTickets = 0;
		$newTickets    = false;

		// Loop over the prices
		foreach ($event->price->value as $index => $value) {
			// Get the amount of tickets
			$event->amount_tickets[$index] = $this->getAmountTickets($event, $payload, $amount, $index, $amountTickets);
			$amountTickets += $event->amount_tickets[$index];

			// Initialize the price details
			$payload->data['price_details'][$event->id]['tickets'][$index] = ['discount' => '0.00', 'original' => '0.00'];

			// Determine the price
			$paymentRequired = Booking::paymentRequired($event);

			// Load the price
			if ($event->amount_tickets[$index] && $paymentRequired) {
				// Set the original price
				$priceOriginal = $value * $event->amount_tickets[$index];
				// Get the price with a discount
				$priceDiscount = Booking::getPriceWithDiscount($value, $event) * $event->amount_tickets[$index];

				// Set the price details
				$payload->data['price_details'][$event->id]['tickets'][$index] = [
					'discount' => DPCalendarHelper::renderPrice(number_format($priceDiscount, 2, '.', '')),
					'original' => DPCalendarHelper::renderPrice(number_format($priceOriginal, 2, '.', '')),
					'raw'      => number_format($priceOriginal, 2, '.', '')
				];

				// Add the real price to the full price for tickets
				$payload->data['price_tickets'] += $priceDiscount;
			}
			// Ensure there are new tickets
			if ($newTickets) {
				continue;
			}
			if ($event->amount_tickets[$index] === 0) {
				continue;
			}
			$newTickets = true;
		}

		// Add the event to the list of events with tickets
		if ($newTickets) {
			$payload->eventsWithTickets[] = $event;
		}

		// Return the amount of new tickets
		return $amountTickets;
	}

	/**
	 * Determine the price for the options of the event.
	 */
	private function getAmountTickets(\stdClass|EventTable $event, \stdClass $payload, array $amount, int $index, int $alreadyCollected): int
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

	/**
	 * Calculate the price for the coupon.
	 */
	private function calculatePrice(\stdClass $coupon, float $price): float
	{
		// Set the discount price
		$discount = $price;

		// Subtract the value
		if ($coupon->type == 'value') {
			$discount -= $coupon->value;
		}

		// Subtract the percentage
		if ($coupon->type == 'percentage') {
			$discount -= ($price / 100) * $coupon->value;
		}

		// Ensure a valid number
		if ($discount < 0) {
			return 0.0;
		}

		// Return the discount
		return $discount;
	}
}
