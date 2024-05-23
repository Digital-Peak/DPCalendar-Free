<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Calendar;

use Joomla\Registry\Registry;

interface CalendarInterface
{
	public function getId(): string;
	public function getTitle(): string;
	public function getDescription(): string;
	public function getIcalUrl(): string;
	public function getLevel(): int;
	public function getColor(): string;
	public function getAccess(): int;
	public function getAccessContent(): int;
	public function canCreate(): bool;
	public function canEdit(): bool;
	public function canEditOwn(): bool;
	public function canDelete(): bool;
	public function canBook(): bool;
	public function getParams(): Registry;

	/**
	 * @return CalendarInterface[]
	 */
	public function getChildren(bool $recursive = true): array;
}
