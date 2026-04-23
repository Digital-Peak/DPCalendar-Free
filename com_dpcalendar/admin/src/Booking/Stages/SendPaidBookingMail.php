<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Mail\MustacheMailTemplate;
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;

class SendPaidBookingMail implements StageInterface, UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;

	public function __construct(private readonly CMSApplicationInterface $app, UserFactoryInterface $factory)
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

		$mailer = new MustacheMailTemplate('booking.user.pay', ['events' => $payload->eventsWithTickets] + $payload->mailVariables);
		$mailer->setCurrentUser($this->getUserFactory()->loadUserById($payload->item->user_id));
		$mailer->setRecipient($payload->item->email);

		if ($payload->mailParams->get('bookingsys_author_as_mail_from')) {
			foreach ($payload->eventsWithTickets as $event) {
				$mailer->getMailerInstance()->setSender($this->getUserFactory()->loadUserById($event->created_by)->email);
			}
		}

		if ($payload->mailParams->get('booking_include_receipt', 1)) {
			$fileName = Booking::createReceipt($payload->item, $payload->tickets, $payload->mailParams, true);
			if (!\in_array($fileName, ['', '0', null], true)) {
				$mailer->addAttachment(basename($fileName), $fileName);
			}
		}

		if ($payload->mailParams->get('booking_include_tickets', 1)) {
			foreach ($payload->tickets as $ticket) {
				$fileName = Booking::createTicket($ticket, $payload->mailParams, true);
				if (!\in_array($fileName, ['', '0', null], true)) {
					$mailer->addAttachment(basename($fileName), $fileName);
				}
			}
		}

		$this->app->triggerEvent('onDPCalendarBeforeSendMail', ['com_dpcalendar.booking.paid', $mailer->getMailerInstance(), $payload->item]);
		$mailer->send();
		$this->app->triggerEvent('onDPCalendarAfterSendMail', ['com_dpcalendar.booking.paid', $mailer->getMailerInstance(), $payload->item]);

		return $payload;
	}
}
