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

class CreateOrUpdateTickets implements StageInterface
{
	/**
	 * @var \JApplicationCms
	 */
	private $application = null;

	/**
	 * @var \DPCalendarModelTicket
	 */
	private $model;

	public function __construct(\DPCalendarModelTicket $model, \JApplicationCms $application)
	{
		$this->model       = $model;
		$this->application = $application;
	}

	public function __invoke($payload)
	{
		// Creating the tickets
		if ($payload->oldItem) {
			// Update the tickets from the booking
			foreach ($payload->tickets as $ticket) {
				$saveTicket = false;

				if (!$ticket->user_id && $payload->item->user_id) {
					$ticket->user_id = $payload->item->user_id;
					$saveTicket      = true;
				}

				if ($payload->oldItem && $payload->oldItem->state != $payload->item->state) {
					$ticket->state = $payload->item->state;
					$saveTicket    = true;
				}

				if (!$saveTicket) {
					continue;
				}

				$this->model->getTable('Ticket')->save($ticket);
			}

			return $payload;
		}

		$payload->tickets = [];
		foreach ($payload->events as $event) {
			$prices = $event->price;

			if (!$prices) {
				// Free event
				$prices = new \JObject(array('value' => array(0 => 0)));
			}
			foreach ($prices->value as $index => $value) {
				for ($i = 0; $i < $event->amount_tickets[$index]; $i++) {
					$ticket             = (object)$payload->data;
					$ticket->id         = 0;
					$ticket->uid        = 0;
					$ticket->booking_id = $payload->item->id;
					$ticket->price      = Booking::getPriceWithDiscount($value, $event);
					$ticket->seat       = $event->capacity_used + 1;
					$ticket->state      = $payload->item->state;
					$ticket->created    = DPCalendarHelper::getDate()->toSql();
					$ticket->type       = $index;

					$ticket->event_id = $event->id;

					// Save the ticket
					if ($this->model->save((array)$ticket)) {
						// Increase the seat
						$ticket->seat++;
						$event->book(true);
						$t                = $this->model->getItem();
						$t->event_calid   = $event->catid;
						$t->event_title   = $event->title;
						$t->start_date    = $event->start_date;
						$t->end_date      = $event->end_date;
						$t->all_day       = $event->all_day;
						$t->show_end_time = $event->show_end_time;
						$t->event_prices  = $event->price;
						$t->event_options = $event->booking_options;

						$payload->tickets[] = $t;
					} else {
						$this->setError($this->getError() . PHP_EOL . $this->model->getError());
					}
				}
			}
		}

		return $payload;
	}
}
