<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class DPCalendarTableCountry extends JTable
{
	public function __construct(&$db)
	{
		parent::__construct('#__dpcalendar_countries', 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	public function store($updateNulls = false)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();
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

		// Attempt to store the user data.
		return parent::store($updateNulls);
	}

	public function check()
	{
		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_countries WHERE short_code = ' . $this->_db->Quote($this->short_code);
		$this->_db->setQuery($query);

		$xid = (int)$this->_db->loadResult();
		if ($xid && $xid != (int)$this->id) {
			$this->setError(JText::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_NAME'));

			return false;
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down > $this->_db->getNullDate() && $this->publish_down < $this->publish_up) {
			$this->setError(JText::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

			return false;
		}

		if (empty($this->modified)) {
			$this->modified = $this->getDbo()->getNullDate();
		}

		return true;
	}
}
