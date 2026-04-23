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
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;

class SendNewBookingMail implements StageInterface, UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;

	public function __construct(private readonly CMSApplicationInterface $app, UserFactoryInterface $factory)
	{
		$this->setUserFactory($factory);
	}

	public function __invoke(\stdClass $payload): \stdClass
	{
		// Never send when disabled
		if (!$payload->mailParams->get('booking_send_mail_new', 2)) {
			return $payload;
		}

		// Never send a mail when booking has a price and no new should be sent when it has a price
		if ($payload->item->price && $payload->mailParams->get('booking_send_mail_new', 2) == 2) {
			return $payload;
		}

		// Never send a mail when we have been active, cancelled or refunded before
		if ($payload->oldItem && \in_array($payload->oldItem->state, [1, 4, 6, 7, 10])) {
			return $payload;
		}

		// Never send a mail when we are before or after activation process or trashed
		if (\in_array($payload->item->state, [0, 2, 3, 5, 6, 7, 8, -2])) {
			return $payload;
		}

		$mailer = new MustacheMailTemplate('booking.user.new', ['events' => $payload->eventsWithTickets] + $payload->mailVariables);
		$mailer->setCurrentUser($this->getUserFactory()->loadUserById($payload->item->user_id));
		$mailer->setRecipient($payload->item->email);

		if ($payload->mailParams->get('bookingsys_author_as_mail_from')) {
			foreach ($payload->eventsWithTickets as $event) {
				$mailer->getMailerInstance()->setSender($this->getUserFactory()->loadUserById($event->created_by)->email);
				break;
			}
		}

		if ($payload->mailParams->get('booking_include_ics', 1)) {
			$content = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ical', 'Administrator')->createIcalFromEvents($payload->eventsWithTickets, false, true);
			if ($content) {
				$mailer->addAttachment($payload->item->uid . '.ics', $content);
			}
		}

		// Only send tickets when booking is active
		if ($payload->mailParams->get('booking_include_tickets', 1) && $payload->item->state == 1) {
			foreach ($payload->tickets as $ticket) {
				$fileName = Booking::createTicket($ticket, $payload->mailParams, true);
				if (!\in_array($fileName, ['', '0', null], true)) {
					$mailer->addAttachment(basename($fileName), $fileName);
				}
			}
		}

		if ($payload->mailParams->get('booking_include_receipt', 1)) {
			$fileName = Booking::createReceipt($payload->item, $payload->tickets, $payload->mailParams, true);
			if (!\in_array($fileName, ['', '0', null], true)) {
				$mailer->addAttachment(basename($fileName), $fileName);
			}
		}

		$this->app->triggerEvent('onDPCalendarBeforeSendMail', ['com_dpcalendar.booking.new', $mailer->getMailerInstance(), $payload->item]);
		$mailer->send();
		$this->app->triggerEvent('onDPCalendarAfterSendMail', ['com_dpcalendar.booking.new', $mailer->getMailerInstance(), $payload->item]);

		return $payload;
	}
}
