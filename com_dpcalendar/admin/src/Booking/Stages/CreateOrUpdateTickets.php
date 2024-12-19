<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\TicketModel;
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Table\EventTable;

class CreateOrUpdateTickets implements StageInterface
{
	public function __construct(private readonly TicketModel $model)
	{
	}

	public function __invoke(\stdClass $payload): \stdClass
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

				if ($payload->oldItem->state != $payload->item->state) {
					$ticket->state = $ticket->state == 9 ? $ticket->state : $payload->item->state;
					$saveTicket    = true;

					if (!\in_array($payload->item->state, [1, 4]) && \in_array($payload->oldItem->state, [1, 4])) {
						foreach ($payload->events as $event) {
							if ($event->id != $ticket->event_id) {
								continue;
							}
							$event->book(false);
						}
					}

					if (\in_array($payload->item->state, [1, 4]) && !\in_array($payload->oldItem->state, [1, 4])) {
						foreach ($payload->events as $event) {
							if ($event->id != $ticket->event_id) {
								continue;
							}
							$event->book(true);
						}
					}
				}

				if (!$saveTicket) {
					continue;
				}

				$this->model->getTable('Ticket')->save($ticket);
			}

			return $payload;
		}

		$payload->tickets = [];
		/** @var EventTable $event */
		foreach ($payload->events as $event) {
			$prices = \is_string($event->prices) ? json_decode((string)$event->prices) : $event->prices;

			if (!$prices) {
				// Free event
				$prices = [(object)['value' => 0]];
			}

			foreach ($prices as $key => $price) {
				$key = preg_replace('/\D/', '', (string)$key);
				for ($i = 0; $i < $event->amount_tickets[$key]; $i++) {
					$ticket             = (object)$payload->data;
					$ticket->id         = 0;
					$ticket->uid        = 0;
					$ticket->booking_id = $payload->item->id;
					$ticket->price      = Booking::getPriceWithDiscount($price->value, (object)$event->getData());
					$ticket->state      = $payload->item->state;
					$ticket->created    = DPCalendarHelper::getDate()->toSql();
					$ticket->type       = $key;

					if ($payload->item->jcfields) {
						$ticket->com_fields = [];
						foreach ($payload->item->jcfields as $field) {
							$relatedTicketFieldName = $field->params->get('ticket_field');
							if (!$relatedTicketFieldName) {
								continue;
							}
							$ticket->com_fields[$relatedTicketFieldName] = $field->rawvalue;
						}
					}

					$ticket->event_id = $event->id;

					if (!$this->model->save((array)$ticket, false)) {
						throw new \Exception($this->model->getError());
					}

					$t                = $this->model->getItem() ?: new \stdClass();
					$t->event_calid   = $event->catid;
					$t->event_title   = $event->title;
					$t->start_date    = $event->start_date;
					$t->end_date      = $event->end_date;
					$t->all_day       = $event->all_day;
					$t->show_end_time = $event->show_end_time;
					$t->event_prices  = $event->prices;
					$t->event_options = $event->booking_options;

					$payload->tickets[] = $t;

					if ($ticket->state == 1 || $ticket->state == 4) {
						$event->book(true);
					}
				}
			}
		}

		return $payload;
	}
}
