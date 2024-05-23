<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

class BookingTable extends BasicTable
{
	/** @var ?string */
	public $uid;

	/** @var int */
	public $user_id;

	/** @var string */
	public $email;

	/** @var string */
	public $name;

	/** @var string */
	public $book_date;

	/** @var float */
	public $price;

	/** @var int */
	public $coupon_id;

	/** @var int */
	public $coupon_rate;

	/** @var int */
	public $tax;

	/** @var int */
	public $tax_rate;

	/** @var string */
	public $txn_type;

	/** @var string */
	public $txn_currency;

	/** @var string */
	public $payer_id;

	/** @var string */
	public $payer_email;

	/** @var float */
	public $latitude;

	/** @var float */
	public $longitude;

	/** @var string */
	public $transaction_id;

	/** @var string */
	public $token;

	protected string $tableName = 'dpcalendar_bookings';
	protected $_columnAlias     = ['published' => 'state'];

	public function store($updateNulls = false)
	{
		$rebuildUID = !$this->uid;

		// Ensure a uid is set as we need one in the database structure
		if (!$this->uid) {
			$this->uid = (string)random_int(0, mt_getrandmax());
		}

		$result = parent::store($updateNulls);

		// Create the UID after store so we have the id of the booking
		if ($result && $rebuildUID) {
			$this->uid = DPCalendarHelper::renderLayout('booking.uid', ['booking' => $this]);
			$this->getDatabase()->setQuery("update #__dpcalendar_bookings set uid='" . $this->uid . "' where id = " . $this->id);
			$this->getDatabase()->execute();
		}

		return $result;
	}

	public function check(): bool
	{
		$fillDefault = !$this->getCurrentUser()->guest && Factory::getApplication()->isClient('site');
		if ($fillDefault && empty($this->user_id) && empty($this->id)) {
			$this->user_id = $this->getCurrentUser()->id;
		}

		if ($fillDefault && empty($this->email)) {
			$this->email = $this->getCurrentUser()->email;
		}

		if ($fillDefault && empty($this->name)) {
			$this->name = $this->getCurrentUser()->name;
		}

		if (empty($this->id)) {
			$this->book_date = DPCalendarHelper::getDate()->toSql(false);
		}

		if (empty($this->user_id)) {
			$this->user_id = 0;
		}

		if (empty($this->price)) {
			$this->price = 0.0;
		}

		if (empty($this->coupon_id)) {
			$this->coupon_id = 0;
		}

		if (empty($this->coupon_rate)) {
			$this->coupon_rate = 0;
		}

		if (empty($this->tax)) {
			$this->tax = 0;
		}

		if (empty($this->tax_rate)) {
			$this->tax_rate = 0;
		}

		if ($this->txn_type === null) {
			$this->txn_type = '';
		}

		if ($this->txn_currency === null) {
			$this->txn_currency = '';
		}

		if ($this->payer_id === null) {
			$this->payer_id = '';
		}

		if ($this->payer_email === null) {
			$this->payer_email = '';
		}

		if (empty($this->latitude)) {
			$this->latitude = 0.0;
		}

		if (empty($this->longitude)) {
			$this->longitude = 0.0;
		}

		// Check for valid name
		$this->email = $this->email ?: '';
		if (trim($this->email) !== '') {
			return true;
		}

		if ($this->user_id >= 1) {
			return true;
		}

		// @phpstan-ignore-next-line
		$this->setError(Text::_('COM_DPCALENDAR_BOOKING_ERR_TABLES_EMAIL'));

		return false;
	}

	public function load($keys = null, $reset = true)
	{
		$return = parent::load($keys, $reset);

		if ($this->price == '0.00') {
			$this->price = 0;
		}

		return $return;
	}

	public function publish($pks = null, $state = 1, $userId = 0): bool
	{
		$k = $this->_tbl_key;

		// Sanitize input.
		ArrayHelper::toInteger($pks);
		$userId = (int)$userId;
		$state  = (int)$state;

		// If there are no primary keys set check to see if the instance key is
		// set.
		if (empty($pks)) {
			if ($this->$k) {
				$pks = [$this->$k];
			} else {
				throw new \Exception(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
			}
		}

		// Build the WHERE clause for the primary keys.
		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);

		// Determine if there is checkin support for the table.
		if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time')) {
			$checkin = '';
		} else {
			$checkin = ' AND (checked_out = 0 OR checked_out = ' . $userId . ')';
		}

		// Update the publishing state for rows with the given primary keys.
		$this->getDatabase()->setQuery(
			'UPDATE ' . $this->_tbl . ' SET state = ' . $state . ' WHERE (' . $where . ')' . $checkin
		);

		$this->getDatabase()->execute();

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && ((is_countable($pks) ? count($pks) : 0) == $this->getDatabase()->getAffectedRows())) {
			// Checkin the rows.
			foreach ($pks as $pk) {
				$this->checkin($pk);
			}
		}

		// If the JTable instance value is in the list of primary keys that were
		// set, set the instance.
		if (in_array($this->$k, $pks)) {
			$this->state = $state;
		}

		return true;
	}
}
