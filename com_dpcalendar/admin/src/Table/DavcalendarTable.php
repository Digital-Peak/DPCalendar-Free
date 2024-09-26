<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

\defined('_JEXEC') or die();

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\CurrentUserTrait;

class DavcalendarTable extends BasicTable
{
	use CurrentUserTrait;

	/** @var string */
	public $calendarid;

	/** @var int */
	public $access;

	/** @var string */
	public $displayname;

	/** @var string */
	public $uri;

	/** @var string */
	public $principaluri;

	protected string $tableName = 'dpcalendar_caldav_calendarinstances';

	public function store($updateNulls = false)
	{
		if (!$this->calendarid) {
			$obj             = new \stdClass();
			$obj->components = 'VEVENT,VTODO,VJOURNAL';
			$this->getDatabase()->insertObject('#__dpcalendar_caldav_calendars', $obj);
			$this->calendarid = $this->getDatabase()->insertid();
			$this->access     = 1;
		}

		return parent::store($updateNulls);
	}

	public function check(): bool
	{
		// Check for valid name
		if (trim($this->displayname) === '') {
			throw new \Exception(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_TITLE'));
		}

		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_caldav_calendarinstances WHERE uri = ' . $this->getDatabase()->quote($this->uri ?: '') .
			" and principaluri = 'principals/" . $this->getCurrentUser()->username . "'";
		$this->getDatabase()->setQuery($query);

		$xid = (int)$this->getDatabase()->loadResult();
		if ($xid && $xid != (int)$this->id) {
			throw new \Exception(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_NAME'));
		}

		if (empty($this->uri)) {
			$this->uri = $this->displayname;
		}

		$this->uri = ApplicationHelper::stringURLSafe($this->uri);
		if (trim(str_replace('-', '', $this->uri)) === '') {
			$this->uri = Factory::getDate()->format("Y-m-d-H-i-s");
		}

		$this->principaluri = 'principals/' . $this->getCurrentUser()->username;

		return true;
	}
}
