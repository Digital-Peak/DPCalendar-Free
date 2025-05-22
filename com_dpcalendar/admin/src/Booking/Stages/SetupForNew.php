<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Exception\TicketExhaustedException;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\CouponModel;
use DigitalPeak\Component\DPCalendar\Administrator\Model\CurrencyModel;
use DigitalPeak\Component\DPCalendar\Administrator\Model\TaxrateModel;
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;

class SetupForNew implements StageInterface
{
	public function __construct(
		private readonly CMSApplicationInterface $application,
		private readonly User $user,
		private readonly TaxrateModel $taxRateModel,
		private readonly CouponModel $couponModel,
		private readonly CurrencyModel $currencyModel,
		private readonly Registry $params,
		private readonly bool $autoAssignUser
	) {
	}

	public function __invoke(\stdClass $payload): \stdClass
	{
		// Default some data
		$event                             = null;
		$payload->data['price']            = 0;
		$payload->data['price_details']    = [];
		$payload->data['price_tickets']    = 0.0;
		$payload->data['price_options']    = 0.0;
		$payload->data['events_discount']  = 0.0;
		$payload->data['tickets_discount'] = 0.0;
		$payload->data['tax']              = 0.0;
		$payload->data['tax_rate']         = 0.0;
		$payload->data['coupon_id']        = empty($payload->data['coupon_id']) ? 0 : $payload->data['coupon_id'];
		$payload->data['coupon_area']      = 0;
		$payload->data['coupon_rate']      = 0.0;
		$payload->data['id']               = 0;
		$payload->data['currency']         = $this->currencyModel->getActualCurrency()->currency;
		$payload->discounts                = [
			'events_discount_amount'  => 0,
			'events_discount_title'   => '',
			'events_discount_value'   => 0,
			'tickets_discount_amount' => 0,
			'tickets_discount_title'  => '',
			'tickets_discount_value'  => 0
		];

		// On front we force the user id to the logged in user
		if ($this->autoAssignUser) {
			$payload->data['user_id'] = $this->user->id;
		}

		// Collect the data
		$amountTickets            = 0;
		$amountEvents             = 0;
		$payload->data['options'] = [];
		foreach ($payload->events as $event) {
			$this->currencyModel->setupCurrencyPrices($event);

			$this->handleOptions($payload, $event);
			$eventTickets = $this->handleTickets($payload, $event, $amountTickets);

			if ($eventTickets > 0) {
				$amountEvents++;
			}

			$amountTickets += $eventTickets;
		}
		$payload->data['options'] = implode(',', $payload->data['options']);

		// When no amount is found, then abort
		if ($amountTickets === 0 && !$payload->data['price_options']) {
			throw new TicketExhaustedException(Text::_('COM_DPCALENDAR_BOOK_ERROR_NEEDS_TICKETS'));
		}

		if ($amountEvents && !empty($payload->original_event) && !empty($payload->original_event->events_discount)) {
			$savedDiscount = null;
			$newPrice      = (int)$payload->data['price_tickets'];
			foreach ($payload->original_event->events_discount as $discount) {
				$amount = (int)$discount->amount;
				if ($amountEvents < $amount || ($savedDiscount !== null && (int)$savedDiscount->amount > $amount)) {
					continue;
				}

				$savedDiscount = $discount;

				// @phpstan-ignore-next-line
				$payload->discounts['events_discount_title']  = $discount->label ?? '';
				$payload->discounts['events_discount_amount'] = $amount;
				$payload->discounts['events_discount_value']  = $discount->type === 'value' ? DPCalendarHelper::renderPrice($discount->value) : $discount->value . '%';
				$payload->data['events_discount']             = (int)$discount->value;

				if ($discount->type !== 'percentage') {
					$newPrice = $payload->data['price_tickets'] - (int)$discount->value;
					continue;
				}

				$newPrice                         = $payload->data['price_tickets'] - (($payload->data['price_tickets'] / 100) * (int)$discount->value);
				$newPrice                         = $newPrice < 0 ? 0 : $newPrice;
				$payload->data['events_discount'] = $payload->data['price_tickets'] - $newPrice;
			}

			$payload->data['price_tickets'] = $newPrice;
		}

		if (!empty($event->tickets_discount)) {
			$savedDiscount = null;
			$newPrice      = (int)$payload->data['price_tickets'];
			foreach ($event->tickets_discount as $discount) {
				$amount = (int)$discount->amount;
				if ($amountTickets < $amount || ($savedDiscount !== null && (int)$savedDiscount->amount > $amount)) {
					continue;
				}

				$savedDiscount = $discount;

				$payload->discounts['tickets_discount_title']  = $discount->label ?? '';
				$payload->discounts['tickets_discount_amount'] = $amount;
				$payload->discounts['tickets_discount_value']  = $discount->type === 'value' ? DPCalendarHelper::renderPrice($discount->value) : $discount->value . '%';
				$payload->data['tickets_discount']             = (int)$discount->value;

				if ($discount->type !== 'percentage') {
					$newPrice = $payload->data['price_tickets'] - (int)$discount->value;
					continue;
				}

				$newPrice                          = $payload->data['price_tickets'] - (($payload->data['price_tickets'] / 100) * (int)$discount->value);
				$newPrice                          = $newPrice < 0 ? 0 : $newPrice;
				$payload->data['tickets_discount'] = $payload->data['price_tickets'] - $newPrice;
			}

			$payload->data['price_tickets'] = $newPrice;
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

		// Reset here to ensure an integer
		$payload->data['coupon_id'] = 0;

		// Set the coupon attributes
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

		// Set the price from the price minus the coupon price
		$payload->data['price']       = $payload->data['price_tickets'] + $payload->data['price_options'];
		$payload->data['coupon_rate'] = $priceTickets + $priceOptions - $payload->data['price_tickets'] - $payload->data['price_options'];

		// Subtract from full price
		if ($coupon instanceof \stdClass && $coupon->id && $coupon->area == 1) {
			$payload->data['price']       = $this->calculatePrice($coupon, $payload->data['price']);
			$payload->data['coupon_rate'] = $priceTickets + $priceOptions - $payload->data['price'];
		}

		// Determine tax
		$taxRate = empty($payload->data['country']) ? null : $this->taxRateModel->getItemByCountry($payload->data['country']);

		// Check if there is a tax free custom field
		if ($fieldName = $this->params->get('bookingsys_tax_free_custom_field')) {
			foreach ($payload->events as $event) {
				// Ensure fields are loaded
				if (empty($event->jcfields)) {
					$this->application->triggerEvent('onContentPrepare', ['com_dpcalendar.event', $event, $this->params, 0]);
				}

				// Still no fields
				if (empty($event->jcfields)) {
					continue;
				}

				// Loop over the fields
				foreach ($event->jcfields as $field) {
					// If the field is the one we are looking for and the value resolves to true, unset the tax
					if ($field->name == $fieldName && filter_var($field->rawvalue, FILTER_VALIDATE_BOOLEAN)) {
						$taxRate = null;
					}
				}
			}
		}

		if ($taxRate instanceof \stdClass) {
			$payload->data['tax'] = $taxRate->inclusive ? $payload->data['price'] - ($payload->data['price'] / (1 + ($taxRate->rate / 100))) : ($payload->data['price'] / 100) * $taxRate->rate;
			$payload->data['price'] += $taxRate->inclusive ? 0 : $payload->data['tax'];
			$payload->data['tax_rate']      = $taxRate->rate;
			$payload->data['tax_title']     = $taxRate->title;
			$payload->data['tax_inclusive'] = $taxRate->inclusive;
		}

		// Do not force state when not on front and is available
		if (!$this->application instanceof AdministratorApplication || empty($payload->data['state'])) {
			if (!\array_key_exists('state', $payload->data)) {
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
			if ($payload->data['state'] == 2 && ((is_countable($payload->events) ? \count($payload->events) : 0) == 1 || $event->booking_series != 2)
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
	private function handleOptions(\stdClass $payload, \stdClass $event): void
	{
		// The booking options
		if (empty($payload->data['event_id'][$event->id]['options']) || !$event->booking_options) {
			return;
		}

		$amount = $payload->data['event_id'][$event->id]['options'];
		foreach ($event->booking_options as $key => $option) {
			$key = (int)preg_replace('/\D/', '', (string)$key);
			if (empty($amount[$key])) {
				continue;
			}

			$payload->data['options'][] = $event->id . '-' . $key . '-' . $amount[$key];

			$priceOriginal = $option->value * $amount[$key];
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
	 * Determine the price for the tickets of the event.
	 */
	private function handleTickets(\stdClass $payload, \stdClass $event, int $amountTickets): int
	{
		// The tickets to process
		$amount = $payload->data['event_id'][$event->id]['tickets'];
		if (empty($amount)) {
			return 0;
		}

		// Free event
		if (!$event->prices) {
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
		foreach ($event->prices as $key => $price) {
			$key = (int)preg_replace('/\D/', '', (string)$key);

			// Get the amount of tickets
			$event->amount_tickets[$key] = $this->getAmountTickets($event, $payload, $amount, $key, $amountTickets);
			$amountTickets += $event->amount_tickets[$key];

			// Initialize the price details
			$payload->data['price_details'][$event->id]['tickets'][$key] = [
				'discount' => DPCalendarHelper::renderPrice('0.00'),
				'original' => DPCalendarHelper::renderPrice('0.00')
			];

			// Determine the price
			$paymentRequired = Booking::paymentRequired($event);

			// Load the price
			if ($event->amount_tickets[$key] && $paymentRequired) {
				// Set the original price
				$priceOriginal = $price->value * $event->amount_tickets[$key];

				// Get the price with a discount
				$priceDiscount = Booking::getPriceWithDiscount($price->value, $event) * $event->amount_tickets[$key];

				// Set the price details
				$payload->data['price_details'][$event->id]['tickets'][$key] = [
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

			if ($event->amount_tickets[$key] === 0) {
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
	private function getAmountTickets(\stdClass $event, \stdClass $payload, array $amount, int $key, int $alreadyCollected): int
	{
		// Check if the user or email address has already tickets booked
		$bookedTickets = 0;
		foreach ($event->tickets as $ticket) {
			if (($ticket->email !== $payload->data['email'] && ($this->user->guest || $ticket->user_id != $payload->data['user_id'])) || $ticket->type != $key) {
				continue;
			}
			$bookedTickets++;
		}

		// Make sure booked tickets is correct
		if ($bookedTickets > $event->max_tickets) {
			$bookedTickets = $event->max_tickets;
		}

		// If there are already booked tickets and the limit is hit, reduce the amount
		$amountTickets = $amount[$key] > ($event->max_tickets - $bookedTickets) ? $event->max_tickets - $bookedTickets : $amount[$key];

		// If the amount is bigger than the available space, reduce it when waiting list is not activated
		if ($event->capacity !== null && $amountTickets > ($event->capacity - $event->capacity_used - $alreadyCollected) && !$event->booking_waiting_list) {
			$amountTickets = $event->capacity - $event->capacity_used - $alreadyCollected;
		}

		// If the amount of tickets is 0 raise a warning
		if ($amountTickets < 1 && $amount[$key] > 0) {
			$amountTickets = 0;
			$this->application->enqueueMessage(
				Text::sprintf(
					'COM_DPCALENDAR_BOOK_ERROR_CAPACITY_EXHAUSTED_USER',
					$event->prices ? $event->prices->{'prices' . $key}->label : '',
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
