<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Mail\MustacheMailTemplate;
use DigitalPeak\Component\DPCalendar\Administrator\Mail\NotificationMailTrait;
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;

class SendNotificationMail implements StageInterface, UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;
	use NotificationMailTrait;

	public function __construct(UserFactoryInterface $factory)
	{
		$this->setUserFactory($factory);
	}

	public function __invoke(\stdClass $payload): \stdClass
	{
		if (!$payload->oldItem) {
			$this->sendNotificationMail(
				'booking.create',
				[$payload->item],
				['events' => $payload->eventsWithTickets] + $payload->mailVariables,
				$payload->mailParams->get('bookingsys_attendee_as_mail_from') ? $payload->item->email : ''
			);
		}

		// Abort when we are in the creation process
		if (\in_array($payload->item->state, [0, 2]) || empty($payload->eventsWithTickets)) {
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

		$emails = $this->sendNotificationMail(
			'booking.update',
			[$payload->item],
			['events' => $payload->eventsWithTickets] + $payload->mailVariables,
			$payload->mailParams->get('bookingsys_attendee_as_mail_from') ? $payload->item->email : ''
		);

		if (!$payload->mailParams->get('booking_send_mail_author', 1)) {
			return $payload;
		}

		$mailer = new MustacheMailTemplate('booking.update', ['events' => $payload->eventsWithTickets] + $payload->mailVariables);
		$mailer->setCurrentUser($this->getCurrentUser());

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
			$author = $this->getUserFactory()->loadUserById($authorId);
			$mailer->setRecipient($author->email);
			$mailer->send();
		}

		return $payload;
	}
}
