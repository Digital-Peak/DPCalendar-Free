<?php

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarTableDavcalendar extends Table
{
	public function __construct(&$db)
	{
		parent::__construct('#__dpcalendar_caldav_calendarinstances', 'id', $db);
	}

	public function store($updateNulls = false)
	{
		if (!$this->calendarid) {
			$obj             = new stdClass();
			$obj->components = 'VEVENT,VTODO,VJOURNAL';
			$this->getDbo()->insertObject('#__dpcalendar_caldav_calendars', $obj);
			$this->calendarid = $this->getDbo()->insertid();
			$this->access     = 1;
		}

		return parent::store($updateNulls);
	}

	public function check()
	{
		// Check for valid name
		if (trim($this->displayname) == '') {
			$this->setError(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_TITLE'));

			return false;
		}

		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_caldav_calendarinstances WHERE uri = ' . $this->_db->Quote($this->uri ?: '') .
			" and principaluri = 'principals/" . Factory::getUser()->username . "'";
		$this->_db->setQuery($query);

		$xid = (int)$this->_db->loadResult();
		if ($xid && $xid != (int)$this->id) {
			$this->setError(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_NAME'));

			return false;
		}

		if (empty($this->uri)) {
			$this->uri = $this->displayname;
		}
		$this->uri = ApplicationHelper::stringURLSafe($this->uri);
		if (trim(str_replace('-', '', $this->uri)) == '') {
			$this->uri = Factory::getDate()->format("Y-m-d-H-i-s");
		}

		$this->principaluri = 'principals/' . Factory::getUser()->username;

		return true;
	}
}
