<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2026 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Mail;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\MailerInterface;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\CMS\User\User;

class MustacheMailTemplate extends MailTemplate
{
	use CurrentUserTrait;

	public function __construct(string $templateId, array $templateData)
	{
		parent::__construct((str_starts_with($templateId, 'plg_') ? '' : 'com_dpcalendar.') . $templateId, '');

		if ($user = Factory::getApplication()->getIdentity()) {
			$this->setCurrentUser($user);
		}

		$templateData['sitename'] = Factory::getApplication()->get('sitename');
		$this->addTemplateData($templateData);
	}

	public function send(): bool
	{
		$app = Factory::getApplication();

		$app->triggerEvent('onDPCalendarBeforeSendMail', [$this->template_id, $this->getMailerInstance(), $this->data]);

		if ($this->language && $this->language !== $app->getLanguage()->getTag()) {
			$lang = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($this->language, $app->get('debug_lang'));

			$extension = explode('.', $this->template_id, 2)[0];
			switch (substr($extension, 0, 3)) {
				case 'plg':
					$parts = explode('_', $extension, 3);
					if (\count($parts) > 2) {
						$lang->load($extension, JPATH_PLUGINS . '/' . $parts[1] . '/' . $parts[2], $this->language);
					}
					break;
				case 'com':
				default:
					$lang->load($extension, JPATH_ADMINISTRATOR . '/components/' . $extension, $this->language);
					break;
			}

			// @phpstan-ignore-next-line
			Factory::$language = $lang;
		}

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

			if ($this->language !== $app->getLanguage()->getTag()) {
				// @phpstan-ignore-next-line
				Factory::$language = $app->getLanguage();
			}

			foreach ($this->attachments as $file) {
				if (str_starts_with((string)$file->file, (string)$app->get('tmp_path', JPATH_ROOT . '/tmp')) && file_exists($file->file)) {
					unlink($file->file);
				}
			}

			$this->attachments = [];
			$this->mailer->clearAttachments();
		}

		$app->triggerEvent('onDPCalendarAfterSendMail', [$this->template_id, $this->getMailerInstance(), $this->data]);

		return $success;
	}

	public function getMailerInstance(): MailerInterface
	{
		return $this->mailer;
	}

	public function getTemplateId(): string
	{
		return $this->template_id;
	}

	/// @phpstan-ignore-next-line
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
		if (!$user->id) {
			return;
		}

		$this->currentUser = $user;

		$this->language = $user->getParam('language', $user->getParam('admin_language', Factory::getApplication()->getLanguage()->getTag()));

		$this->addTemplateData(['user' => $user->name]);
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
