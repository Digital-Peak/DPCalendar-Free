<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use League\Pipeline\StageInterface;

class SetupForMail implements StageInterface
{
	/**
	 * @var \JApplicationCms
	 */
	private $application = null;

	public function __construct(\JApplicationCms $application)
	{
		$this->application = $application;
	}

	public function __invoke($payload)
	{
		$this->application->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		// Create the booking details for mail notification
		$params = clone \JComponentHelper::getParams('com_dpcalendar');
		$params->set('show_header', false);

		$payload->item->book_date_formatted = DPCalendarHelper::getDate($payload->item->book_date)
			->format($params->get('event_date_format', 'm.d.Y') . ' ' . $params->get('event_time_format', 'g:i a'), true);
		$payload->mailVariables             = array(
			'booking'        => $payload->item,
			'bookingDetails' => $payload->item->invoice,
			'bookingLink'    => \DPCalendarHelperRoute::getBookingRoute($payload->item, true),
			'bookingUid'     => $payload->item->uid,
			'sitename'       => $this->application->get('sitename'),
			'user'           => $payload->item->name,
			'tickets'        => $payload->tickets,
			'countTickets'   => count($payload->tickets)
		);

		foreach ($payload->item->jcfields as $field) {
			$payload->mailVariables['field-' . $field->name] = $field;
		}

		if ($payload->eventsWithTickets) {
			foreach ($payload->eventsWithTickets as $event) {
				$event->text = $event->description;
				$this->application->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$params, 0]);
				$event->description = $event->text;
			}
		}

		// Show the logo and address in the tickets
		$params->set('show_header', true);

		$payload->mailParams = $params;

		return $payload;
	}
}
