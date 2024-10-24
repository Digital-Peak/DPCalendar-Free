<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Language\Text;

class CountryTable extends BasicTable
{
	/** @var ?string */
	public $modified;

	/** @var int */
	public $modified_by;

	/** @var ?string */
	public $created;

	/** @var int */
	public $created_by;

	/** @var string */
	public $short_code;

	/** @var ?string */
	public $publish_down;

	/** @var ?string */
	public $publish_up;

	/** @var ?string */
	public $checked_out_time;

	protected string $tableName = 'dpcalendar_countries';
	protected $_columnAlias     = ['published' => 'state'];

	public function store($updateNulls = false)
	{
		$date = DPCalendarHelper::getDate();
		$user = $this->getCurrentUser();
		if ($this->id !== 0) {
			// Existing item
			$this->modified    = $date->toSql();
			$this->modified_by = $user->id;
		} else {
			if ((int)$this->created === 0) {
				$this->created = $date->toSql();
			}
			if (empty($this->created_by)) {
				$this->created_by = $user->id;
			}
		}

		// Attempt to store the user data.
		return parent::store($updateNulls);
	}

	public function check(): bool
	{
		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_countries WHERE short_code = ' . $this->getDatabase()->Quote($this->short_code);
		$this->getDatabase()->setQuery($query);

		$xid = (int)$this->getDatabase()->loadResult();
		if ($xid && $xid != (int)$this->id) {
			throw new \Exception(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_NAME'));
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down && $this->publish_down < $this->publish_up) {
			throw new \Exception(Text::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));
		}

		if (empty($this->created) || $this->created === $this->getDatabase()->getNullDate()) {
			$this->created = null;
		}
		if (empty($this->modified) || $this->modified === $this->getDatabase()->getNullDate()) {
			$this->modified = null;
		}
		if (empty($this->publish_up) || $this->publish_up === $this->getDatabase()->getNullDate()) {
			$this->publish_up = null;
		}
		if (empty($this->publish_down) || $this->publish_down === $this->getDatabase()->getNullDate()) {
			$this->publish_down = null;
		}
		if (empty($this->checked_out_time) || $this->checked_out_time === $this->getDatabase()->getNullDate()) {
			$this->checked_out_time = null;
		}

		return true;
	}
}
