<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2026 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Mail;

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\CMS\User\UserFactoryAwareTrait;

trait NotificationMailTrait
{
	use UserFactoryAwareTrait;
	use CurrentUserTrait;

	/**
	 * Sends a mail to all users of the given component parameter groups.
	 * The user objects are returned where a mail is sent to.
	 */
	public function sendNotificationMail(string $action, array $items, ?array $templateData = [], ?string $customSender = ''): array
	{
		$notifications   = array_values((array)DPCalendarHelper::getComponentParameter('component_notifications', new \stdClass()));
		$mergedCalendars = [];
		foreach ($items as $item) {
			if (!is_numeric($item->catid ?? '')) {
				continue;
			}

			$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($item->catid);
			if (!$calendar instanceof CalendarInterface) {
				continue;
			}

			if (\array_key_exists($calendar->getId(), $mergedCalendars)) {
				continue;
			}

			$mergedCalendars[$calendar->getId()] = $calendar;
			$notifications                       = array_merge(
				$notifications,
				array_values((array)$calendar->getParams()->get('component_notifications', new \stdClass()))
			);
		}

		$userMails = [];
		foreach ($notifications as $notification) {
			// Calendar notifications are array
			$notification = (object)$notification;

			[$id]   = explode('.', $action);
			$mailer = new MustacheMailTemplate($action, [$id . 's' => $items] + $templateData);

			if ($customSender) {
				$mailer->getMailerInstance()->setSender($customSender);
			}

			if ($notification->action !== $mailer->getTemplateId()) {
				continue;
			}

			$groups = $notification->groups ?? [];
			if (!\is_array($groups)) {
				$groups = [$groups];
			}

			$groups = array_unique($groups);

			$users = [];
			foreach ($groups as $groupId) {
				$users = array_merge($users, Access::getUsersByGroup($groupId));
			}

			$currentUser = Factory::getApplication()->getIdentity();
			$users       = array_unique($users);
			foreach ($users as $userId) {
				$user = $this->getUserFactory()->loadUserById($userId);
				if (!$currentUser || $user->id == $currentUser->id || !$user->email) {
					continue;
				}

				$mailer->setCurrentUser($user);
				$mailer->setRecipient($user->email);

				// Set the user in template data for current user
				$mailer->addTemplateData(['user' => $currentUser->name]);

				$mailer->send();
				$userMails[$userId] = $user;
			}

			if (empty($notification->author)) {
				continue;
			}

			$authors = [];
			foreach ($items as $item) {
				if (empty($item->created_by)) {
					continue;
				}

				if (($this->getCurrentUser()->id != $item->created_by && $notification->author == 1) || $notification->author == 2) {
					$authors[] = $item->created_by;
				}
			}

			// Check if authors should get a mail
			if ($authors === []) {
				continue;
			}

			$authors = array_unique($authors);

			$mailer = new MustacheMailTemplate(
				str_replace('.', '.author.', $action),
				$mailer->getTemplateData()
			);

			if ($customSender) {
				$mailer->getMailerInstance()->setSender($customSender);
			}

			// Loop over the authors to send the notification
			foreach ($authors as $author) {
				// Load the user
				$u = $this->getUserFactory()->loadUserById($author);
				if (!$u->id || !$u->email) {
					continue;
				}

				// Send the mail
				$mailer->setCurrentUser($u);
				$mailer->setRecipient($u->email);

				// Set the user in template data for current user
				if ($currentUser) {
					$mailer->addTemplateData(['user' => $currentUser->name]);
				}

				$mailer->send();
			}
		}

		return $userMails;
	}
}
