<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Calendar;

interface ExternalCalendarInterface extends CalendarInterface
{
	public function getPluginName(): string;
	public function getSystemName(): string;
	public function getAccessContent(): int;
	public function forceColor(): bool;
	public function getSyncDate(): ?string;
	public function getSyncToken(): ?string;
}
