<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

class DPCalendarTableCoupon extends Table
{
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
			if (!(int)$this->created) {
				$this->created = $date->toSql();
			}
			if (empty($this->created_by)) {
				$this->created_by = $user->get('id');
			}
		}

		// Set publish_up to null date if not set
		if (!$this->publish_up) {
			$this->publish_up = $this->_db->getNullDate();
		}

		// Set publish_down to null date if not set
		if (!$this->publish_down) {
			$this->publish_down = $this->_db->getNullDate();
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
		if ($this->publish_down > $this->_db->getNullDate() && $this->publish_down < $this->publish_up) {
			$this->setError(Text::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

			return false;
		}

		if (empty($this->modified)) {
			$this->modified = $this->getDbo()->getNullDate();
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
