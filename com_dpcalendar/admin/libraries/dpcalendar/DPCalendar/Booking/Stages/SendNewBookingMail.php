<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\Ical;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\Mail;
use League\Pipeline\StageInterface;

class SendNewBookingMail implements StageInterface
{
	/**
	 * @var Mail
	 */
	private $mailer;

	/**
	 * @var CMSApplication
	 */
	private $app;

	public function __construct(Mail $mailer, CMSApplication $app)
	{
		$this->mailer = $mailer;
		$this->app    = $app;
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
		if ($payload->oldItem && in_array($payload->oldItem->state, [1, 4, 6, 7])) {
			return $payload;
		}

		// Never send a mail when we are before or after activation process
		if (in_array($payload->item->state, [0, 2, 3, 5, 6, 7, 8])) {
			return $payload;
		}

		// If a payment is required include the payment statement from the plugin
		$oldDetails = $payload->mailVariables['bookingDetails'];
		if ($payload->item->state == 4) {
			$payload->mailVariables['bookingDetails'] = $payload->mailVariables['bookingDetails']
				. '<br/>' . Booking::getPaymentStatementFromPlugin($payload->item, null, $payload->language);
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

		if (!empty($body)) {
			$this->mailer->setSubject($subject);
			$this->mailer->setBody($body);
			$this->mailer->IsHTML(true);
			$this->mailer->addRecipient($payload->item->email);

			$files = [];
			if ($payload->mailParams->get('booking_include_ics', 1)) {
				$icsFile = JPATH_ROOT . '/tmp/' . $payload->item->uid . '.ics';
				$content = Ical::createIcalFromEvents($payload->eventsWithTickets, false, true);
				if (!$content || !file_put_contents($icsFile, $content)) {
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
					if ($fileName) {
						$this->mailer->addAttachment($fileName);
						$files[] = $fileName;
					}
				}
			}
			try {
				$this->app->triggerEvent('onDPCalendarBeforeSendMail', ['com_dpcalendar.booking.new', $this->mailer, $payload->item]);
				$this->mailer->Send();
				$this->app->triggerEvent('onDPCalendarAfterSendMail', ['com_dpcalendar.booking.new', $this->mailer, $payload->item]);
				foreach ($files as $file) {
					\JFile::delete($file);
				}
			} catch (\Exception $e) {
				foreach ($files as $file) {
					\JFile::delete($file);
				}

				if (!$e instanceof MailDisabledException) {
					throw $e;
				}
			}
		}

		$payload->mailVariables['bookingDetails'] = $oldDetails;

		return $payload;
	}
}
