<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Mail\MustacheMailTemplate;
use DigitalPeak\Component\DPCalendar\Administrator\Pipeline\StageInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\User\UserFactoryInterface;

class SendInviteMail implements StageInterface
{
	use UserFactoryAwareTrait;

	public function __construct(UserFactoryInterface $factory)
	{
		$this->setUserFactory($factory);
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

		$mailer = new MustacheMailTemplate('booking.user.invite', ['events' => $payload->eventsWithTickets] + $payload->mailVariables);
		$mailer->setCurrentUser($this->getUserFactory()->loadUserById($payload->item->user_id));
		$mailer->setRecipient($payload->item->email);

		if ($payload->mailParams->get('booking_include_ics', 1)) {
			$content = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ical', 'Administrator')->createIcalFromEvents($payload->eventsWithTickets, false, true);
			if ($content) {
				$mailer->addAttachment($payload->item->uid . '.ics', $content);
			}
		}

		$mailer->send();

		return $payload;
	}
}
