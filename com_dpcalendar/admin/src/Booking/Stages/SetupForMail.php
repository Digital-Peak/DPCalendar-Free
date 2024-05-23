<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DateHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use League\Pipeline\StageInterface;

class SetupForMail implements StageInterface
{
	public function __construct(private readonly CMSApplicationInterface $application, private readonly Registry $params)
	{
	}

	public function __invoke($payload)
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
			'booking'           => $payload->item,
			'bookingDetails'    => $details,
			'bookingLink'       => RouteHelper::getBookingRoute($payload->item, true),
			'bookingCancelLink' => Route::link(
				'site',
				'index.php?option=com_dpcalendar&task=booking.cancel&b_id=' . $payload->item->id
					. ($payload->item->token ? '&token=' . $payload->item->token : ''),
				false,
				Route::TLS_IGNORE,
				true
			),
			'bookingUid'   => $payload->item->uid,
			'sitename'     => $this->application->get('sitename'),
			'user'         => $payload->item->name,
			'tickets'      => $payload->tickets,
			'countTickets' => $payload->tickets ? count($payload->tickets) : 0,
			'acceptUrl'    => RouteHelper::getInviteChangeRoute($payload->item, true, true),
			'declineUrl'   => RouteHelper::getInviteChangeRoute($payload->item, false, true)
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
