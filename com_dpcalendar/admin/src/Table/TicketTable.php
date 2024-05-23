<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

class TicketTable extends BasicTable
{
	/** @var string */
	public $uid;

	/** @var string */
	public $name;

	/** @var ?string */
	public $created;

	/** @var float */
	public $latitude;

	/** @var float */
	public $longitude;

	/** @var float */
	public $price;

	protected string $tableName = 'dpcalendar_tickets';
	protected $_columnAlias     = ['published' => 'state'];

	public function store($updateNulls = false)
	{
		$result = parent::store($updateNulls);

		// Create the UID after store so we have the id of the booking
		if ($result && !$this->uid) {
			$this->uid = DPCalendarHelper::renderLayout('ticket.uid', ['ticket' => $this]);
			$this->getDatabase()->setQuery("update #__dpcalendar_tickets set uid='" . $this->uid . "' where id = " . $this->id);
			$this->getDatabase()->execute();
		}

		return $result;
	}

	public function check(): bool
	{
		if (!$this->getCurrentUser()->guest && empty($this->name)) {
			$this->name = $this->getCurrentUser()->name;
		}

		if (empty($this->id)) {
			$this->created = DPCalendarHelper::getDate()->toSql(false);
		}

		if (!$this->latitude) {
			$this->latitude = 0.0;
		}

		if (!$this->longitude) {
			$this->longitude = 0.0;
		}

		return true;
	}

	public function load($keys = null, $reset = true)
	{
		$return = parent::load($keys, $reset);

		if ($this->price == '0.00') {
			$this->price = 0;
		}

		return $return;
	}
}
