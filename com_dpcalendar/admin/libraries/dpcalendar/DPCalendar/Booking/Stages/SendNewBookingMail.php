<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\Ical;
use League\Pipeline\StageInterface;

class SendNewBookingMail implements StageInterface
{
	/**
	 * @var \JMail
	 */
	private $mailer = null;

	public function __construct(\JMail $mailer)
	{
		$this->mailer = $mailer;
	}

	public function __invoke($payload)
	{
		if ($payload->oldItem || !$payload->mailParams->get('booking_send_mail_new', 1)) {
			return $payload;
		}

		// If a payment is required include the payment statement from the plugin
		$oldDetails = $payload->mailVariables['bookingDetails'];
		if ($payload->item->state == 3 || $payload->item->state == 4) {
			$payload->mailVariables['bookingDetails'] = $payload->mailVariables['bookingDetails']
				. '<br/>' . Booking::getPaymentStatementFromPlugin($payload->item);
		}

		// Send a mail to the booker
		$subject = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			\JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_SUBJECT'),
			null,
			$payload->mailVariables
		);
		$body    = trim(
			DPCalendarHelper::renderEvents(
				$payload->eventsWithTickets,
				DPCalendarHelper::getStringFromParams(
					'bookingsys_new_booking_mail',
					'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_BODY',
					$payload->mailParams
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

			$files = array();
			if ($payload->mailParams->get('booking_include_ics', 1)) {
				$icsFile = JPATH_ROOT . '/tmp/' . $payload->item->uid . '.ics';
				$content = Ical::createIcalFromEvents($payload->events, false);
				if (!$content || !\JFile::write($icsFile, $content)) {
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
				$this->mailer->Send();
				foreach ($files as $file) {
					\JFile::delete($file);
				}
			} catch (\Exception $e) {
				foreach ($files as $file) {
					\JFile::delete($file);
				}

				throw $e;
			}
		}

		$payload->mailVariables['bookingDetails'] = $oldDetails;

		return $payload;
	}
}
