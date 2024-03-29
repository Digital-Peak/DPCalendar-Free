<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Application\CMSApplication;
use League\Pipeline\StageInterface;

class CollectEventsAndTickets implements StageInterface
{
	private \DPCalendarModelBooking $model;

	/**
	 * @var CMSApplication
	 */
	private $application;

	public function __construct(CMSApplication $application, \DPCalendarModelBooking $model)
	{
		$this->application = $application;
		$this->model       = $model;
	}

	public function __invoke($payload)
	{
		if ($payload->oldItem) {
			$payload->tickets = $payload->oldItem->tickets;
			foreach ($payload->tickets as $ticket) {
				$payload->events[$ticket->event_id]                     = $this->model->getEvent($ticket->event_id);
				$payload->events[$ticket->event_id]->waiting_list_count = count((array) array_filter(
					$payload->events[$ticket->event_id]->tickets,
					static fn ($t): bool => $t->state == 8
				));
			}
			$payload->eventsWithTickets = $payload->events;

			return $payload;
		}

		if (!$payload->data['event_id']) {
			return $payload;
		}

		foreach ((array)$payload->data['event_id'] as $eId => $types) {
			$event = $this->model->getEvent($eId);

			if ($event->original_id == '-1' && $event->booking_series != 1) {
				throw new \Exception('Whole series can only be booked when series booking is enabled in original event. Please contact the administrator!');
			}

			if ($event->original_id > 0 && $event->booking_series == 1) {
				$event                                 = $this->model->getEvent($event->original_id);
				$payload->data['event_id'][$event->id] = $types;
				unset($payload->data['event_id'][$eId]);
			}

			$event->waiting_list_count = count((array) array_filter($event->tickets, static fn ($t): bool => $t->state == 8));

			// If we can't book continue
			if (!Booking::openForBooking($event)) {
				if (DPCalendarHelper::getDate($event->start_date)->format('U') < DPCalendarHelper::getDate()->format('U')) {
					$this->application->enqueueMessage(Text::_('COM_DPCALENDAR_BOOK_ERROR_PAST'), 'warning');

					continue;
				}
				if ($event->capacity !== null && $event->capacity_used >= $event->capacity) {
					$this->application->enqueueMessage(Text::_('COM_DPCALENDAR_BOOK_ERROR_CAPACITY_EXHAUSTED'), 'warning');

					continue;
				}

				continue;
			}

			$event->amount_tickets = [];

			$payload->events[] = $event;
		}

		return $payload;
	}
}
