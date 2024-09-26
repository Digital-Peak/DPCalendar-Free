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
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerInterface;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;
use League\Pipeline\StageInterface;

class SendNewBookingMail implements StageInterface, UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;

	public function __construct(private readonly MailerInterface $mailer, private readonly CMSApplicationInterface $app, UserFactoryInterface $factory)
	{
		$this->setUserFactory($factory);
	}

	public function __invoke($payload)
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
		if ($payload->oldItem && \in_array($payload->oldItem->state, [1, 4, 6, 7])) {
			return $payload;
		}

		// Never send a mail when we are before or after activation process
		if (\in_array($payload->item->state, [0, 2, 3, 5, 6, 7, 8])) {
			return $payload;
		}

		// Send a mail to the booker
		$subject = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			$payload->language->_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_SUBJECT'),
			null,
			$payload->mailVariables
		);
		$body = trim(
			DPCalendarHelper::renderEvents(
				$payload->eventsWithTickets,
				DPCalendarHelper::getStringFromParams(
					'bookingsys_new_booking_mail',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_BODY',
					$payload->mailParams,
					$payload->language
				),
				null,
				$payload->mailVariables
			)
		);

		if ($body !== '' && $body !== '0') {
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
			if ($payload->mailParams->get('booking_include_ics', 1)) {
				$icsFile = JPATH_ROOT . '/tmp/' . $payload->item->uid . '.ics';
				$content = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ical', 'Administrator')->createIcalFromEvents($payload->eventsWithTickets, false, true);
				if (!$content || (file_put_contents($icsFile, $content) === 0 || file_put_contents($icsFile, $content) === false)) {
					$icsFile = null;
				} else {
					$this->mailer->addAttachment($icsFile);
					$files[] = $icsFile;
				}
			}

			// Only send tickets when booking is active
			if ($payload->mailParams->get('booking_include_tickets', 1) && $payload->item->state == 1) {
				foreach ($payload->tickets as $ticket) {
					$fileName = Booking::createTicket($ticket, $payload->mailParams, true);
					if ($fileName !== '' && $fileName !== '0' && $fileName !== null) {
						$this->mailer->addAttachment($fileName);
						$files[] = $fileName;
					}
				}
			}

			if ($payload->mailParams->get('booking_include_receipt', 1)) {
				$fileName = Booking::createReceipt($payload->item, $payload->tickets, $payload->mailParams, true);
				if ($fileName !== '' && $fileName !== '0' && $fileName !== null) {
					$this->mailer->addAttachment($fileName);
					$files[] = $fileName;
				}
			}

			try {
				$this->app->triggerEvent('onDPCalendarBeforeSendMail', ['com_dpcalendar.booking.new', $this->mailer, $payload->item]);
				$this->mailer->Send();
				$this->app->triggerEvent('onDPCalendarAfterSendMail', ['com_dpcalendar.booking.new', $this->mailer, $payload->item]);
				foreach ($files as $file) {
					if (file_exists($file)) {
						unlink($file);
					}
				}
			} catch (\Exception $e) {
				foreach ($files as $file) {
					if (file_exists($file)) {
						unlink($file);
					}
				}

				if (!$e instanceof MailDisabledException) {
					throw $e;
				}
			}
		}

		return $payload;
	}
}
