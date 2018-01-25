<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class DPCalendarTableExtcalendar extends JTable
{
	public function __construct(&$db)
	{
		parent::__construct('#__dpcalendar_extcalendars', 'id', $db);
	}

	public function delete($pk = null)
	{
		$success = parent::delete($pk);
		if ($success) {
			// Load the DPCalendar plugins to delete cached events
			JPluginHelper::importPlugin('dpcalendar');
			JFactory::getApplication()->triggerEvent('onCalendarAfterDelete', array($this));
		}

		return $success;
	}

	public function bind($array, $ignore = '')
	{
		if (isset($array['params']) && is_array($array['params'])) {
			$registry = new Registry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}

		// Bind the rules.
		if (isset($array['rules']) && is_array($array['rules'])) {
			$rules = new JRules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}

	public function load($keys = null, $reset = true)
	{
		$return = parent::load($keys, $reset);

		// Check the password
		if ($this->params) {
			$params = new Registry($this->params);
			if ($pw = $params->get('password')) {
				$params->set('password', \DPCalendar\Helper\DPCalendarHelper::deobfuscate($pw));
			}
			$this->params = $params->toString();
		}

		return $return;
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

		// Verify that the alias is unique
		$table = JTable::getInstance('Extcalendar', 'DPCalendarTable');
		if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0)) {
			$this->alias = \Joomla\String\StringHelper::increment($this->alias);
		}

		if (empty($this->_rules)) {
			$this->_rules = new JRules(array('core.edit' => array(), 'core.create' => array(), 'core.delete' => array()));
		}

		// Obfuscate the password
		if (!empty($this->params)) {
			$params = new \Joomla\Registry\Registry(json_decode($this->params));

			if ($pw = $params->get('password')) {
				$params->set('password', \DPCalendar\Helper\DPCalendarHelper::obfuscate($pw));
			}
			$this->params = $params->toString();
		}

		// Attempt to store the user data.
		return parent::store($updateNulls);
	}

	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return 'com_dpcalendar.extcalendar.' . (int)$this->$k;
	}

	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_dpcalendar');

		return $asset->id;
	}

	public function check()
	{
		// Check for valid name
		if (trim($this->title) == '') {
			$this->setError(JText::sprintf('COM_DPCALENDAR_EXTCALENDAR_ERR_TABLES_NAME', ''));

			return false;
		}

		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_extcalendars WHERE title = ' . $this->_db->Quote($this->title);
		$this->_db->setQuery($query);

		$xid = (int)$this->_db->loadResult();
		if ($xid && $xid != (int)$this->id) {
			$this->setError(JText::sprintf('COM_DPCALENDAR_EXTCALENDAR_ERR_TABLES_NAME', htmlspecialchars($this->title)));

			return false;
		}

		if (empty($this->alias)) {
			$this->alias = $this->title;
		}
		$this->alias = JApplicationHelper::stringURLSafe($this->alias);
		if (trim(str_replace('-', '', $this->alias)) == '') {
			$this->alias = JFactory::getDate()->format("Y-m-d-H-i-s");
		}

		if (empty($this->language)) {
			$this->language = '*';
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down > $this->_db->getNullDate() && $this->publish_down < $this->publish_up) {
			$this->setError(JText::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));

			return false;
		}

		// Clean up keywords -- eliminate extra spaces between phrases
		// and cr (\r) and lf (\n) characters from string
		if (!empty($this->metakey)) {
			$bad_characters = array("\n", "\r", "\"", "<", ">");

			$after_clean = \Joomla\String\StringHelper::str_ireplace($bad_characters, "", $this->metakey);
			$keys        = explode(',', $after_clean);
			$clean_keys  = array();
			foreach ($keys as $key) {
				if (trim($key)) {
					$clean_keys[] = trim($key);
				}
			}
			$this->metakey = implode(", ", $clean_keys);
		}

		$this->color = str_replace('#', '', $this->color);

		return true;
	}

	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->_tbl_key;

		// Sanitize input.
		ArrayHelper::toInteger($pks);
		$userId = (int)$userId;
		$state  = (int)$state;

		// If there are no primary keys set check to see if the instance key is
		// set.
		if (empty($pks)) {
			if ($this->$k) {
				$pks = array($this->$k);
			} // Nothing to set publishing state on, return false.
			else {
				$this->setError(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

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

		// Get the JDatabaseQuery object
		$query = $this->_db->getQuery(true);

		// Update the publishing state for rows with the given primary keys.
		$query->update($this->_db->quoteName($this->_tbl));
		$query->set($this->_db->quoteName('state') . ' = ' . (int)$state);
		$query->where('(' . $where . ')' . $checkin);
		$this->_db->setQuery($query);
		$this->_db->execute();

		// Check for a database error.
		if ($this->_db->getErrorNum()) {
			$this->setError($this->_db->getErrorMsg());

			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && (count($pks) == $this->_db->getAffectedRows())) {
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

		$this->setError('');

		return true;
	}
}
