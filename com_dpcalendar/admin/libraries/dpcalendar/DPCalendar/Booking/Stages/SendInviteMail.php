<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\Ical;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\Mail;
use League\Pipeline\StageInterface;

class SendInviteMail implements StageInterface
{
	/**
	 * @var Mail
	 */
	private $mailer;

	public function __construct(Mail $mailer)
	{
		$this->mailer = $mailer;
	}

	public function __invoke($payload)
	{
		// Never send a mail when we have been invited before
		if ($payload->oldItem && $payload->oldItem->state == 5) {
			return $payload;
		}

		// Never send a mail when not invited state
		if ($payload->item->state != 5) {
			return $payload;
		}

		// Send a mail to the booker
		$subject = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			$payload->language->_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_INVITE_SUBJECT'),
			null,
			$payload->mailVariables
		);

		$body = trim(
			DPCalendarHelper::renderEvents(
				$payload->eventsWithTickets,
				$payload->language->_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_USER_INVITE_BODY'),
				null,
				$payload->mailVariables
			)
		);

		if ($body !== '' && $body !== '0') {
			$this->mailer->setSubject($subject);
			$this->mailer->setBody($body);
			$this->mailer->IsHTML(true);
			$this->mailer->addRecipient($payload->item->email);

			$files = [];
			if ($payload->mailParams->get('booking_include_ics', 1)) {
				$icsFile = JPATH_ROOT . '/tmp/' . $payload->item->uid . '.ics';
				$content = Ical::createIcalFromEvents($payload->eventsWithTickets, false, true);
				if (!$content || !File::write($icsFile, $content)) {
					$icsFile = null;
				} else {
					$this->mailer->addAttachment($icsFile);
					$files[] = $icsFile;
				}
			}

			try {
				$this->mailer->Send();
				foreach ($files as $file) {
					File::delete($file);
				}
			} catch (\Exception $e) {
				foreach ($files as $file) {
					File::delete($file);
				}

				if (!$e instanceof MailDisabledException) {
					throw $e;
				}
			}
		}

		return $payload;
	}
}
