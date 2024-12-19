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
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerInterface;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;

class SendPaidBookingMail implements StageInterface, UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;

	public function __construct(private readonly MailerInterface $mailer, UserFactoryInterface $factory)
	{
		$this->setUserFactory($factory);
	}

	public function __invoke(\stdClass $payload): \stdClass
	{
		// Never send when disabled, is new booking or has no price
		if (!$payload->mailParams->get('booking_send_mail_paid', 2) || !$payload->oldItem || !$payload->item->price) {
			return $payload;
		}

		// Never send a mail when we have been active or cancelled/refunded before
		if (\in_array($payload->oldItem->state, [1, 6, 7])) {
			return $payload;
		}

		// Never send a mail when the booking is not active
		if ($payload->item->state != 1) {
			return $payload;
		}

		// We have a successful payment
		$subject = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			$payload->language->_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_PAYED_SUBJECT'),
			null,
			$payload->mailVariables
		);
		$body = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			DPCalendarHelper::getStringFromParams(
				'bookingsys_paid_booking_mail',
				'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_PAYED_BODY',
				$payload->mailParams,
				$payload->language
			),
			null,
			$payload->mailVariables
		);

		if ($payload->mailParams->get('bookingsys_author_as_mail_from')) {
			foreach ($payload->eventsWithTickets as $event) {
				$this->mailer->setSender($this->getUserFactory()->loadUserById($event->created_by)->email);
			}
		}

		$this->mailer->setSubject($subject);
		$this->mailer->setBody($body);
		$this->mailer->addRecipient($payload->item->email);
		if ($this->mailer instanceof Mail) {
			$this->mailer->IsHTML(true);
		}

		$files = [];
		if ($payload->mailParams->get('booking_include_receipt', 1)) {
			$fileName = Booking::createReceipt($payload->item, $payload->tickets, $payload->mailParams, true);
			if ($fileName !== '' && $fileName !== '0' && $fileName !== null) {
				$this->mailer->addAttachment($fileName);
				$files[] = $fileName;
			}
		}

		if ($payload->mailParams->get('booking_include_tickets', 1)) {
			foreach ($payload->tickets as $ticket) {
				$fileName = Booking::createTicket($ticket, $payload->mailParams, true);
				if ($fileName !== '' && $fileName !== '0' && $fileName !== null) {
					$this->mailer->addAttachment($fileName);
					$files[] = $fileName;
				}
			}
		}

		try {
			$this->mailer->Send();
			foreach ($files as $file) {
				if (file_exists($file)) {
					unlink($file);
				}
			}
		} catch (\Exception $exception) {
			foreach ($files as $file) {
				if (file_exists($file)) {
					unlink($file);
				}
			}

			throw $exception;
		}

		return $payload;
	}
}
