<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Calendar;

use Joomla\CMS\User\User;

class ExternalCalendar extends Calendar implements ExternalCalendarInterface
{
	private string $pluginName  = '';
	private string $systemName  = '';
	private int $access_content = 1;
	private ?string $syncDate   = null;
	private ?string $syncToken  = null;
	private bool $forceColor    = true;

	protected string $assetName = 'extcalendar';

	public function __construct(string $id, string $title, User $user)
	{
		parent::__construct($id, $title, $user);

		$this->setCanCreate($this->canCreate() && (bool)$this->getParams()->get('action-create', 'false'));
		$this->setCanEdit($this->canEdit() && (bool)$this->getParams()->get('action-edit', 'false'));
		$this->setCanDelete($this->canDelete() && (bool)$this->getParams()->get('action-delete', 'false'));
	}

	public function getPluginName(): string
	{
		return $this->pluginName;
	}

	public function setPluginName(string $pluginName): void
	{
		$this->pluginName = $pluginName;
	}

	public function getSystemName(): string
	{
		return $this->systemName;
	}

	public function setSystemName(string $systemName): void
	{
		$this->systemName = $systemName;
	}

	public function getAccessContent(): int
	{
		return $this->access_content;
	}

	public function setAccessContent(int $access): void
	{
		$this->access_content = $access;
	}

	public function getSyncDate(): ?string
	{
		return $this->syncDate;
	}

	public function setSyncDate(?string $syncDate): void
	{
		$this->syncDate = $syncDate;
	}

	public function getSyncToken(): ?string
	{
		return $this->syncToken;
	}

	public function setSyncToken(?string $syncToken): void
	{
		if ($syncToken === '') {
			$syncToken = null;
		}

		$this->syncToken = $syncToken;
	}

	public function forceColor(): bool
	{
		return $this->forceColor;
	}

	public function setForceColor(bool $forceColor): void
	{
		$this->forceColor = $forceColor;
	}
}
