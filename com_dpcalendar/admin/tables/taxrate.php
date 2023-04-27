<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

class DPCalendarTableTaxrate extends Table
{
	public function __construct(&$db)
	{
		parent::__construct('#__dpcalendar_taxrates', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function bind($array, $ignore = '')
	{
		if (isset($array['countries']) && is_array($array['countries'])) {
			$registry = new Registry();
			$registry->loadArray($array['countries']);
			$array['countries'] = (string)$registry;
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

		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_taxrates WHERE title = ' . $this->_db->Quote($this->title);
		$this->_db->setQuery($query);

		$xid = (int)$this->_db->loadResult();
		if ($xid && $xid != (int)$this->id) {
			$this->setError(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_NAME'));

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

		return true;
	}
}
