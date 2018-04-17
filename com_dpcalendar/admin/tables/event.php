<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\String\StringHelper;

class DPCalendarTableEvent extends JTable
{

	public function __construct(&$db = null)
	{
		if (\DPCalendar\Helper\DPCalendarHelper::isJoomlaVersion('4', '<')) {
			JObserverMapper::addObserverClassToClass('JTableObserverTags', 'DPCalendarTableEvent', array('typeAlias' => 'com_dpcalendar.event'));
			JObserverMapper::addObserverClassToClass(
				'JTableObserverContenthistory',
				'DPCalendarTableEvent',
				array('typeAlias' => 'com_dpcalendar.event')
			);
		}

		if ($db == null) {
			$db = JFactory::getDbo();
		}
		parent::__construct('#__dpcalendar_events', 'id', $db);

		$this->access         = \DPCalendar\Helper\DPCalendarHelper::getComponentParameter('event_form_access', $this->access);
		$this->access_content = \DPCalendar\Helper\DPCalendarHelper::getComponentParameter('event_form_access_content');
	}

	public function bind($array, $ignore = '')
	{
		if (is_array($array) && isset($array['params']) && is_array($array['params'])) {
			$registry = new JRegistry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}

		if (is_array($array) && isset($array['metadata']) && is_array($array['metadata'])) {
			$registry = new JRegistry();
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string)$registry;
		}

		if (is_array($array) && isset($array['rooms']) && is_array($array['rooms'])) {
			$array['rooms'] = implode(',', $array['rooms']);
		}

		return parent::bind($array, $ignore);
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
			if (!intval($this->created)) {
				$this->created = $date->toSql();
			}
			if (empty($this->created_by)) {
				$this->created_by = $user->get('id');
			}
		}

		// Quick add checks
		if (empty($this->language)) {
			$this->language = '*';
		}

		// Verify that the alias is unique
		$table = JTable::getInstance('Event', 'DPCalendarTable');
		if ($table->load(array('alias' => $this->alias, 'catid' => $this->catid)) && ($table->id != $this->id || $this->id == 0)) {
			$this->alias = StringHelper::increment($this->alias, 'dash');
			$this->alias = JApplicationHelper::stringURLSafe($this->alias);
		}

		$start = DPCalendarHelper::getDate($this->start_date, $this->all_day);
		$end   = DPCalendarHelper::getDate($this->end_date, $this->all_day);

		if ($start->format('U') > $end->format('U')) {
			$end = clone $start;
			$end->modify('+30 minutes');
			$this->end_date = $end->toSql(false);
		}

		// All day event
		if ($this->all_day) {
			$start->setTime(0, 0, 0);
			$end->setTime(0, 0, 0);
			$this->start_date = $start->toSql(true);
			$this->end_date   = $end->toSql(true);
		}

		if ($this->original_id < 1) {
			$this->original_id = !empty($this->rrule) ? -1 : 0;
		}
		if ($this->original_id > 0) {
			$this->rrule = null;
		}

		// Break never ending rules
		if (!empty($this->rrule) && strpos(strtoupper($this->rrule), 'UNTIL') === false && strpos(strtoupper($this->rrule), 'COUNT') === false) {
			$this->rrule .= ';UNTIL=20200101T000000Z';
		}

		$table       = JTable::getInstance('Event', 'DPCalendarTable');
		$hardReset   = false;
		$tagsChanged = isset($this->newTags) && !$this->newTags;
		if ($this->id > 0) {
			$table->load($this->id);

			// If there is a new rrule or date configuratino do a hard reset
			$hardReset = $this->all_day != $table->all_day || $this->start_date != $table->start_date || $this->end_date != $table->end_date || $this->rrule != $table->rrule;
			$oldTags   = new JHelperTags();
			$oldTags   = $oldTags->getItemTags('com_dpcalendar.event', $this->id);
			$oldTags   = array_map(function ($t) {
				return $t->id;
			}, $oldTags);

			$tagsChanged = !isset($this->newTags) ? $oldTags != null : $this->newTags != $oldTags;

			if ($hardReset || $this->price != $table->price) {
				// Check for tickets
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select('id')
					->from('#__dpcalendar_tickets')
					->where('(event_id = ' . (int)$this->id . ' or event_id = ' . (int)$this->original_id . ')')
					->where('state >= 0');
				$db->setQuery($query);
				if ($db->loadResult()) {
					$this->all_day    = $table->all_day;
					$this->start_date = $table->start_date;
					$this->end_date   = $table->end_date;
					$this->rrule      = $table->rrule;
					$this->price      = $table->price;
					$hardReset        = false;

					JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
					JFactory::getApplication()->enqueueMessage(JText::_('COM_DPCALENDAR_ERR_TABLE_NO_DATE_CHANGE'), 'notice');
				}
			}
		}

		// Only delete the childs when a hard reset must be done
		if ($this->id > 0 && $hardReset) {
			$this->_db->setQuery('delete from #__dpcalendar_events where original_id = ' . (int)$this->id);
			$this->_db->execute();
		}

		// Null capacity for unlimited usage
		if ($this->capacity === '') {
			$this->capacity = null;
		}

		$isNew = empty($this->id);

		// Create the UID
		JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);
		if (!$this->uid) {
			$this->uid = strtoupper(Sabre\VObject\UUIDUtil::getUUID());
		}

		// Attempt to store the user data.
		$success = parent::store(true);
		if ($success) {
			DPCalendarHelper::increaseEtag($this->catid);
		}
		if (!$success || empty($this->rrule)) {
			return $success;
		}

		if ($isNew || $hardReset) {
			$text   = array();
			$text[] = 'BEGIN:VCALENDAR';
			$text[] = 'BEGIN:VEVENT';
			$text[] = 'UID:' . md5($this->title);

			$userTz = $start->getTimezone()->getName();
			if (empty($userTz)) {
				$userTz = 'UTC';
			}
			if ($this->all_day == 1) {
				$text[] = 'DTSTART;VALUE=DATE:' . $start->format('Ymd', true);
			} else {
				$text[] = 'DTSTART;TZID=' . $userTz . ':' . $start->format('Ymd\THis', true);
			}
			if ($this->all_day == 1) {
				$text[] = 'DTEND;VALUE=DATE:' . $end->format('Ymd', true);
			} else {
				$text[] = 'DTEND;TZID=' . $userTz . ':' . $end->format('Ymd\THis', true);
			}

			$text[] = 'RRULE:' . $this->rrule;
			$text[] = 'END:VEVENT';
			$text[] = 'END:VCALENDAR';

			$cal = Sabre\VObject\Reader::read(implode(PHP_EOL, $text));
			$cal = $cal->expand(new DateTime('1970-01-01'), new DateTime('2038-01-01'));
			foreach ($cal->VEVENT as $vevent) {
				$startDate = DPCalendarHelper::getDate($vevent->DTSTART->getDateTime()->format('U'), $this->all_day);
				$endDate   = DPCalendarHelper::getDate($vevent->DTEND->getDateTime()->format('U'), $this->all_day);

				$table = JTable::getInstance('Event', 'DPCalendarTable');
				$table->bind((array)$this, array('id'));

				$table->alias      = $table->alias . '_' . $startDate->format('U');
				$table->start_date = $startDate->toSql();
				if ($table->all_day) {
					$table->recurrence_id = $startDate->format('Ymd');
				} else {
					$table->recurrence_id = $startDate->format('Ymd\THis\Z');
				}
				$table->end_date    = $endDate->toSql();
				$table->original_id = $this->id;
				$table->rrule       = '';
				$table->checked_out = 0;
				$table->modified    = $this->getDbo()->getNullDate();
				$table->modified_by = 0;

				// If the xreference does exist, then we need to create it with
				// the proper scheme
				if ($this->xreference) {
					// Replacing the _0 with the start date
					$table->xreference = $this->str_replace_last('_0',
						'_' . ($this->all_day ? $startDate->format('Ymd') : $startDate->format('YmdHi')), $this->xreference);
				}

				if (isset($this->newTags)) {
					$table->newTags = $this->newTags;
				}

				$table->store();
			}
		} else {
			// If tags have changed we need to update each instance
			if ($tagsChanged) {
				$this->populateTags();
			} else {
				$query = $this->_db->getQuery(true);
				$query->update('#__dpcalendar_events');

				if (is_array($this->price)) {
					$this->price = json_encode($this->price);
				}
				if (is_array($this->rooms)) {
					$this->rooms = json_encode($this->rooms);
				}

				// Fields to update.
				$files = array(
					$this->_db->qn('catid') . ' = ' . $this->_db->q($this->catid),
					$this->_db->qn('title') . ' = ' . $this->_db->q($this->title),
					$this->_db->qn('alias') . ' = concat(' . $this->_db->q($this->alias . '_') . ', UNIX_TIMESTAMP(start_date))',
					$this->_db->qn('color') . ' = ' . $this->_db->q($this->color),
					$this->_db->qn('show_end_time') . ' = ' . $this->_db->q($this->show_end_time),
					$this->_db->qn('url') . ' = ' . $this->_db->q($this->url),
					$this->_db->qn('images') . ' = ' . $this->_db->q($this->images),
					$this->_db->qn('description') . ' = ' . $this->_db->q($this->description),
					$this->_db->qn('capacity') . ' = ' . ($this->capacity === null ? 'NULL' : $this->_db->q($this->capacity)),
					$this->_db->qn('capacity_used') . ' = ' . $this->_db->q($this->capacity_used),
					$this->_db->qn('max_tickets') . ' = ' . $this->_db->q($this->max_tickets),
					$this->_db->qn('booking_closing_date') . ' = ' . $this->_db->q($this->booking_closing_date),
					$this->_db->qn('price') . ' = ' . $this->_db->q($this->price),
					$this->_db->qn('earlybird') . ' = ' . $this->_db->q($this->earlybird),
					$this->_db->qn('user_discount') . ' = ' . $this->_db->q($this->user_discount),
					$this->_db->qn('booking_information') . ' = ' . $this->_db->q($this->booking_information),
					$this->_db->qn('tax') . ' = ' . $this->_db->q($this->tax),
					$this->_db->qn('ordertext') . ' = ' . $this->_db->q($this->ordertext),
					$this->_db->qn('orderurl') . ' = ' . $this->_db->q($this->orderurl),
					$this->_db->qn('canceltext') . ' = ' . $this->_db->q($this->canceltext),
					$this->_db->qn('cancelurl') . ' = ' . $this->_db->q($this->cancelurl),
					$this->_db->qn('state') . ' = ' . $this->_db->q($this->state),
					$this->_db->qn('checked_out') . ' = ' . $this->_db->q(0),
					$this->_db->qn('checked_out_time') . ' = ' . $this->_db->q($this->_db->getNullDate()),
					$this->_db->qn('access') . ' = ' . $this->_db->q($this->access),
					$this->_db->qn('access_content') . ' = ' . $this->_db->q($this->access_content),
					$this->_db->qn('params') . ' = ' . $this->_db->q($this->params),
					$this->_db->qn('rooms') . ' = ' . $this->_db->q($this->rooms),
					$this->_db->qn('language') . ' = ' . $this->_db->q($this->language),
					$this->_db->qn('modified') . ' = ' . $this->_db->q($this->modified),
					$this->_db->qn('modified_by') . ' = ' . $this->_db->q($user->id),
					$this->_db->qn('metakey') . ' = ' . $this->_db->q($this->metakey),
					$this->_db->qn('metadesc') . ' = ' . $this->_db->q($this->metadesc),
					$this->_db->qn('metadata') . ' = ' . $this->_db->q($this->metadata),
					$this->_db->qn('featured') . ' = ' . $this->_db->q($this->featured),
					$this->_db->qn('publish_up') . ' = ' . $this->_db->q($this->publish_up),
					$this->_db->qn('publish_down') . ' = ' . $this->_db->q($this->publish_down),
					$this->_db->qn('plugintype') . ' = ' . $this->_db->q($this->plugintype)
				);

				// If the xreference does exist, then we need to create it with
				// the proper scheme
				if ($this->xreference) {
					// Replacing the _0 with the start date
					$files[] = $this->_db->qn('xreference') . ' = concat(' . $this->_db->q($this->str_replace_last('_0', '_', $this->xreference)) .
						", DATE_FORMAT(start_date, CASE WHEN all_day = '1' THEN '%Y%m%d' ELSE '%Y%m%d%H%i' END))";
				} else {
					$files[] = $this->_db->qn('xreference') . ' = null';
				}

				$query->set($files);
				$query->where($this->_db->qn('original_id') . ' = ' . $this->_db->q($this->id));

				$this->_db->setQuery($query);
				$this->_db->execute();
			}
		}

		return $success;
	}

	public function check()
	{
		if (JFilterInput::checkAttribute(array('start_date', $this->start_date))) {
			$this->setError(JText::_('COM_DPCALENDAR_ERR_TABLES_PROVIDE_START_DATE'));

			return false;
		}
		if (JFilterInput::checkAttribute(array('end_date', $this->end_date))) {
			$this->setError(JText::_('COM_DPCALENDAR_ERR_TABLES_PROVIDE_END_DATE'));

			return false;
		}

		// Check for valid name
		if (trim($this->title) == '') {
			$this->setError(JText::_('COM_DPCALENDAR_ERR_TABLES_TITLE') . ' [' . $this->catid . ']');

			return false;
		}

		// Check for existing name
		$query = 'SELECT id, original_id, alias FROM #__dpcalendar_events WHERE alias = ' . $this->_db->Quote($this->alias) . ' AND catid = ' .
			(int)$this->catid;
		$this->_db->setQuery($query);

		$xid = $this->_db->loadObject();
		if ($xid && $xid->id != intval($this->id) && $xid->original_id == $this->original_id) {
			$this->alias = StringHelper::increment($this->alias, 'dash');
		}

		if (empty($this->alias)) {
			$this->alias = $this->title;
		}
		$this->alias = JApplicationHelper::stringURLSafe($this->alias);
		if (trim(str_replace('-', '', $this->alias)) == '') {
			$this->alias = JFactory::getDate()->format("Y-m-d-H-i-s");
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down > $this->_db->getNullDate() && $this->publish_down < $this->publish_up) {
			// Swap the dates.
			$temp               = $this->publish_up;
			$this->publish_up   = $this->publish_down;
			$this->publish_down = $temp;
		}

		// Clean up keywords -- eliminate extra spaces between phrases
		// and cr (\r) and lf (\n) characters from string
		if (!empty($this->metakey)) {
			// Only process if not empty
			$bad_characters = array("\n", "\r", "\"", "<", ">");
			$after_clean    = StringHelper::str_ireplace($bad_characters, "", $this->metakey);
			$keys           = explode(',', $after_clean);
			$clean_keys     = array();
			foreach ($keys as $key) {
				if (trim($key)) {
					$clean_keys[] = trim($key);
				}
			}
			$this->metakey = implode(", ", $clean_keys);
		}

		if (!$this->id) {
			// Images can be an empty json string
			if (!isset($this->images)) {
				$this->images = '{}';
			}
		}

		// Strict mode adjustments
		if (!is_numeric($this->capacity_used)) {
			$this->capacity_used = 0;
		}

		if (empty($this->modified)) {
			$this->modified = $this->getDbo()->getNullDate();
		}
		if (empty($this->publish_up)) {
			$this->publish_up = $this->getDbo()->getNullDate();
		}
		if (empty($this->publish_down)) {
			$this->publish_down = $this->getDbo()->getNullDate();
		}

		return true;
	}

	public function delete($pk = null)
	{
		$success = parent::delete($pk);
		if ($success && $pk > 0) {
			$this->_db->setQuery('delete from #__dpcalendar_events where original_id = ' . (int)$pk);
			$this->_db->execute();
			$this->_db->setQuery('delete from #__dpcalendar_tickets where event_id = ' . (int)$pk);
			$this->_db->execute();
		}
		if ($success && $this->catid) {
			$this->load($pk);
			DPCalendarHelper::increaseEtag($this->catid);
		}

		return $success;
	}

	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->_tbl_key;

		// Sanitize input.
		JArrayHelper::toInteger($pks);
		$userId = (int)$userId;
		$state  = (int)$state;

		// If there are no primary keys set check to see if the instance key is
		// set.
		if (empty($pks)) {
			if ($this->$k) {
				$pks = array($this->$k);
			} else {
				$this->setError(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

				return false;
			}
		}

		// Build the WHERE clause for the primary keys.
		$where = $k . '=' . implode(' OR ' . $k . '=', $pks);

		// Add child events
		$where .= ' or original_id = ' . implode(' OR original_id =', $pks);

		// Determine if there is checkin support for the table.
		if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time')) {
			$checkin = '';
		} else {
			$checkin = ' AND (checked_out = 0 OR checked_out = ' . (int)$userId . ')';
		}

		// Update the publishing state for rows with the given primary keys.
		$this->_db->setQuery(
			'UPDATE ' . $this->_db->quoteName($this->_tbl) . ' SET ' . $this->_db->quoteName('state') . ' = ' . (int)$state . ' WHERE (' . $where . ')' . $checkin
		);
		$this->_db->query();

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

	public function book($increment = true, $pk = null)
	{
		if ($pk == null) {
			$pk = $this->id;
		}

		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set($this->_db->quoteName('capacity_used') . ' = (' . $this->_db->quoteName('capacity_used') . ' ' . ($increment ? '+' : '-') . ' 1)');
		$query->where('id = ' . (int)$pk);
		if (!$increment) {
			$query->where('capacity_used > 0');
		}
		$this->_db->setQuery($query);
		$this->_db->execute();

		if ($increment) {
			$this->capacity_used++;
		} else {
			$this->capacity_used--;
		}

		return true;
	}

	public function populateTags($newTags = null)
	{
		$this->_db->setQuery('select * from #__dpcalendar_events where ' . $this->_db->qn('original_id') . ' = ' . $this->_db->q($this->id));
		$childs = $this->_db->loadObjectList(null, 'DPCalendarTableEvent');

		foreach ($childs as $child) {
			$child->bind((array)$this,
				array(
					'id',
					'original_id',
					'start_date',
					'end_date',
					'all_day',
					'alias',
					'rrule',
					'recurrence_id',
					'checked_out',
					'checked_out_time',
					'xreference'
				));

			if ($newTags === null) {
				$newTags = $this->newTags;
			}

			if (isset($newTags)) {
				$child->newTags = $newTags;
			}
			$child->store();
		}
	}

	public function clearDb()
	{
		$this->_db = null;

		return true;
	}

	private function str_replace_last($search, $replace, $str)
	{
		if (($pos = strrpos($str, $search)) !== false) {
			$search_length = strlen($search);
			$str           = substr_replace($str, $replace, $pos, $search_length);
		}

		return $str;
	}
}
