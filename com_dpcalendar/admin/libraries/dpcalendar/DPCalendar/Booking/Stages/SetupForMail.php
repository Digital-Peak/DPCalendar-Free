<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use League\Pipeline\StageInterface;

class SetupForMail implements StageInterface
{
	/**
	 * @var CMSApplication
	 */
	private $application;

	/**
	 * @var Registry
	 */
	private $params;

	public function __construct(CMSApplication $application, Registry $params)
	{
		$this->application = $application;
		$this->params      = $params;
	}

	public function __invoke($payload)
	{
		// Create the booking details for mail notification
		$params = clone $this->params;
		$params->set('show_header', false);

		$format                             = $params->get('event_date_format', 'd.m.Y') . ' ' . $params->get('event_time_format', 'H:i');
		$payload->item->book_date_formatted = DPCalendarHelper::getDate($payload->item->book_date)->format($format, true);
		$payload->mailVariables             = [
			'booking'           => $payload->item,
			'bookingDetails'    => $payload->item->invoice,
			'bookingLink'       => \DPCalendarHelperRoute::getBookingRoute($payload->item, true),
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
			'acceptUrl'    => \DPCalendarHelperRoute::getInviteChangeRoute($payload->item, true, true),
			'declineUrl'   => \DPCalendarHelperRoute::getInviteChangeRoute($payload->item, false, true)
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

		// Show the logo and address in the tickets
		$params->set('show_header', true);

		$payload->mailParams = $params;

		return $payload;
	}
}
