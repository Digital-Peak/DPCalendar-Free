<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Calendar;

use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;

class InternalCalendar extends Calendar
{
	private readonly CategoryNode $calendar;

	public function __construct(CategoryNode $calendar, User $user)
	{
		parent::__construct((string)$calendar->id, $calendar->title, $user);

		$this->calendar = $calendar;

		$color = str_replace('#', '', (string)$calendar->getParams()->get('color', '3366CC'));

		// Check if it is a valid color
		if ((\strlen($color) !== 6 && \strlen($color) !== 3) || !ctype_xdigit($color)) {
			$color = '3366CC';
		}

		$this->setColor($color);

		$this->setDescription($calendar->description ?: '');
		$this->setLevel($calendar->level);
		$this->setAccess($calendar->access);
		$this->setParams(new Registry($calendar->params));
	}

	public function getChildren(bool $recursive = true): array
	{
		$childs = [];
		foreach ($this->calendar->getChildren($recursive) as $calendar) {
			$childs[] = new InternalCalendar($calendar, $this->getCurrentUser());
		}

		return $childs;
	}
}
