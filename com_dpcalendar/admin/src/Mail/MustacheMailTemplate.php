<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2026 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Mail;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\MailerInterface;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\CMS\User\User;

class MustacheMailTemplate extends MailTemplate
{
	use CurrentUserTrait;

	private CMSApplicationInterface $application;

	public function __construct(string $templateId, array $templateData)
	{
		parent::__construct((str_starts_with($templateId, 'plg_') ? '' : 'com_dpcalendar.') . $templateId, '');

		$this->application = Factory::getApplication();

		if ($user = $this->application->getIdentity()) {
			$this->setCurrentUser($user);
		}

		$templateData['sitename'] = $this->application->get('sitename');
		$this->addTemplateData($templateData);
	}

	/**
	 * Sends out the mail and does an attachment cleanup when defined. When the mailer is used to send
	 * multiple mails to different senders, then this is useful to cleanup attachments after send out.
	 */
	public function sendWithAttachments(?bool $cleanup = true): bool
	{
		$this->application->triggerEvent('onDPCalendarBeforeSendMail', [$this->template_id, $this->getMailerInstance(), $this->data]);

		$config    = ComponentHelper::getParams('com_mails');
		$mailStyle = $config->get('mail_style');
		if ($mailStyle !== 'html') {
			$config->set('mail_style', 'html');
		}

		$success = true;
		try {
			$success = parent::send();
		} catch (MailDisabledException) {
		} finally {
			$config->set('mail_style', $mailStyle);

			if ($cleanup) {
				$this->cleanupAttachments();
			}
		}

		$this->application->triggerEvent('onDPCalendarAfterSendMail', [$this->template_id, $this->getMailerInstance(), $this->data]);

		return $success;
	}

	public function send(): bool
	{
		return $this->sendWithAttachments(true);
	}

	public function getMailerInstance(): MailerInterface
	{
		return $this->mailer;
	}

	public function getTemplateId(): string
	{
		return $this->template_id;
	}

	// @phpstan-ignore-next-line
	public function getTemplateData($plain = false): array
	{
		return $plain ? $this->plain_data : $this->data;
	}

	public function setRecipient(string $email): void
	{
		$this->recipients = [];
		$this->mailer->clearAllRecipients();
		parent::addRecipient($email);
	}

	public function setCurrentUser(User $user): void
	{
		$this->currentUser = $user;

		$this->language = $user->getParam('language', $user->getParam('admin_language', $this->application->getLanguage()->getTag()));

		$this->addTemplateData(['user' => $user->name]);
	}

	public function cleanupAttachments(): void
	{
		$tmpPath = (string)$this->application->get('tmp_path', JPATH_ROOT . '/tmp');
		foreach ($this->attachments as $file) {
			if (str_starts_with((string)$file->file, $tmpPath) && file_exists($file->file)) {
				unlink($file->file);
			}
		}

		$this->attachments = [];
		$this->mailer->clearAttachments();
	}

	protected function replaceTags($text, $tags, $isHtml = false): string
	{
		$data   = $tags;
		$events = $data['events'] ?? [];
		unset($data['events']);

		return DPCalendarHelper::renderEvents(
			$events,
			$text,
			null,
			$tags
		);
	}
}
