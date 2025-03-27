<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DateHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Registry\Registry;

class SetupForMail implements StageInterface
{
	public function __construct(private readonly CMSApplicationInterface $application, private readonly Registry $params)
	{
	}

	public function __invoke(\stdClass $payload): \stdClass
	{
		$this->application->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		// Create the booking details for mail notification
		$params = clone $this->params;
		$params->set('show_header', false);
		$params->set('pdf_header', '');
		$params->set('pdf_content_top', '');
		$params->set('pdf_content_bottom', '');

		$details = DPCalendarHelper::renderLayout(
			'booking.details',
			[
				'booking'    => $payload->item,
				'tickets'    => $payload->tickets,
				'translator' => new Translator($this->application->getLanguage()),
				'dateHelper' => new DateHelper(),
				'params'     => $params
			]
		);

		$params = clone $this->params;

		$format                             = $params->get('event_date_format', 'd.m.Y') . ' ' . $params->get('event_time_format', 'H:i');
		$payload->item->book_date_formatted = DPCalendarHelper::getDate($payload->item->book_date)->format($format, true);
		$payload->mailVariables             = [
			'booking'            => $payload->item,
			'bookingDetails'     => $details,
			'bookingLink'        => RouteHelper::getBookingRoute($payload->item, true),
			'bookingCancelLink'  => RouteHelper::getBookingRoute($payload->item, true) . '&action=cancel',
			'bookingUid'         => $payload->item->uid,
			'bookingStatusLabel' => Booking::getStatusLabel($payload->item),
			'sitename'           => $this->application->get('sitename'),
			'user'               => $payload->item->first_name . ' ' . $payload->item->name,
			'tickets'            => $payload->tickets,
			'countTickets'       => $payload->tickets ? \count($payload->tickets) : 0,
			'acceptUrl'          => RouteHelper::getInviteChangeRoute($payload->item, true, true),
			'declineUrl'         => RouteHelper::getInviteChangeRoute($payload->item, false, true)
		];

		if (!empty($payload->item->jcfields)) {
			foreach ($payload->item->jcfields as $field) {
				$payload->mailVariables['field-' . $field->name] = $field;
			}
		}

		if ($payload->eventsWithTickets) {
			foreach ($payload->eventsWithTickets as $event) {
				$event->text = $event->description;
				$this->application->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$params, 0]);
				$event->description = $event->text;
			}
		}

		$payload->mailParams = $params;

		return $payload;
	}
}
