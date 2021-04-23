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

		$payload->item->book_date_formatted = DPCalendarHelper::getDate($payload->item->book_date)
			->format($params->get('event_date_format', 'd.m.Y') . ' ' . $params->get('event_time_format', 'H:i'), true);
		$payload->mailVariables             = [
			'booking'        => $payload->item,
			'bookingDetails' => $payload->item->invoice,
			'bookingLink'    => \DPCalendarHelperRoute::getBookingRoute($payload->item, true),
			'bookingUid'     => $payload->item->uid,
			'sitename'       => $this->application->get('sitename'),
			'user'           => $payload->item->name,
			'tickets'        => $payload->tickets,
			'countTickets'   => count($payload->tickets)
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
