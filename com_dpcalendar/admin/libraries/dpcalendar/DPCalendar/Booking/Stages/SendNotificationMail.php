<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use League\Pipeline\StageInterface;

class SendNotificationMail implements StageInterface
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
		// Send the notification to the groups
		$subject = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			\JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_SUBJECT'),
			null,
			$payload->mailVariables
		);
		$body    = DPCalendarHelper::renderEvents(
			$payload->eventsWithTickets,
			\JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_BOOK_BODY'), null,
			$payload->mailVariables
		);

		DPCalendarHelper::sendMail($subject, $body, 'notification_groups_book');

		if ($payload->mailParams->get('booking_send_mail_author', 1)) {
			// Send to the authors of the events
			$authors = array();
			foreach ($payload->eventsWithTickets as $e) {
				$authors[$e->created_by] = $e->created_by;
			}
			foreach ($authors as $authorId) {
				$mailer = clone $this->mailer;
				$mailer->setSubject($subject);
				$mailer->setBody($body);
				$mailer->IsHTML(true);
				$mailer->addRecipient(\JFactory::getUser($authorId)->email);
				try {
					$mailer->Send();
				} catch (\Exception $e) {
				}
			}
		}

		return $payload;
	}
}
