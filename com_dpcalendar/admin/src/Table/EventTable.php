<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;
use Sabre\VObject\UUIDUtil;

class EventTable extends BasicTable implements TaggableTableInterface, VersionableTableInterface
{
	use TaggableTableTrait;

	/** @var int|string */
	// @phpstan-ignore-next-line
	public $id;

	/** @var string */
	public $access;

	/** @var string */
	public $access_content;

	/** @var ?string */
	public $modified;

	/** @var int */
	public $modified_by;

	/** @var ?string */
	public $created;

	/** @var int */
	public $created_by;

	/** @var string */
	public $language;

	/** @var string */
	public $alias;

	/** @var string */
	public $start_date;

	/** @var int */
	public $all_day;

	/** @var string */
	public $end_date;

	/** @var int|string */
	public $original_id;

	/** @var string */
	public $recurrence_id;

	/** @var ?string */
	public $rrule;

	/** @var string */
	public $exdates;

	/** @var \stdClass|string */
	public $prices;

	/** @var string */
	public $booking_options;

	/** @var string */
	public $booking_series;

	/** @var ?string */
	public $capacity;

	/** @var string */
	public $uid;

	/** @var \stdClass|string */
	public $images;

	/** @var string */
	public $color;

	/** @var string */
	public $catid;

	/** @var string */
	public $xreference;

	/** @var array|string */
	public $rooms;

	/** @var string */
	public $title;

	/** @var string */
	public $show_end_time;

	/** @var string */
	public $url;

	/** @var string */
	public $description;

	/** @var string */
	public $schedule;

	/** @var string */
	public $max_tickets;

	/** @var string */
	public $booking_opening_date;

	/** @var string */
	public $booking_closing_date;

	/** @var string */
	public $booking_cancel_closing_date;

	/** @var string */
	public $booking_waiting_list;

	/** @var string */
	public $earlybird_discount;

	/** @var string */
	public $user_discount;

	/** @var string */
	public $events_discount;

	/** @var string */
	public $tickets_discount;

	/** @var string */
	public $booking_information;

	/** @var string */
	public $terms;

	/** @var string */
	public $params;

	/** @var string */
	public $metakey;

	/** @var string */
	public $metadesc;

	/** @var string */
	public $metadata;

	/** @var string */
	public $featured;

	/** @var ?string */
	public $publish_up;

	/** @var ?string */
	public $publish_down;

	/** @var ?string */
	public $payment_provider;

	/** @var int */
	public $capacity_used;

	/** @var ?string */
	public $checked_out_time;

	/** @var int */
	public $hits;

	/** @var int */
	public $checked_out;

	/** @var string */
	public $_update_modified;

	/** @var string */
	public $typeAlias = 'com_dpcalendar.event';

	/** @var array|string */
	public $newTags;

	/** @var array */
	public $location_ids;

	protected string $tableName = 'dpcalendar_events';
	protected $_columnAlias     = ['published' => 'state'];

	public function __construct(DatabaseDriver $db)
	{
		parent::__construct($db);

		// Set access flag as they are set in the base class already from props
		$params               = method_exists(Factory::getApplication(), 'getParams') ? Factory::getApplication()->getParams() : ComponentHelper::getParams('com_dpcalendar');
		$this->access         = $params->get('event_form_access', $this->access);
		$this->access_content = $params->get('event_form_access_content');
	}

	public function bind($data, $ignore = '')
	{
		$data = \is_object($data) ? get_object_vars($data) : $data;

		if (isset($data['params']) && \is_array($data['params'])) {
			$registry = new Registry();
			$registry->loadArray($data['params']);
			$data['params'] = (string)$registry;
		}

		if (isset($data['metadata']) && \is_array($data['metadata'])) {
			$registry = new Registry();
			$registry->loadArray($data['metadata']);
			$data['metadata'] = (string)$registry;
		}

		if (isset($data['rooms']) && \is_array($data['rooms'])) {
			$data['rooms'] = implode(',', $data['rooms']);
		}

		return parent::bind($data, $ignore);
	}

	public function store($updateNulls = false)
	{
		// Needs reset, so no caching of now
		Factory::$dates = [];
		$date           = DPCalendarHelper::getDate();
		$user           = $this->getCurrentUser();
		if ($this->id !== 0) {
			// Existing item
			$this->modified    = $date->toSql();
			$this->modified_by = $user->id;
		}
		if (!$this->id && (int)$this->created === 0) {
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
			$this->getDatabase()->setQuery('SELECT id, alias FROM #__dpcalendar_events WHERE alias = ' . $this->getDatabase()->quote($this->alias) . ' and id != ' . (int)$this->id);
			$table = $this->getDatabase()->loadObject();
			if (!$table || !$table->id) {
				break;
			}

			$this->alias = ApplicationHelper::stringURLSafe(StringHelper::increment($this->alias, 'dash'));
		}

		$start = DPCalendarHelper::getDate($this->start_date, (bool)$this->all_day);
		$end   = DPCalendarHelper::getDate($this->end_date, (bool)$this->all_day);
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
			$this->original_id = empty($this->rrule) ? 0 : -1;
		}
		if ($this->original_id > 0) {
			$this->rrule = null;
		}

		// Break never ending rules
		if (!empty($this->rrule) && !str_contains(strtoupper($this->rrule), 'UNTIL') && !str_contains(strtoupper($this->rrule), 'COUNT')) {
			$until = new \DateTime();
			$until->modify('+3 years');
			$this->rrule .= ';UNTIL=' . $until->format('Y') . '0101T000000Z';
		}

		$oldEvent    = new self($this->getDbo());
		$hardReset   = false;
		$tagsChanged = !empty($this->newTags);
		if ($this->id > 0) {
			$oldEvent->load($this->id);

			// If there is a new rrule or date configuration do a hard reset
			$hardReset = $this->all_day != $oldEvent->all_day || $this->start_date != $oldEvent->start_date || $this->end_date != $oldEvent->end_date || $this->rrule != $oldEvent->rrule || $this->exdates != $oldEvent->exdates;
			$oldTags   = new TagsHelper();
			$oldTags   = $oldTags->getItemTags('com_dpcalendar.event', (int)$this->id);
			$oldTags   = array_map(static fn ($t) => $t->id, $oldTags);

			$tagsChanged = empty($this->newTags) ? $oldTags != null : $this->newTags != $oldTags;

			if ($this->prices != $oldEvent->prices || $this->booking_options != $oldEvent->booking_options || ($hardReset && $this->rrule && $this->booking_series != 1)) {
				// Check for tickets
				$query = $this->getDatabase()->getQuery(true);
				$query->select('t.id')
					->from('#__dpcalendar_tickets as t')
					->join('LEFT', '#__dpcalendar_events as e on e.original_id=' . (int)$this->id)
					->where('(t.event_id = ' . (int)$this->id . ' or t.event_id = ' . (int)$this->original_id . ' or t.event_id = e.id)')
					->where('t.state >= 0');
				$this->getDatabase()->setQuery($query);
				if ($this->getDatabase()->loadResult()) {
					$this->all_day         = $oldEvent->all_day;
					$this->start_date      = $oldEvent->start_date;
					$this->end_date        = $oldEvent->end_date;
					$this->rrule           = $oldEvent->rrule;
					$this->exdates         = $oldEvent->exdates;
					$this->prices          = $oldEvent->prices;
					$this->booking_options = $oldEvent->booking_options;
					$hardReset             = false;

					Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
					Factory::getApplication()->enqueueMessage(Text::_('COM_DPCALENDAR_ERR_TABLE_NO_PRICE_CHANGE'), 'notice');
				}
			}
		}

		// Only delete the childs when a hard reset must be done
		if ($this->id > 0 && $hardReset) {
			$this->getDatabase()->setQuery('delete from #__dpcalendar_events where original_id = ' . (int)$this->id);
			$this->getDatabase()->execute();
		}

		// Null capacity for unlimited usage
		if ($this->capacity === '') {
			$this->capacity = null;
		}

		$isNew = empty($this->id);

		// Create the UID
		if (!$this->uid) {
			$this->uid = strtoupper(UUIDUtil::getUUID());
		}

		if (!empty($this->images) && $this->images !== '{}') {
			$images = $this->images instanceof \stdClass ? $this->images : json_decode($this->images);
			if (!$images instanceof \stdClass) {
				$images = new \stdClass();
			}

			if (!empty($images->image_intro)) {
				$path = JPATH_ROOT . '/' . $images->image_intro;
				if ($hashPos = strpos((string)$images->image_intro, '#')) {
					$path = JPATH_ROOT . '/' . substr((string)$images->image_intro, 0, $hashPos);
				}
				if (file_exists($path)) {
					$props                      = Image::getImageFileProperties($path);
					$images->image_intro_width  = $props->width;
					$images->image_intro_height = $props->height;
				}
			}

			if (!empty($images->image_full)) {
				$path = JPATH_ROOT . '/' . $images->image_full;
				if ($hashPos = strpos((string)$images->image_full, '#')) {
					$path = JPATH_ROOT . '/' . substr((string)$images->image_full, 0, $hashPos);
				}

				if (file_exists($path)) {
					$props                     = Image::getImageFileProperties($path);
					$images->image_full_width  = $props->width;
					$images->image_full_height = $props->height;
				}
			}

			$this->images = json_encode($images) ?: '';
		}

		// On some front end templates the color is set with none
		if ($this->color == 'none') {
			$this->color = '';
		}

		// Attempt to store the user data.
		$success = parent::store(true);
		if ($success && $this->catid) {
			Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->increaseEtag($this->catid);
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
			$untilDate = null;
			$rrule     = '';
			foreach (explode(';', strtoupper($this->rrule)) as $part) {
				if ($part === '' || $part === '0') {
					continue;
				}
				[$partName, $partValue] = explode('=', $part);

				if ($partName === 'UNTIL') {
					// Remove the timezone information, sabre assumes then the field is in user timezone
					$partValue = str_replace('Z', '', $partValue);
					$untilDate = (new \DateTime($partValue))->modify('+2 days');
				}

				$rrule .= $partName . '=' . $partValue . ';';
			}
			$text[] = 'RRULE:' . $rrule;

			if ($this->exdates) {
				$exdates = [];
				foreach (json_decode($this->exdates) as $date) {
					$dateObject   = DPCalendarHelper::getDate($date->date, true);
					$tzDateObject = clone $start;
					$tzDateObject->setDate((int)$dateObject->format('Y'), (int)$dateObject->format('m'), (int)$dateObject->format('d'));
					$exdates[] = $tzDateObject->format('Ymd\THis', true);
				}

				if ($exdates !== []) {
					$text[] = 'EXDATE;TZID=' . $userTz . ':' . implode(',', $exdates);
				}
			}

			$text[] = 'END:VEVENT';
			$text[] = 'END:VCALENDAR';

			/** @var VCalendar $cal */
			$cal = Reader::read(implode(PHP_EOL, $text));
			$cal = $cal->expand(new \DateTime($start->modify('-2 days')->format('Ymd')), $untilDate ?: new \DateTime('2038-01-01'));

			// @phpstan-ignore-next-line
			foreach ($cal->VEVENT ?? [] as $vevent) {
				$startDate = DPCalendarHelper::getDate($vevent->DTSTART->getDateTime()->format('U'), (bool)$this->all_day);
				$endDate   = DPCalendarHelper::getDate($vevent->DTEND->getDateTime()->format('U'), (bool)$this->all_day);

				$table = new self($this->getDbo());
				$table->bind((array)$this, ['id']);

				$table->alias         = ApplicationHelper::stringURLSafe($table->alias . '-' . $startDate->format('U'));
				$table->start_date    = $startDate->toSql();
				$table->recurrence_id = $startDate->format('Ymd' . ($table->all_day ? '' : '\THis\Z'));
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

				if (!empty($this->newTags)) {
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

		$query = $this->getDatabase()->getQuery(true);
		$query->update('#__dpcalendar_events');

		if (\is_array($this->rooms)) {
			$this->rooms = json_encode($this->rooms) ?: '';
		}
		$db = $this->getDatabase();

		// Fields to update
		$files = [
			'catid = ' . $db->quote($this->catid),
			'title = ' . $db->quote($this->title),
			'color = ' . $db->quote($this->color),
			'show_end_time = ' . $db->quote($this->show_end_time),
			'url = ' . $db->quote($this->url),
			'images = ' . $db->quote($this->images instanceof \stdClass ? (json_encode($this->images) ?: '') : $this->images),
			'description = ' . $db->quote($this->description),
			'schedule = ' . $db->quote($this->schedule),
			'capacity = ' . ($this->capacity === null ? 'NULL' : $db->quote($this->capacity)),
			'max_tickets = ' . $db->quote($this->max_tickets),
			'booking_opening_date = ' . $db->quote($this->booking_opening_date),
			'booking_closing_date = ' . $db->quote($this->booking_closing_date),
			'booking_cancel_closing_date = ' . $db->quote($this->booking_cancel_closing_date),
			'booking_series = ' . $db->quote($this->booking_series),
			'booking_waiting_list = ' . $db->quote($this->booking_waiting_list),
			'prices = ' . $db->quote($this->prices instanceof \stdClass ? (json_encode($this->prices) ?: '') : $this->prices),
			'earlybird_discount = ' . $db->quote($this->earlybird_discount),
			'user_discount = ' . $db->quote($this->user_discount),
			'events_discount = ' . $db->quote($this->events_discount),
			'tickets_discount = ' . $db->quote($this->tickets_discount),
			'booking_information = ' . $db->quote($this->booking_information),
			'terms = ' . $db->quote($this->terms),
			'state = ' . (int)$this->state,
			'checked_out = 0',
			'checked_out_time = null',
			'access = ' . $db->quote($this->access),
			'access_content = ' . $db->quote($this->access_content),
			'params = ' . $db->quote($this->params),
			'rooms = ' . $db->quote($this->rooms ?: ''),
			'language = ' . $db->quote($this->language),
			'modified = ' . ($this->modified ? $db->quote($this->modified) : 'null'),
			'modified_by = ' . (int)$user->id,
			'created_by = ' . (int)$this->created_by,
			'metakey = ' . $db->quote($this->metakey ?: ''),
			'metadesc = ' . $db->quote($this->metadesc ?: ''),
			'metadata = ' . $db->quote($this->metadata),
			'featured = ' . $db->quote($this->featured),
			'publish_up = ' . ($this->publish_up ? $db->quote($this->publish_up) : 'null'),
			'publish_down = ' . ($this->publish_down ? $db->quote($this->publish_down) : 'null'),
			'payment_provider = ' . ($this->payment_provider ? $db->quote($this->payment_provider) : 'null')
		];

		// If the xreference does exist, then we need to create it with the proper scheme
		if ($this->xreference) {
			// Replacing the _0 with the start date
			$files[] = 'xreference = concat(' . $db->quote($this->replaceLastInString('_0', '_', $this->xreference)) .
				", DATE_FORMAT(start_date, CASE WHEN all_day = '1' THEN '%Y%m%d' ELSE '%Y%m%d%H%i' END))";
		} else {
			$files[] = 'xreference = null';
		}

		// Reset capacity used only when the whole series can be booked
		if ($this->booking_series == 1) {
			$files[] = 'capacity_used = ' . (int)$this->capacity_used;
		}

		$query->set($files);
		$query->where('original_id = ' . (int)$this->id);

		if ($oldEvent->modified && $this->_update_modified !== null && $this->_update_modified == 0) {
			$query->where('(modified = ' . $db->quote($oldEvent->modified)
				. ' or modified is null)');
		}

		$db->setQuery($query);
		$db->execute();

		return $success;
	}

	public function check(): bool
	{
		if (!$this->start_date || InputFilter::checkAttribute(['start_date', $this->start_date])) {
			// @phpstan-ignore-next-line
			$this->setError(Text::_('COM_DPCALENDAR_ERR_TABLES_PROVIDE_START_DATE'));

			return false;
		}

		if (!$this->end_date || InputFilter::checkAttribute(['end_date', $this->end_date])) {
			// @phpstan-ignore-next-line
			$this->setError(Text::_('COM_DPCALENDAR_ERR_TABLES_PROVIDE_END_DATE'));

			return false;
		}

		// Check for valid name
		if (!$this->title || trim($this->title) === '') {
			// @phpstan-ignore-next-line
			$this->setError(Text::_('COM_DPCALENDAR_ERR_TABLES_TITLE') . ' [' . $this->catid . ']');

			return false;
		}

		if (empty($this->alias)) {
			$this->alias = $this->title;
		}

		$this->alias = ApplicationHelper::stringURLSafe($this->alias);
		if (trim(str_replace('-', '', $this->alias)) === '') {
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
			$bad_characters = ["\n", "\r", '"', "<", ">"];
			$after_clean    = utf8_ireplace($bad_characters, "", $this->metakey);
			$keys           = explode(',', $after_clean);
			$clean_keys     = [];
			foreach ($keys as $key) {
				if (trim($key) === '') {
					continue;
				}
				if (trim($key) === '0') {
					continue;
				}
				$clean_keys[] = trim($key);
			}
			$this->metakey = implode(", ", $clean_keys);
		}

		// Images can be an empty json string
		if (!$this->id && $this->images === null) {
			$this->images = '{}';
		}

		// Strict mode adjustments
		if (empty($this->capacity_used)) {
			$this->capacity_used = 0;
		}

		if (empty($this->created) || $this->created === $this->getDatabase()->getNullDate()) {
			$this->created = null;
		}
		if (empty($this->modified) || $this->modified === $this->getDatabase()->getNullDate()) {
			$this->modified = null;
		}
		if (empty($this->publish_up) || $this->publish_up === $this->getDatabase()->getNullDate()) {
			$this->publish_up = null;
		}
		if (empty($this->publish_down) || $this->publish_down === $this->getDatabase()->getNullDate()) {
			$this->publish_down = null;
		}
		if (empty($this->checked_out_time) || $this->checked_out_time === $this->getDatabase()->getNullDate()) {
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

		if (empty($this->payment_provider)) {
			$this->payment_provider = null;
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
			$this->getDatabase()->setQuery('delete from #__dpcalendar_events where original_id = ' . (int)$pk)->execute();
			$this->getDatabase()->setQuery('delete from #__dpcalendar_tickets where event_id = ' . (int)$pk)->execute();
		}

		if ($success && $this->catid) {
			Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->increaseEtag($this->catid);
		}

		return $success;
	}

	public function publish($pks = null, $state = 1, $userId = 0): bool
	{
		// Sanitize input
		$pks   = ArrayHelper::toInteger($pks);
		$state = (int)$state;

		// If there are no primary keys set check to see if the instance key is set
		if (empty($pks) && !$this->id) {
			throw new \Exception(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
		}

		if (empty($pks)) {
			$pks = [$this->id];
		}

		$now = DPCalendarHelper::getDate()->toSql();

		$query = $this->getDatabase()->getQuery(true);
		$query->update($this->_tbl);
		$query->set('state = :state')->bind(':state', $state, ParameterType::INTEGER);
		$query->set('modified = :modified')->bind(':modified', $now);
		$query->where('(checked_out = 0 OR checked_out = :user)')->bind(':user', $userId, ParameterType::INTEGER);

		// Build the WHERE clause for the primary keys
		$where = 'id = ' . implode(' OR id = ', $pks);

		// Add child events
		$where .= ' or original_id = ' . implode(' OR original_id =', $pks);

		$query->where('(' . $where . ')');
		$this->getDatabase()->setQuery($query)->execute();

		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator');
		foreach ($this->getDatabase()->setQuery('select distinct catid from #__dpcalendar_events where ' . $where)->loadColumn() as $calId) {
			$model->increaseEtag($calId);
		}

		// If checkin is supported and all rows were adjusted, check them in
		if (\count($pks) === $this->getDatabase()->getAffectedRows()) {
			// Checkin the rows
			foreach ($pks as $pk) {
				$this->checkin($pk);
			}
		}

		// If the Table instance value is in the list of primary keys that were set, set the instance
		if (\in_array($this->id, $pks)) {
			$this->state = $state;
		}

		return true;
	}

	public function book(bool $increment = true, ?int $pk = null): bool
	{
		if ($pk == null) {
			$pk = $this->id;
		}

		$query = $this->getDatabase()->getQuery(true);
		$query->update($this->_tbl);
		$query->set('capacity_used = (capacity_used ' . ($increment ? '+' : '-') . ' 1)');
		$query->where('(id = ' . (int)$pk . ' or (original_id = ' . (int)$pk . ' and booking_series = 1))');
		if (!$increment) {
			$query->where('capacity_used > 0');
		}
		$this->getDatabase()->setQuery($query);
		$this->getDatabase()->execute();

		if ($this->capacity_used === null) {
			$this->capacity_used = 0;
		}

		if ($increment) {
			$this->capacity_used++;
		} else {
			$this->capacity_used--;
		}

		return true;
	}

	public function populateTags(?array $newTags = null): void
	{
		$this->getDatabase()->setQuery('select * from #__dpcalendar_events where original_id = ' . (int)$this->id);
		foreach ($this->getDatabase()->loadAssocList() as $child) {
			$table = new self($this->getDbo());
			$table->bind($child);

			if ($newTags === null) {
				$newTags = $this->newTags;
			}

			$table->newTags = $newTags;
			$table->store();
		}
	}

	private function replaceLastInString(string $search, string $replace, string $str): string
	{
		if (($pos = strrpos($str, $search)) !== false) {
			$search_length = \strlen($search);
			$str           = substr_replace($str, $replace, $pos, $search_length);
		}

		return $str;
	}

	public function getTypeAlias()
	{
		return $this->typeAlias;
	}
}
