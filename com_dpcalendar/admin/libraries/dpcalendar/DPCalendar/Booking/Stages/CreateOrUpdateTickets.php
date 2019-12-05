<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
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

		$events = $payload->events;

		// Check if series
		if (count($payload->events) == 1) {
			$event = reset($payload->events);

			if ($event->original_id == '-1' && $event->booking_series) {
				$events = Booking::getSeriesEvents($event, 1000);

				foreach ($events as $e) {
					$e->amount_tickets = $event->amount_tickets;
				}
			}
		}

		$payload->tickets = [];
		foreach ($events as $event) {
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
					$ticket->price      = $event->booking_series ? 0 : Booking::getPriceWithDiscount($value, $event);
					$ticket->seat       = $event->capacity_used + 1;
					$ticket->state      = $payload->item->state;
					$ticket->created    = DPCalendarHelper::getDate()->toSql();
					$ticket->type       = $index;

					if ($payload->item->jcfields) {
						$ticket->com_fields = [];
						foreach ($payload->item->jcfields as $field) {
							$relatedTicketFieldName = $field->params->get('ticket_field');
							if (!$relatedTicketFieldName) {
								continue;
							}
							$ticket->com_fields[$relatedTicketFieldName] = $field->value;
						}
					}

					$ticket->event_id = $event->id;

					// Do not create a ticket for the original event just increase the counter
					if ($event->original_id == -1) {
						$table = $this->model->getTable('Event');
						$table->bind($event);
						$table->book(true);
					} else if ($this->model->save((array)$ticket)) {
						// Increase the seat
						$ticket->seat++;

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

						$table = $this->model->getTable('Event');
						$table->bind($event);
						$table->book(true);
					} else {
						$this->setError($this->getError() . PHP_EOL . $this->model->getError());
					}
				}
			}
		}

		return $payload;
	}
}
