<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerInterface;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;
use League\Pipeline\StageInterface;

class SendNotificationMail implements StageInterface, UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;

	public function __construct(private readonly MailerInterface $mailer, UserFactoryInterface $factory)
	{
		$this->setUserFactory($factory);
	}

	public function __invoke($payload)
	{
		// Abort when we are in the creation process
		if (\in_array($payload->item->state, [0, 2])) {
			return $payload;
		}

		// When the notification comes from a confirmation step and now is on hold and the invoice is set to 1, then no notification should be triggered,
		// as we did already
		if ($payload->item->price
			&& $payload->oldItem
			&& $payload->oldItem->state == 3
			&& $payload->item->state == 4
			&& $payload->item->invoice == 1) {
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
			$calendar       = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($e->catid);
			$calendarGroups = array_merge($calendarGroups, $calendar instanceof CalendarInterface ? $calendar->getParams()->get('notification_groups_book', []) : []);
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
			if (\array_key_exists($e->created_by, $emails)) {
				continue;
			}

			if ((int)$e->created_by === 0) {
				continue;
			}

			$authors[$e->created_by] = $e->created_by;
		}

		foreach ($authors as $authorId) {
			$mailer = $this->mailer;

			if ($payload->mailParams->get('bookingsys_attendee_as_mail_from')) {
				$mailer->setSender($payload->item->email);
			}

			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->addRecipient($this->getUserFactory()->loadUserById($authorId)->email);
			if ($mailer instanceof Mail) {
				$mailer->IsHTML(true);
			}
			try {
				$mailer->Send();
			} catch (\Exception) {
			}
		}

		return $payload;
	}
}
