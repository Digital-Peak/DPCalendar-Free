<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

class CouponTable extends BasicTable
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
	public $code;

	/** @var string */
	public $title;

	/** @var ?string */
	public $publish_down;

	/** @var ?string */
	public $publish_up;

	/** @var ?string */
	public $checked_out_time;

	/** @var ?string */
	public $limit;

	/** @var ?string */
	public $checked_out;

	/** @var ?string */
	public $emails;

	protected string $tableName = 'dpcalendar_coupons';
	protected $_columnAlias     = ['published' => 'state'];

	public function bind($array, $ignore = '')
	{
		if (isset($array['params']) && is_array($array['params'])) {
			$registry = new Registry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}

		if (isset($array['calendars']) && is_array($array['calendars'])) {
			$array['calendars'] = implode(',', $array['calendars']);
		}
		if (isset($array['users']) && is_array($array['users'])) {
			$array['users'] = implode(',', $array['users']);
		}

		return parent::bind($array, $ignore);
	}

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

		// Verify that the alias is unique
		$table = new self($this->getDbo());
		if ($table->load(['code' => $this->code]) && ($table->id != $this->id || $this->id == 0)) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ERROR_UNIQUE_ALIAS_CODE') . ': ' . $table->code);
		}

		// Attempt to store the user data.
		return parent::store($updateNulls);
	}

	public function check(): bool
	{
		// Check for valid name
		if (trim($this->title) === '') {
			throw new \Exception(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_TITLE'));
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

		if (empty($this->limit)) {
			$this->limit = null;
		}

		if (empty($this->checked_out)) {
			$this->checked_out = null;
		}

		if (empty($this->checked_out_time)) {
			$this->checked_out_time = null;
		}

		$this->emails = $this->emails ? trim($this->emails) : null;

		return true;
	}
}
