<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerInterface;

class SendInviteMail implements StageInterface
{
	public function __construct(private readonly MailerInterface $mailer)
	{
	}

	public function __invoke(\stdClass $payload): \stdClass
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
			$this->mailer->addRecipient($payload->item->email);
			if ($this->mailer instanceof Mail) {
				$this->mailer->IsHTML(true);
			}

			$files = [];
			if ($payload->mailParams->get('booking_include_ics', 1)) {
				$icsFile = JPATH_ROOT . '/tmp/' . $payload->item->uid . '.ics';
				$content = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ical', 'Administrator')->createIcalFromEvents($payload->eventsWithTickets, false, true);
				$result  = file_put_contents($icsFile, $content);
				if (!$content || $result === 0 || $result === false) {
					$icsFile = null;
				} else {
					$this->mailer->addAttachment($icsFile);
					$files[] = $icsFile;
				}
			}

			try {
				$this->mailer->Send();
				foreach ($files as $file) {
					unlink($file);
				}
			} catch (\Exception $e) {
				foreach ($files as $file) {
					unlink($file);
				}

				if (!$e instanceof MailDisabledException) {
					throw $e;
				}
			}
		}

		return $payload;
	}
}
