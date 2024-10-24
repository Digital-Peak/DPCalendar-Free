<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Calendar;

use Joomla\CMS\User\CurrentUserTrait;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;

class Calendar extends \stdClass implements CalendarInterface
{
	use CurrentUserTrait;

	/**
	 * Placeholder for plugin events.
	 */
	public string $text = '';

	private string $id;
	private string $title       ;
	private string $description = '';
	private string $icalUrl     = '';
	private int $level          = 1;
	private string $color       = '3366CC';
	private int $access         = 1;
	private int $accessContent  = 1;
	private bool $canCreate     = false;
	private bool $canEdit       = false;
	private bool $canEditOwn    = false;
	private bool $canDelete     = false;
	private bool $canBook       = false;
	private Registry $params;

	protected string $assetName = 'category';

	public function __construct(string $id, string $title, User $user)
	{
		$this->setCurrentUser($user);

		$this->setId($id);
		$this->setTitle($title);
		$this->setParams(new Registry());

		$asset = 'com_dpcalendar' . ($this->getId() !== 'root' ? '.' . $this->assetName . '.' . $this->getId() : '');
		$this->setCanCreate($user->authorise('core.create', $asset));
		$this->setCanEdit($user->authorise('core.edit', $asset));
		$this->setCanEditOwn($user->authorise('core.edit.own', $asset));
		$this->setCanDelete($user->authorise('core.delete', $asset));
		$this->setCanBook($user->authorise('dpcalendar.book', $asset));

		if (empty($user->id) || !$this->canEditOwn()) {
			return;
		}

		/*
		if ($user->id != ($calendarData->created_user_id ?? 0)) {
			return;
		}

		$this->setCanEdit(true);
		*/
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setId(string $id): void
	{
		$this->id = $id;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): void
	{
		$this->title = $title;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getIcalUrl(): string
	{
		return $this->icalUrl;
	}

	public function setIcalUrl(string $url): void
	{
		$this->icalUrl = $url;
	}

	public function getLevel(): int
	{
		return $this->level;
	}

	public function setLevel(int $level): void
	{
		$this->level = $level;
	}

	public function getColor(): string
	{
		return $this->color;
	}

	public function setColor(string $color): void
	{
		$this->color = str_replace('#', '', $color);
	}

	public function getAccess(): int
	{
		return $this->access;
	}

	public function setAccess(int $access): void
	{
		$this->access = $access;
	}

	public function getAccessContent(): int
	{
		return $this->accessContent;
	}

	public function setAccessContent(int $accessContent): void
	{
		$this->accessContent = $accessContent;
	}

	public function canCreate(): bool
	{
		return $this->canCreate;
	}

	public function setCanCreate(bool $canCreate): void
	{
		$this->canCreate = $canCreate;
	}

	public function canEdit(): bool
	{
		return $this->canEdit;
	}

	public function setCanEdit(bool $canEdit): void
	{
		$this->canEdit = $canEdit;
	}

	public function canEditOwn(): bool
	{
		return $this->canEditOwn;
	}

	public function setCanEditOwn(bool $canEditOwn): void
	{
		$this->canEditOwn = $canEditOwn;
	}

	public function canDelete(): bool
	{
		return $this->canDelete;
	}

	public function setCanDelete(bool $canDelete): void
	{
		$this->canDelete = $canDelete;
	}

	public function canBook(): bool
	{
		return $this->canBook;
	}

	public function setCanBook(bool $canBook): void
	{
		$this->canBook = $canBook;
	}

	public function getParams(): Registry
	{
		return $this->params;
	}

	public function setParams(Registry $params): void
	{
		$this->params = $params;
	}

	public function getChildren(bool $recursive = true): array
	{
		return [];
	}

	public function __get(string $name): mixed
	{
		return $this->$name;
	}

	public function __set(string $name, mixed $value): void
	{
		$this->$name = $value;
	}
}
