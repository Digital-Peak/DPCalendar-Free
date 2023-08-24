<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Object\CMSObject;

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use DPCalendar\Helper\DPCalendarHelper;
use League\Pipeline\StageInterface;

class CreateOrUpdateTickets implements StageInterface
{
	private ?CMSApplication $application = null;

	private \DPCalendarModelTicket $model;

	public function __construct(\DPCalendarModelTicket $model, CMSApplication $application)
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
					$ticket->state = $ticket->state == 9 ? $ticket->state : $payload->item->state;
					$saveTicket    = true;

					if (!in_array($payload->item->state, [1, 4]) && in_array($payload->oldItem->state, [1, 4])) {
						foreach ($payload->events as $event) {
							if ($event->id != $ticket->event_id) {
								continue;
							}
							$event->book(false);
						}
					}

					if (in_array($payload->item->state, [1, 4]) && !in_array($payload->oldItem->state, [1, 4])) {
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
		foreach ($payload->events as $event) {
			$prices = $event->price;

			if (!$prices) {
				// Free event
				$prices = new CMSObject(['value' => [0 => 0]]);
			}

			foreach ($prices->value as $index => $value) {
				for ($i = 0; $i < $event->amount_tickets[$index]; $i++) {
					$ticket             = (object)$payload->data;
					$ticket->id         = 0;
					$ticket->uid        = 0;
					$ticket->booking_id = $payload->item->id;
					$ticket->price      = Booking::getPriceWithDiscount($value, $event);
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
							$ticket->com_fields[$relatedTicketFieldName] = $field->rawvalue;
						}
					}

					$ticket->event_id = $event->id;

					if (!$this->model->save((array)$ticket, false)) {
						throw new \Exception($this->model->getError());
					}

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

					if ($ticket->state == 1 || $ticket->state == 4) {
						$event->book(true);
					}
				}
			}
		}

		return $payload;
	}
}
