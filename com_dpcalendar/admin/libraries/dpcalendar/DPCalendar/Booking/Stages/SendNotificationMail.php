<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use League\Pipeline\StageInterface;

class SendNotificationMail implements StageInterface
{
	/**
	 * @var Mail
	 */
	private $mailer = null;

	public function __construct(Mail $mailer)
	{
		$this->mailer = $mailer;
	}

	public function __invoke($payload)
	{
		// Abort when we are in the creation process
		if (in_array($payload->item->state, [0, 2])) {
			return $payload;
		}

		// Send the notification to the groups
		$subject = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_SUBJECT'),
			null,
			$payload->mailVariables
		);
		$body = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_BODY'),
			null,
			$payload->mailVariables
		);

		$calendarGroups = [];
		foreach ($payload->eventsWithTickets as $e) {
			$calendarGroups = array_merge($calendarGroups, DPCalendarHelper::getCalendar($e->catid)->params->get('notification_groups_book', []));
		}
		$emails = DPCalendarHelper::sendMail(
			$subject,
			$body,
			'notification_groups_book',
			$calendarGroups,
			$payload->mailParams->get('bookingsys_attendee_as_mail_from') ? $payload->item->email : null
		);

		if (!$payload->mailParams->get('booking_send_mail_author', 1)) {
			return $payload;
		}

		// Send to the authors of the events
		$authors = [];
		foreach ($payload->eventsWithTickets as $e) {
			// Ignore already sent out mails
			if (array_key_exists($e->created_by, $emails)) {
				continue;
			}

			$authors[$e->created_by] = $e->created_by;
		}

		foreach ($authors as $authorId) {
			$mailer = clone $this->mailer;

			if ($payload->mailParams->get('bookingsys_attendee_as_mail_from')) {
				$mailer->setFrom($payload->item->email);
			}

			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->IsHTML(true);
			$mailer->addRecipient(Factory::getUser($authorId)->email);
			try {
				$mailer->Send();
			} catch (\Exception $e) {
			}
		}

		return $payload;
	}
}
