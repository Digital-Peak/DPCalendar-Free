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

class CollectEventsAndTickets implements StageInterface
{
	/**
	 * @var \DPCalendarModelBooking
	 */
	private $model;

	public function __construct(\DPCalendarModelBooking $model)
	{
		$this->model = $model;
	}

	public function __invoke($payload)
	{
		if ($payload->oldItem) {
			$payload->tickets = $this->model->getTickets($payload->oldItem->id);
			foreach ($payload->tickets as $ticket) {
				$payload->events[] = $this->model->getEvent($ticket->event_id);
			}
			$payload->eventsWithTickets = $payload->events;

			return $payload;
		}

		foreach ((array)$payload->data['event_id'] as $eId => $types) {
			$event = $this->model->getEvent($eId);

			// If we can't book continue
			if (!Booking::openForBooking($event)) {
				if (DPCalendarHelper::getDate($event->start_date)->format('U') < DPCalendarHelper::getDate()->format('U')) {
					$this->application->enqueueMessage(\JText::_('COM_DPCALENDAR_BOOK_ERROR_PAST'), 'warning');

					continue;
				}
				if ($event->capacity !== null && $event->capacity_used >= $event->capacity) {
					$this->application->enqueueMessage(\JText::_('COM_DPCALENDAR_BOOK_ERROR_CAPACITY_EXHAUSTED'), 'warning');

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
