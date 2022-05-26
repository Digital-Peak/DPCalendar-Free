<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Location;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class DPCalendarTableLocation extends Table implements TaggableTableInterface, VersionableTableInterface
{
	use TaggableTableTrait;

	public function __construct(&$db)
	{
		parent::__construct('#__dpcalendar_locations', 'id', $db);

		if (DPCalendarHelper::isJoomlaVersion('4', '<')) {
			JObserverMapper::addObserverClassToClass('JTableObserverTags', 'DPCalendarTableLocation', ['typeAlias' => 'com_dpcalendar.location']);
		} else {
			$this->typeAlias = 'com_dpcalendar.location';
		}

		$this->setColumnAlias('published', 'state');
	}

	public function bind($array, $ignore = '')
	{
		if (isset($array['params']) && is_array($array['params'])) {
			$registry = new Registry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}

		if (isset($array['metadata']) && is_array($array['metadata'])) {
			$registry = new Registry();
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string)$registry;
		}

		if (isset($array['images']) && is_array($array['images'])) {
			$registry = new Registry();
			$registry->loadArray($array['images']);
			$array['images'] = (string)$registry;
		}

		if (isset($array['rooms']) && is_array($array['rooms'])) {
			$registry = new Registry();
			$registry->loadArray($array['rooms']);
			$array['rooms'] = (string)$registry;
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
		$table = Table::getInstance('Location', 'DPCalendarTable');
		if ($table->load(['alias' => $this->alias]) && ($table->id != $this->id || $this->id == 0)) {
			$this->setError(Text::_('COM_DPCALENDAR_ERROR_UNIQUE_ALIAS_LOCATION') . ': ' . $table->alias);

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

		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_locations WHERE title = ' . $this->_db->Quote($this->title);
		$this->_db->setQuery($query);

		$xid = (int)$this->_db->loadResult();
		if ($xid && $xid != (int)$this->id) {
			$this->setError(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_NAME'));

			return false;
		}

		if (empty($this->alias)) {
			$this->alias = $this->title;
		}
		$this->alias = ApplicationHelper::stringURLSafe($this->alias);
		if (trim(str_replace('-', '', $this->alias)) == '') {
			$this->alias = DPCalendarHelper::getDate()->format("Y-m-d-H-i-s");
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down > $this->_db->getNullDate() && $this->publish_down < $this->publish_up) {
			$this->setError(Text::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

			return false;
		}

		// Clean up keywords -- eliminate extra spaces between phrases
		// and cr (\r) and lf (\n) characters from string
		if (!empty($this->metakey)) {
			$bad_characters = ["\n", "\r", "\"", "<", ">"];

			$after_clean = StringHelper::str_ireplace($bad_characters, "", $this->metakey);
			$keys        = explode(',', $after_clean);
			$clean_keys  = [];
			foreach ($keys as $key) {
				if (trim($key)) {
					$clean_keys[] = trim($key);
				}
			}
			$this->metakey = implode(", ", $clean_keys);
		}

		if (empty($this->modified)) {
			$this->modified = $this->getDbo()->getNullDate();
		}

		if (empty($this->color)) {
			$this->color = Location::getColor($this);
		}
		$this->color = str_replace('#', '', $this->color);

		return true;
	}

	public function publish($pks = null, $state = 1, $userId = 0)
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
				$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

				return false;
			}
		}

		// Build the WHERE clause for the primary keys.
		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);

		// Determine if there is checkin support for the table.
		if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time')) {
			$checkin = '';
		} else {
			$checkin = ' AND (checked_out = 0 OR checked_out = ' . (int)$userId . ')';
		}

		// Update the publishing state for rows with the given primary keys.
		$this->_db->setQuery(
			'UPDATE ' . $this->_db->quoteName($this->_tbl) . ' SET ' . $this->_db->quoteName('state') . ' = ' . (int)$state . ' WHERE (' . $where .
			')' . $checkin
		);

		try {
			$this->_db->execute();
		} catch (RuntimeException $e) {
			$this->setError($e->getMessage());

			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in
		if ($checkin && (count($pks) == $this->_db->getAffectedRows())) {
			// Checkin the rows
			foreach ($pks as $pk) {
				$this->checkin($pk);
			}
		}

		// If the JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks)) {
			$this->state = $state;
		}

		$this->setError('');

		return true;
	}

	public function getTypeAlias()
	{
		return $this->typeAlias;
	}
}
