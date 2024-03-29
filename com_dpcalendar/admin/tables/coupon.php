<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

class DPCalendarTableCoupon extends Table
{
	public $id;
	public $modified;
	public $modified_by;
	public $created;
	public $created_by;
	public $code;
	public $title;
	public $publish_down;
	public $publish_up;
	public $checked_out_time;
	public $limit;
	public $checked_out;
	/**
	 * @var string|null
	 */
	public $emails;
	public function __construct(&$db)
	{
		parent::__construct('#__dpcalendar_coupons', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

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
		$user = Factory::getUser();
		if ($this->id) {
			// Existing item
			$this->modified    = $date->toSql();
			$this->modified_by = $user->get('id');
		} else {
			if ((int)$this->created === 0) {
				$this->created = $date->toSql();
			}
			if (empty($this->created_by)) {
				$this->created_by = $user->get('id');
			}
		}

		// Verify that the alias is unique
		$table = Table::getInstance('Coupon', 'DPCalendarTable');
		if ($table->load(['code' => $this->code]) && ($table->id != $this->id || $this->id == 0)) {
			$this->setError(Text::_('COM_DPCALENDAR_ERROR_UNIQUE_ALIAS_CODE') . ': ' . $table->code);

			return false;
		}

		// Attempt to store the user data.
		return parent::store($updateNulls);
	}

	public function check()
	{
		// Check for valid name
		if (trim($this->title) == '') {
			$this->setError(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_TITLE'));

			return false;
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down && $this->publish_down < $this->publish_up) {
			$this->setError(Text::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

			return false;
		}

		if (empty($this->created) || $this->created === $this->getDbo()->getNullDate()) {
			$this->created = null;
		}
		if (empty($this->modified) || $this->modified === $this->getDbo()->getNullDate()) {
			$this->modified = null;
		}
		if (empty($this->publish_up) || $this->publish_up === $this->getDbo()->getNullDate()) {
			$this->publish_up = null;
		}
		if (empty($this->publish_down) || $this->publish_down === $this->getDbo()->getNullDate()) {
			$this->publish_down = null;
		}
		if (empty($this->checked_out_time) || $this->checked_out_time === $this->getDbo()->getNullDate()) {
			$this->checked_out_time = null;
		}

		if ($this->limit === '') {
			$this->limit = null;
		}

		if ($this->checked_out === '') {
			$this->checked_out = null;
		}

		if ($this->checked_out_time === '') {
			$this->checked_out_time = null;
		}

		$this->emails = $this->emails ? trim($this->emails) : null;

		return true;
	}
}
