<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;
use Sabre\VObject\UUIDUtil;

class DPCalendarTableEvent extends Table implements TaggableTableInterface, VersionableTableInterface
{
	use TaggableTableTrait;

	public function __construct(&$db = null)
	{
		if (!class_exists(DPCalendarHelper::class)) {
			// Needed for versions
			JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
		}

		if (DPCalendarHelper::isJoomlaVersion('4', '<')) {
			JObserverMapper::addObserverClassToClass('JTableObserverTags', 'DPCalendarTableEvent', ['typeAlias' => 'com_dpcalendar.event']);
			JObserverMapper::addObserverClassToClass(
				'JTableObserverContenthistory',
				'DPCalendarTableEvent',
				['typeAlias' => 'com_dpcalendar.event']
			);
		} else {
			$this->typeAlias = 'com_dpcalendar.event';
		}

		if ($db == null) {
			$db = Factory::getDbo();
		}
		parent::__construct('#__dpcalendar_events', 'id', $db);

		$this->setColumnAlias('published', 'state');

		// Set access flag as they are set in the base class already from props
		$params               = method_exists(Factory::getApplication(), 'getParams') ? Factory::getApplication()->getParams() : ComponentHelper::getParams('com_dpcalendar');
		$this->access         = $params->get('event_form_access', $this->access);
		$this->access_content = $params->get('event_form_access_content');
	}

	public function bind($array, $ignore = '')
	{
		if (is_array($array) && isset($array['params']) && is_array($array['params'])) {
			$registry = new Registry();
			$registry->loadArray($array['params']);
			$array['params'] = (string)$registry;
		}

		if (is_array($array) && isset($array['metadata']) && is_array($array['metadata'])) {
			$registry = new Registry();
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
		// Needs reset, so no caching of now
		Factory::$dates = [];
		$date           = DPCalendarHelper::getDate();
		$user           = Factory::getUser();
		if ($this->id) {
			// Existing item
			$this->modified    = $date->toSql();
			$this->modified_by = $user->id;
		}
		if (!$this->id && !intval($this->created)) {
			$this->created = $date->toSql();
		}
		if (!$this->id && empty($this->created_by)) {
			$this->created_by = $user->id;
		}

		// Quick add checks
		if (empty($this->language)) {
			$this->language = '*';
		}

		// Verify that the alias is unique
		while (true) {
			$this->getDbo()->setQuery('SELECT id, alias FROM #__dpcalendar_events WHERE alias = ' . $this->getDbo()->quote($this->alias) . ' and id != ' . (int)$this->id);
			$table = $this->getDbo()->loadObject();
			if (!$table || !$table->id) {
				break;
			}

			$this->alias = ApplicationHelper::stringURLSafe(StringHelper::increment($this->alias, 'dash'));
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
			$until = new DateTime();
			$until->modify('+3 years');
			$this->rrule .= ';UNTIL=' . $until->format('Y') . '0101T000000Z';
		}

		$oldEvent    = Table::getInstance('Event', 'DPCalendarTable');
		$hardReset   = false;
		$tagsChanged = isset($this->newTags) && !$this->newTags;
		if ($this->id > 0) {
			$oldEvent->load($this->id);

			// If there is a new rrule or date configuration do a hard reset
			$hardReset = $this->all_day != $oldEvent->all_day || $this->start_date != $oldEvent->start_date || $this->end_date != $oldEvent->end_date || $this->rrule != $oldEvent->rrule;
			$oldTags   = new TagsHelper();
			$oldTags   = $oldTags->getItemTags('com_dpcalendar.event', $this->id);
			$oldTags   = array_map(fn ($t) => $t->id, $oldTags);

			$tagsChanged = !isset($this->newTags) ? $oldTags != null : $this->newTags != $oldTags;

			if ($this->price != $oldEvent->price || $this->booking_options != $oldEvent->booking_options || ($hardReset && $this->rrule && $this->booking_series != 1)) {
				// Check for tickets
				$query = $this->getDbo()->getQuery(true);
				$query->select('t.id')
					->from('#__dpcalendar_tickets as t')
					->join('LEFT', '#__dpcalendar_events as e on e.original_id=' . (int)$this->id)
					->where('(t.event_id = ' . (int)$this->id . ' or t.event_id = ' . (int)$this->original_id . ' or t.event_id = e.id)')
					->where('t.state >= 0');
				$this->getDbo()->setQuery($query);
				if ($this->getDbo()->loadResult()) {
					$this->all_day         = $oldEvent->all_day;
					$this->start_date      = $oldEvent->start_date;
					$this->end_date        = $oldEvent->end_date;
					$this->rrule           = $oldEvent->rrule;
					$this->price           = $oldEvent->price;
					$this->booking_options = $oldEvent->booking_options;
					$hardReset             = false;

					Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
					Factory::getApplication()->enqueueMessage(Text::_('COM_DPCALENDAR_ERR_TABLE_NO_PRICE_CHANGE'), 'notice');
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
		JLoader::import('components.com_dpcalendar.vendor.autoload', JPATH_ADMINISTRATOR);
		if (!$this->uid) {
			$this->uid = strtoupper(UUIDUtil::getUUID());
		}

		if (!empty($this->images) && $this->images != '{}') {
			$images = is_object($this->images) ? $this->images : json_decode($this->images);
			if (!empty($images->image_intro)) {
				$path = JPATH_ROOT . '/' . $images->image_intro;
				if ($hashPos = strpos($images->image_intro, '#')) {
					$path = JPATH_ROOT . '/' . substr($images->image_intro, 0, $hashPos);
				}
				if (file_exists($path)) {
					$props                      = Image::getImageFileProperties($path);
					$images->image_intro_width  = $props->width;
					$images->image_intro_height = $props->height;
				}
			}
			if (!empty($images->image_full)) {
				$path = JPATH_ROOT . '/' . $images->image_full;
				if ($hashPos = strpos($images->image_full, '#')) {
					$path = JPATH_ROOT . '/' . substr($images->image_full, 0, $hashPos);
				}
				if (file_exists($path)) {
					$props                     = Image::getImageFileProperties($path);
					$images->image_full_width  = $props->width;
					$images->image_full_height = $props->height;
				}
			}

			$this->images = json_encode($images);
		}

		// On some front end templates the color is set with none
		if ($this->color == 'none') {
			$this->color = '';
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
			$text   = [];
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

			// The rrule until field needs to be adapted to the user timezone
			$rrule = '';
			foreach (explode(';', strtoupper($this->rrule)) as $part) {
				if (empty($part)) {
					continue;
				}
				[$partName, $partValue] = explode('=', $part);

				if ($partName === 'UNTIL') {
					// Remove the timezone information, sabre assumes then the field is in user timezone
					$partValue = str_replace('Z', '', $partValue);
				}

				$rrule .= $partName . '=' . $partValue . ';';
			}

			$text[] = 'RRULE:' . $rrule;
			$text[] = 'END:VEVENT';
			$text[] = 'END:VCALENDAR';

			/** @var VCalendar $cal */
			$cal = Reader::read(implode(PHP_EOL, $text));
			$cal = $cal->expand(new DateTime('1970-01-01'), new DateTime('2038-01-01'));
			foreach ($cal->VEVENT as $vevent) {
				$startDate = DPCalendarHelper::getDate($vevent->DTSTART->getDateTime()->format('U'), $this->all_day);
				$endDate   = DPCalendarHelper::getDate($vevent->DTEND->getDateTime()->format('U'), $this->all_day);

				$table = Table::getInstance('Event', 'DPCalendarTable');
				$table->bind((array)$this, ['id']);

				$table->alias         = ApplicationHelper::stringURLSafe($table->alias . '-' . $startDate->format('U'));
				$table->start_date    = $startDate->toSql();
				$table->recurrence_id = $startDate->format('Ymd' . (!$table->all_day ? '\THis\Z' : ''));
				$table->end_date      = $endDate->toSql();
				$table->original_id   = $this->id;
				$table->rrule         = '';
				$table->checked_out   = 0;
				$table->modified      = null;
				$table->modified_by   = 0;

				// If the xreference does exist, then we need to create it with the proper scheme
				if ($this->xreference) {
					// Replacing the _0 with the start date
					$table->xreference = $this->replaceLastInString(
						'_0',
						'_' . ($this->all_day ? $startDate->format('Ymd') : $startDate->format('YmdHi')),
						$this->xreference
					);
				}

				if (isset($this->newTags)) {
					$table->newTags = $this->newTags;
				}

				$table->store();
			}

			return $success;
		}

		// If tags have changed we need to update each instance
		if ($tagsChanged) {
			$this->populateTags();
		}

		$query = $this->_db->getQuery(true);
		$query->update('#__dpcalendar_events');

		if (is_array($this->price)) {
			$this->price = json_encode($this->price);
		}
		if (is_array($this->rooms)) {
			$this->rooms = json_encode($this->rooms);
		}

		// Fields to update
		$files = [
			$this->_db->qn('catid') . ' = ' . $this->_db->q($this->catid),
			$this->_db->qn('title') . ' = ' . $this->_db->q($this->title),
			$this->_db->qn('color') . ' = ' . $this->_db->q($this->color),
			$this->_db->qn('show_end_time') . ' = ' . $this->_db->q($this->show_end_time),
			$this->_db->qn('url') . ' = ' . $this->_db->q($this->url),
			$this->_db->qn('images') . ' = ' . $this->_db->q($this->images),
			$this->_db->qn('description') . ' = ' . $this->_db->q($this->description),
			$this->_db->qn('schedule') . ' = ' . $this->_db->q($this->schedule),
			$this->_db->qn('capacity') . ' = ' . ($this->capacity === null ? 'NULL' : $this->_db->q($this->capacity)),
			$this->_db->qn('max_tickets') . ' = ' . $this->_db->q($this->max_tickets),
			$this->_db->qn('booking_opening_date') . ' = ' . $this->_db->q($this->booking_opening_date),
			$this->_db->qn('booking_closing_date') . ' = ' . $this->_db->q($this->booking_closing_date),
			$this->_db->qn('booking_cancel_closing_date') . ' = ' . $this->_db->q($this->booking_cancel_closing_date),
			$this->_db->qn('booking_series') . ' = ' . $this->_db->q($this->booking_series),
			$this->_db->qn('booking_waiting_list') . ' = ' . $this->_db->q($this->booking_waiting_list),
			$this->_db->qn('price') . ' = ' . $this->_db->q($this->price),
			$this->_db->qn('earlybird') . ' = ' . $this->_db->q($this->earlybird),
			$this->_db->qn('user_discount') . ' = ' . $this->_db->q($this->user_discount),
			$this->_db->qn('booking_information') . ' = ' . $this->_db->q($this->booking_information),
			$this->_db->qn('terms') . ' = ' . $this->_db->q($this->terms),
			$this->_db->qn('state') . ' = ' . $this->_db->q($this->state),
			$this->_db->qn('checked_out') . ' = ' . $this->_db->q(0),
			$this->_db->qn('checked_out_time') . ' = null',
			$this->_db->qn('access') . ' = ' . $this->_db->q($this->access),
			$this->_db->qn('access_content') . ' = ' . $this->_db->q($this->access_content),
			$this->_db->qn('params') . ' = ' . $this->_db->q($this->params),
			$this->_db->qn('rooms') . ' = ' . $this->_db->q($this->rooms ?: ''),
			$this->_db->qn('language') . ' = ' . $this->_db->q($this->language),
			$this->_db->qn('modified') . ' = ' . ($this->modified ? $this->_db->q($this->modified) : 'null'),
			$this->_db->qn('modified_by') . ' = ' . $this->_db->q($user->id),
			$this->_db->qn('created_by') . ' = ' . $this->_db->q($this->created_by),
			$this->_db->qn('metakey') . ' = ' . $this->_db->q($this->metakey ?: ''),
			$this->_db->qn('metadesc') . ' = ' . $this->_db->q($this->metadesc ?: ''),
			$this->_db->qn('metadata') . ' = ' . $this->_db->q($this->metadata),
			$this->_db->qn('featured') . ' = ' . $this->_db->q($this->featured),
			$this->_db->qn('publish_up') . ' = ' . ($this->publish_up ? $this->_db->q($this->publish_up) : 'null'),
			$this->_db->qn('publish_down') . ' = ' . ($this->publish_down ? $this->_db->q($this->publish_down) : 'null'),
			$this->_db->qn('payment_provider') . ' = ' . $this->_db->q($this->payment_provider)
		];

		// If the xreference does exist, then we need to create it with the proper scheme
		if ($this->xreference) {
			// Replacing the _0 with the start date
			$files[] = $this->_db->qn('xreference') . ' = concat(' . $this->_db->q($this->replaceLastInString('_0', '_', $this->xreference)) .
				", DATE_FORMAT(start_date, CASE WHEN all_day = '1' THEN '%Y%m%d' ELSE '%Y%m%d%H%i' END))";
		} else {
			$files[] = $this->_db->qn('xreference') . ' = null';
		}

		// Reset capacity used only when the whole series can be booked
		if ($this->booking_series == 1) {
			$files[] = $this->_db->qn('capacity_used') . ' = ' . $this->_db->q($this->capacity_used);
		}

		$query->set($files);
		$query->where($this->_db->qn('original_id') . ' = ' . $this->_db->q($this->id));

		if ($oldEvent->modified && isset($this->_update_modified) && $this->_update_modified == 0) {
			$query->where('(' . $this->_db->qn('modified') . ' = ' . $this->_db->q($oldEvent->modified)
				. ' or modified is null)');
		}

		$this->_db->setQuery($query);
		$this->_db->execute();

		return $success;
	}

	public function check()
	{
		if (!$this->start_date || InputFilter::checkAttribute(['start_date', $this->start_date])) {
			$this->setError(Text::_('COM_DPCALENDAR_ERR_TABLES_PROVIDE_START_DATE'));

			return false;
		}
		if (!$this->end_date || InputFilter::checkAttribute(['end_date', $this->end_date])) {
			$this->setError(Text::_('COM_DPCALENDAR_ERR_TABLES_PROVIDE_END_DATE'));

			return false;
		}

		// Check for valid name
		if (!$this->title || trim($this->title) == '') {
			$this->setError(Text::_('COM_DPCALENDAR_ERR_TABLES_TITLE') . ' [' . $this->catid . ']');

			return false;
		}

		if (empty($this->alias)) {
			$this->alias = $this->title;
		}
		$this->alias = ApplicationHelper::stringURLSafe($this->alias);
		if (trim(str_replace('-', '', $this->alias)) == '') {
			$this->alias = Factory::getDate($this->start_date)->format('Y-m-d-H-i-s');
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down && $this->publish_down < $this->publish_up) {
			// Swap the dates.
			$temp               = $this->publish_up;
			$this->publish_up   = $this->publish_down;
			$this->publish_down = $temp;
		}

		// Clean up keywords -- eliminate extra spaces between phrases and cr (\r) and lf (\n) characters from string
		if (!empty($this->metakey)) {
			// Only process if not empty
			$bad_characters = ["\n", "\r", "\"", "<", ">"];
			$after_clean    = StringHelper::str_ireplace($bad_characters, "", $this->metakey);
			$keys           = explode(',', $after_clean);
			$clean_keys     = [];
			foreach ($keys as $key) {
				if (trim($key)) {
					$clean_keys[] = trim($key);
				}
			}
			$this->metakey = implode(", ", $clean_keys);
		}

		// Images can be an empty json string
		if (!$this->id && !isset($this->images)) {
			$this->images = '{}';
		}

		// Strict mode adjustments
		if (!is_numeric($this->capacity_used)) {
			$this->capacity_used = 0;
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

		if (empty($this->hits)) {
			$this->hits = 0;
		}
		if (empty($this->checked_out)) {
			$this->checked_out = 0;
		}
		if (empty($this->created_by)) {
			$this->created_by = 0;
		}

		if ($this->color) {
			$this->color = str_replace('#', '', $this->color);
		}

		return true;
	}

	public function delete($pk = null)
	{
		if (!$this->catid) {
			$this->load($pk);
		}

		$success = parent::delete($pk);
		if ($success && $pk > 0) {
			$this->_db->setQuery('delete from #__dpcalendar_events where original_id = ' . (int)$pk);
			$this->_db->execute();
			$this->_db->setQuery('delete from #__dpcalendar_tickets where event_id = ' . (int)$pk);
			$this->_db->execute();
		}
		if ($success && $this->catid) {
			DPCalendarHelper::increaseEtag($this->catid);
		}

		return $success;
	}

	public function publish($pks = null, $state = 1, $userId = 0)
	{
		// Initialise variables.
		$k = $this->_tbl_key;

		// Sanitize input.
		ArrayHelper::toInteger($pks);
		$userId = (int)$userId;
		$state  = (int)$state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks) && !$this->$k) {
			$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

			return false;
		}

		if (empty($pks)) {
			$pks = [$this->$k];
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
		$this->_db->execute();

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && ((is_countable($pks) ? count($pks) : 0) == $this->_db->getAffectedRows())) {
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
		$query->where('(id = ' . (int)$pk . ' or (original_id = ' . (int)$pk . ' and booking_series = 1))');
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
		foreach ($this->_db->loadAssocList() as $child) {
			$table = new self($this->getDbo());
			$table->bind($child);

			if ($newTags === null) {
				$newTags = $this->newTags;
			}

			if (isset($newTags)) {
				$table->newTags = $newTags;
			}
			$table->store();
		}
	}

	public function clearDb()
	{
		$this->_db = null;

		return true;
	}

	private function replaceLastInString($search, $replace, $str)
	{
		if (($pos = strrpos($str, (string) $search)) !== false) {
			$search_length = strlen($search);
			$str           = substr_replace($str, $replace, $pos, $search_length);
		}

		return $str;
	}

	public function getTypeAlias()
	{
		return $this->typeAlias;
	}
}
