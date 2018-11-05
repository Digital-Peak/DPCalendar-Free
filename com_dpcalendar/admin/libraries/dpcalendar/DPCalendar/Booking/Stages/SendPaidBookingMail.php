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
use League\Pipeline\StageInterface;

class SendPaidBookingMail implements StageInterface
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
		if (!$payload->oldItem
			|| ($payload->oldItem->state != 3 && $payload->oldItem->state != 4)
			|| $payload->item->state == 1
			|| !$payload->mailParams->get('booking_send_mail_paid', 1)) {
			return $payload;
		}

		// We have a successful payment
		$subject = DPCalendarHelper::renderEvents(
			[],
			\JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_PAYED_SUBJECT'),
			null,
			$payload->mailVariables
		);
		$body    = DPCalendarHelper::renderEvents(
			[],
			DPCalendarHelper::getStringFromParams(
				'bookingsys_paid_booking_mail',
				'COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_PAYED_BODY',
				$payload->mailParams
			),
			null,
			$payload->mailVariables
		);

		$this->mailer->setSubject($subject);
		$this->mailer->setBody($body);
		$this->mailer->IsHTML(true);
		$this->mailer->addRecipient($payload->item->email);

		// Adding the invoice attachment
		$payload->mailParams->set('show_header', true);

		$files = [];
		if ($payload->mailParams->get('booking_include_invoice', 1)) {
			$fileName = Booking::createInvoice($payload->item, $payload->tickets, $payload->mailParams, true);
			if ($fileName) {
				$this->mailer->addAttachment($fileName);
				$files[] = $fileName;
			}
		}

		if ($payload->mailParams->get('booking_include_tickets', 1)) {
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

		return $payload;
	}
}
