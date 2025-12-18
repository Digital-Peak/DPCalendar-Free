<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class EventsModel extends ListModel
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'id', 'a.id', 'title', 'a.title', 'search', 'location', 'length-type', 'radius', 'calendars', 'created_by', 'com_fields', 'tags', 'state'
			];
		}

		parent::__construct($config, $factory);
	}

	public function getItems()
	{
		$items       = [];
		$categoryIds = $this->getState('category.id');
		if (!\is_array($categoryIds)) {
			$categoryIds = [$categoryIds];
		}
		$options = new Registry();
		$search  = $this->getState('filter.search');
		if (!empty($search)) {
			$options->set('filter', $search);
		}
		if ($this->getState('list.limit') > 0) {
			$options->set('limit', $this->getState('list.limit'));
		}
		$options->set('order', $this->getState('list.direction', 'ASC'));
		$options->set('expand', $this->getState('filter.expand', '1'));
		$options->set('publish_date', $this->getState('filter.publish_date'));

		// Add location filter
		$options->set('location', $this->getState('filter.location'));
		$options->set('location_ids', $this->getState('filter.locations'));
		$options->set('radius', $this->getState('filter.radius', 20));
		$options->set('length-type', $this->getState('filter.length-type', 'm'));

		$containsExternalEvents = false;

		if (\in_array('root', $categoryIds)) {
			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$categoryIds[] = $calendar->getId();
					}
				}
			}
		}
		foreach ($categoryIds as $catId) {
			if (!$catId) {
				continue;
			}

			$calendar = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($catId);
			if (!$calendar instanceof ExternalCalendarInterface) {
				continue;
			}

			$startDate = null;
			if (!empty($this->getState('list.start-date'))) {
				$startDate = DPCalendarHelper::getDate($this->getState('list.start-date'));
			}
			$endDate = null;
			if (!empty($this->getState('list.end-date'))) {
				$endDate = DPCalendarHelper::getDate($this->getState('list.end-date'));
			}

			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent('onEventsFetch', [$catId, $startDate, $endDate, $options]);
			if (!empty($tmp)) {
				$containsExternalEvents = true;
				foreach ($tmp as $events) {
					if (!$events) {
						continue;
					}

					foreach ($events as $event) {
						$items[] = $event;
					}
				}
			}
		}
		if ($containsExternalEvents) {
			$dbItems = [];
			if ($categoryIds !== []) {
				$dbItems = parent::getItems();
			}
			$items = array_merge($dbItems, $items);
			usort($items, fn (\stdClass $event1, \stdClass $event2): int => $this->compareEvent($event1, $event2));
			if ($this->getState('list.limit') > 0) {
				$items = \array_slice($items, 0, $this->getState('list.limit'));
			}
		} else {
			$items = parent::getItems();
			if ($items && $this->getState('list.ordering', 'a.start_date') == 'a.start_date') {
				usort($items, fn (\stdClass $event1, \stdClass $event2): int => $this->compareEvent($event1, $event2));
			}
		}

		if (empty($items)) {
			return [];
		}

		$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.ordering', 'ordering');
		$model->setState('list.direction', 'asc');

		foreach ($items as $item) {
			// Initialize the parameters
			if (!property_exists($this, '_params') || $this->_params === null) {
				$params = new Registry();
				$params->loadString($item->params ?: '');
				$item->params = $params;
			}

			// Add the locations
			$item->locations = empty($item->locations) || $this->getState('filter.locations', []) ? [] : $item->locations;
			if (!empty($item->location_ids) && empty($item->locations)) {
				$model->setState('filter.search', 'ids:' . $item->location_ids);
				$item->locations = $model->getItems();
			}

			// Set up the rooms
			$item->roomTitles = [];
			if (!empty($item->rooms)) {
				$item->rooms = \is_array($item->rooms) ? $item->rooms : explode(',', (string)$item->rooms);

				if ($item->locations) {
					/** @var \stdClass $location */
					foreach ($item->locations as $location) {
						if (empty($location->rooms)) {
							continue;
						}

						foreach ($item->rooms as $room) {
							[$locationId, $roomId] = explode('-', (string)$room, 2);

							foreach ($location->rooms as $lroom) {
								if ($locationId != $location->id || $roomId != $lroom->id) {
									continue;
								}

								$item->roomTitles[$locationId][$room] = $lroom->title;
							}
						}
					}
				}
			} else {
				$item->rooms = [];
			}

			$item->color = str_replace('#', '', $item->color ?? '');
			if (\is_array($item->color)) {
				$item->color = implode('', $item->color);
			}

			// If the event has no color, use the one from the calendar
			$calendar = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($item->catid);
			if ($item->color === '' || $item->color === '0') {
				$item->color = $calendar instanceof CalendarInterface ? str_replace('#', '', $calendar->getColor()) : '3366CC';
			}

			// Check if it is a valid color
			if ((\strlen($item->color) !== 6 && \strlen($item->color) !== 3) || !ctype_xdigit($item->color)) {
				$item->color = '3366CC';
			}

			if (\is_string($item->exdates)) {
				$item->exdates = ArrayHelper::getColumn((array)json_decode($item->exdates), 'date');
			}

			if (\is_string($item->prices)) {
				$item->prices = json_decode($item->prices);
			}

			if (\is_string($item->earlybird_discount)) {
				$item->earlybird_discount = json_decode($item->earlybird_discount);
			}

			if (\is_string($item->user_discount)) {
				$item->user_discount = json_decode($item->user_discount);
			}

			if (\is_string($item->events_discount)) {
				$item->events_discount = json_decode($item->events_discount);
			}

			if (\is_string($item->tickets_discount)) {
				$item->tickets_discount = json_decode($item->tickets_discount);
			}

			if (\is_string($item->booking_options)) {
				$item->booking_options = json_decode($item->booking_options);

				// Ensure min amount is properly set
				if (!empty($item->booking_options)) {
					foreach ((array)$item->booking_options as $option) {
						if (empty($option->min_amount)) {
							$option->min_amount = 0;
						}
					}
				}
			}
			$item->booking_options = $item->booking_options ?: null;

			if (\is_string($item->schedule)) {
				$item->schedule = json_decode($item->schedule);
			}
			$item->schedule = $item->schedule ?: null;

			// Implement View Level Access
			$user = $this->getCurrentUser();
			if (!$user->authorise('core.admin', 'com_dpcalendar') && !\in_array($item->access_content, $user->getAuthorisedViewLevels())
				// Special access content flag, where only the logged in user can see the content of his own event
				&& ($item->access_content > -1 || $item->created_by != $user->id)) {
				$item->title               = Text::_('COM_DPCALENDAR_EVENT_BUSY');
				$item->location            = '';
				$item->locations           = [];
				$item->location_ids        = null;
				$item->rooms               = [];
				$item->url                 = '';
				$item->description         = '';
				$item->images              = null;
				$item->schedule            = [];
				$item->prices              = null;
				$item->capacity            = 0;
				$item->capacity_used       = 0;
				$item->booking_information = '';
				$item->booking_options     = null;
			}

			DPCalendarHelper::parseImages($item);
			DPCalendarHelper::parseReadMore($item);
		}

		return $items;
	}

	protected function getListQuery()
	{
		$user   = $this->getCurrentUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		// Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		// Select required fields from the categories.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from('#__dpcalendar_events AS a');
		$query->where('a.access IN (' . $groups . ')');

		// Don't show original events
		$expand = $this->getState('filter.expand');
		if ($expand && $expand !== 'all') {
			$query->where('a.original_id > -1');
		} elseif (!$expand) {
			$query->where('a.original_id in (-1, 0)');
		}

		// Join over the categories
		$query->join('LEFT', '#__categories AS c ON c.id = a.catid');

		if ($user->id > 0 && !DPCalendarHelper::isFree()) {
			// Join to tickets to add a field if the logged in user has a ticket for the event
			$query->select('max(t.id) as booking');
			$query->join('LEFT', '#__dpcalendar_tickets AS t ON (t.event_id = a.id or t.event_id = a.original_id) and t.user_id = ' . (int)$user->id);
		}

		// Join to tickets to check if the event has waiting list tickets
		$query->select('count(DISTINCT wl.id) as waiting_list_count');
		$query->join('LEFT', '#__dpcalendar_tickets AS wl ON (wl.event_id = a.id or wl.event_id = a.original_id) and wl.state = 8');

		// Join on series max/min
		$query->select('min(ser.min_date) AS series_min_start_date, max(ser.max_date) AS series_max_end_date');
		$query->join(
			'LEFT',
			'(select original_id, max(end_date) as max_date, min(start_date) as min_date from #__dpcalendar_events group by original_id) ser on ' . ($this->getState('filter.expand') ? 'ser.original_id = a.original_id and a.original_id > 0' : 'ser.original_id = a.id')
		);

		// Join over the original
		$query->select('o.title as original_title, o.rrule as original_rrule');
		$query->join('LEFT', '#__dpcalendar_events AS o ON o.id = a.original_id');

		// Join locations
		$query->select("GROUP_CONCAT(DISTINCT v.id SEPARATOR ', ') location_ids");
		$query->join('LEFT', '#__dpcalendar_events_location AS rel ON a.id = rel.event_id');
		$query->join('LEFT', '#__dpcalendar_locations AS v ON rel.location_id = v.id');
		$query->group('a.id');

		// Join hosts
		$query->select("GROUP_CONCAT(DISTINCT uh.id SEPARATOR ',') host_ids");
		$query->join('LEFT', '#__dpcalendar_events_hosts AS relh ON a.id = relh.event_id');
		$query->join('LEFT', '#__users AS uh ON relh.user_id = uh.id');

		// Filter by category
		if ($categoryIds = $this->getState('category.id', 0)) {
			if (!\is_array($categoryIds)) {
				$categoryIds = [$categoryIds];
			}
			if (\in_array('root', $categoryIds)) {
				PluginHelper::importPlugin('dpcalendar');
				$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
				if (!empty($tmp)) {
					foreach ($tmp as $tmpCalendars) {
						foreach ($tmpCalendars as $calendar) {
							$categoryIds[] = $calendar->getId();
						}
					}
				}
			}
			$cats = [];
			foreach ($categoryIds as $categoryId) {
				if (!$categoryId) {
					continue;
				}
				$cats[$categoryId] = $db->quote($categoryId);

				if (!$this->getState('category.recursive', false) || (!is_numeric($categoryId) && $categoryId != 'root')) {
					continue;
				}

				$cal = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($categoryId);
				if ($cal == null) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$cats[$child->getId()] = $db->quote($child->getId());
				}
			}
			if ($cats !== []) {
				$query->where('a.catid IN (' . implode(',', $cats) . ')');
			}

			$query->where('(c.access IN (' . $groups . ') or c.access is null)');

			// Filter by published category
			$cpublished = $this->getState('filter.c.published');
			if (is_numeric($cpublished)) {
				$query->where('c.published = ' . (int)$cpublished);
			}
		}
		// Join over the users for the author and modified_by names.
		$query->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author");

		$query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');
		$query->join('LEFT', '#__users AS uam ON uam.id = a.modified_by');

		// Filter by state
		$state = $this->getState('filter.state', []);
		if (!\is_array($state) && $state !== '' && $state !== null) {
			$state = [$state];
		}
		$stateOwner = $user->id && $this->getState('filter.state_owner') ? ' or a.created_by = ' . $user->id : '';

		if (!\in_array($state, [[], '', null], true)) {
			$query->where('(a.state in (' . implode(',', ArrayHelper::toInteger($state)) . ')' . $stateOwner . ')');
		} else {
			// Do not show trashed events on the front-end
			$query->where('a.state != -2');
		}

		// Filter by start and end dates
		$date    = Factory::getDate();
		$nowDate = $db->quote($date->toSql());

		if ($this->getState('filter.publish_date')) {
			$query->where('(a.publish_up is null OR a.publish_up <= ' . $nowDate . ')');
			$query->where('(a.publish_down is null OR a.publish_down >= ' . $nowDate . ')');
		}

		$startDate     = $db->quote(DPCalendarHelper::getDate($this->getState('list.start-date'))->toSql());
		$dateCondition = 'a.start_date  >= ' . $startDate;
		if (!empty($this->getState('list.end-date'))) {
			$endDate       = $db->quote(DPCalendarHelper::getDate($this->getState('list.end-date'))->toSql());
			$dateCondition = '(a.end_date between ' . $startDate . ' and ' . $endDate . ' or a.start_date between ' . $startDate . ' and ' . $endDate .
				' or (a.start_date < ' . $startDate . ' and a.end_date > ' . $endDate . '))';
		}

		if ($this->getState('list.local-date')) {
			$startDate     = DPCalendarHelper::getDate($this->getState('list.start-date'));
			$endDate       = DPCalendarHelper::getDate($this->getState('list.end-date'));
			$dateCondition = '(';

			// Timed events must be searched in UTC timezone
			$startDateString = $db->quote($startDate->toSql());
			$endDateString   = $db->quote($endDate->toSql());
			$dateCondition .= '(a.all_day = 0 and a.start_date between ' . $startDateString . ' and ' . $endDateString . ') ';
			$dateCondition .= 'or (a.all_day = 0 and a.end_date between ' . $startDateString . ' and ' . $endDateString . ') ';
			$dateCondition .= 'or (a.all_day = 0 and a.start_date < ' . $startDateString . ' and a.end_date > ' . $endDateString . ') ';

			// We need to use it in the user timezone to match the correct day when the day shifts because of the timezone
			$startDateString = $db->quote($startDate->format('Y-m-d', true));
			$endDateString   = $db->quote($endDate->format('Y-m-d', true));
			$dateCondition .= 'or (a.all_day = 1 and a.start_date between ' . $startDateString . ' and ' . $endDateString . ') ';
			$dateCondition .= 'or (a.all_day = 1 and a.end_date between ' . $startDateString . ' and ' . $endDateString . ') ';
			$dateCondition .= 'or (a.all_day = 1 and a.start_date < ' . $startDateString . ' and a.end_date > ' . $endDateString . ')';

			$dateCondition .= ')';
		}

		if ($this->getState('filter.ongoing', 0) == 1) {
			$now = DPCalendarHelper::getDate();
			$dateCondition .= ' or ' . $db->quote($now->toSql()) . ' between a.start_date and a.end_date';
			$dateCondition .= ' or (a.start_date=' . $db->quote($now->format('Y-m-d')) . ' and a.all_day=1)';
			$dateCondition .= ' or (a.end_date=' . $db->quote($now->format('Y-m-d')) . ' and a.all_day=1)';

			if (!$this->getState('filter.expand')) {
				$dateCondition .= ' or ' . $db->quote($now->toSql()) . ' between ser.min_date and ser.max_date';
			}
		}
		$query->where('(' . $dateCondition . ')');

		// Filter by language
		if ($this->getState('filter.language')) {
			$query->where('a.language in (' . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
		}

		// Filter for featured events
		if ($featured = $this->getState('filter.featured')) {
			$query->where('a.featured = ' . ($featured === '2' ? '0' : '1'));
		}

		// Filter by title
		$searchString = $this->getState('filter.search');
		if (!empty($searchString)) {
			if (stripos((string)$searchString, 'uid:') === 0) {
				$query->where('a.uid like ' . $this->getDatabase()->quote(substr((string)$searchString, 4)));
			} elseif (stripos((string)$searchString, 'id:') === 0) {
				$ids = ArrayHelper::toInteger(explode(',', substr((string)$searchString, 3)));
				$query->where('a.id in (' . implode(',', $ids) . ')');
			} else {
				// Immitating simple boolean search
				$searchColumns = $this->getState('filter.search.columns', [
					'v.title',
					'CONCAT_WS(v.`country`,",",v.`province`,",",v.`city`,",",v.`zip`,",",v.`street`)',
					'a.title',
					'a.alias',
					'a.description',
					'a.metakey',
					'a.metadesc'
				]);

				// Only add custom fields when there is default search
				if (!$this->getState('filter.search.columns')) {
					// Search in custom fields
					// Join over Fields.
					$query->join('LEFT', '#__fields_values AS jf ON jf.item_id = ' . $query->castAs('CHAR', 'a.id'))
						->join(
							'LEFT',
							'#__fields AS f ON f.id = jf.field_id and f.context = ' . $db->quote('com_dpcalendar.event') . ' and f.state = 1 and f.access IN (' . $groups . ')'
						);
					$searchColumns[] = 'jf.value';
				}

				// Creating the search terms
				$searchTerms = explode(' ', (string)StringHelper::strtolower($searchString));
				natsort($searchTerms);

				// Filtering the terms based on + - or none operators
				$must    = [];
				$mustNot = [];
				$can     = [];
				foreach ($searchTerms as $search) {
					if ($search === '' || $search === '0') {
						continue;
					}
					switch (substr($search, 0, 1)) {
						case '+':
							$must[] = $search;
							break;
						case '-':
							$mustNot[] = $search;
							break;
						default:
							$can[] = $search;
					}
				}
				$searchQuery = $this->buildSearchQuery($must, $searchColumns, 'AND');

				if ($must && $mustNot) {
					$searchQuery .= ' AND ';
				}
				$searchQuery .= $this->buildSearchQuery($mustNot, $searchColumns, 'AND');

				if ($can && ($must || $mustNot)) {
					$searchQuery .= ' AND ';
				}
				$searchQuery .= $this->buildSearchQuery($can, $searchColumns, 'OR');
				$query->where('(' . $searchQuery . ')');
			}
		}

		// The locations to filter for
		$locationsFilter = $this->getState('filter.locations', []);

		// Search for a location
		$location = $this->getState('filter.location');
		if ($location) {
			$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
			$model->getState();
			$model->setState('filter.location', $location);
			$model->setState('filter.radius', $this->getState('filter.radius', 20));
			$model->setState('filter.length-type', $this->getState('filter.length-type', 'm'));
			$model->setState('list.ordering', 'ordering');
			$model->setState('list.direction', 'asc');
			foreach ($model->getItems() as $l) {
				$locationsFilter[] = $l->id;
			}

			if (empty($locationsFilter)) {
				$locationsFilter = [0];
			}
		}

		// If we have a location filter apply it
		if ($locationsFilter) {
			$query->where(
				$locationsFilter != '-1' && (!\is_array($locationsFilter) || !\in_array('-1', $locationsFilter))
				? 'v.id in (' . implode(',', ArrayHelper::toInteger($locationsFilter)) . ')'
				: 'v.id is not null'
			);
		}

		// Filter rooms
		if ($rooms = $this->getState('filter.rooms')) {
			$conditions = [];
			foreach ((array)$rooms as $room) {
				$conditions[] = 'a.rooms like ' . $db->quote($db->escape('%' . $room . '%'));
			}
			$query->where('(' . implode(' or ', $conditions) . ')');
		}

		// Filter by tags
		$tagIds = (array)$this->getState('filter.tags');
		if ($tagIds !== []) {
			$query->join(
				'LEFT',
				'#__contentitem_tag_map tagmap ON tagmap.content_item_id = a.id AND tagmap.type_alias = ' . $db->quote('com_dpcalendar.event')
			);

			$query->where('tagmap.tag_id in (' . implode(',', ArrayHelper::toInteger($tagIds)) . ')');
		}

		if ($author = $this->getState('filter.author', 0)) {
			$author = \is_array($author) ? ArrayHelper::toInteger($author) : [$author];
			if (\in_array(-1, $author)) {
				$author[] = $user->id;
				$author   = array_filter($author, static fn ($a): bool => $a != '-1');
			}
			// My events when author is -1
			$cond = 'a.created_by in (' . implode(',', $author) . ')';

			if (!\in_array(-1, $author) && $user->id > 0 && !DPCalendarHelper::isFree()) {
				$cond .= ' or t.id is not null';
			}

			$query->where('(' . $cond . ')');
		}

		if ($hosts = $this->getState('filter.hosts')) {
			$hosts = \is_array($hosts) ? $hosts : [$hosts];
			$query->where('relh.user_id in (' . implode(',', ArrayHelper::toInteger($hosts)) . ')');
		}

		if ($this->getState('filter.children', 0) > 0) {
			$query->where('a.original_id = ' . (int)$this->getState('filter.children', 0));
		}

		$this->searchInFields($query);

		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'a.start_date')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		if ($this->getState('print.query', false) === true) {
			echo nl2br(implode('', (array)str_replace('#__', 'j_', $query)));
		}

		return $query;
	}

	private function buildSearchQuery(array $terms, array $searchColumns, string $termOperator): string
	{
		if ($terms === []) {
			return '';
		}

		$db = $this->getDatabase();

		$searchQuery = '';
		foreach ($terms as $termsKey => $search) {
			$searchQuery .= '(';
			$operator  = ' LIKE ';
			$condition = ' OR ';
			if (str_starts_with((string)$search, '-')) {
				$search    = substr((string)$search, 1);
				$operator  = ' NOT LIKE ';
				$condition = ' AND ';
			} elseif (str_starts_with((string)$search, '+')) {
				$search = substr((string)$search, 1);
			}

			$search = $db->quote('%' . $db->escape($search, true) . '%');
			foreach ($searchColumns as $key => $column) {
				if ($key > 0) {
					$searchQuery .= $condition;
				}

				$externalColumn = !str_starts_with((string)$column, 'a.');
				if ($externalColumn && $termOperator === 'AND') {
					$searchQuery .= '(' . $column . ' IS NULL OR LOWER(' . $column . ')' . $operator . $search . ')';
				} else {
					$searchQuery .= 'LOWER(' . $column . ')' . $operator . $search;
				}
			}
			$searchQuery .= ')';

			if ($termsKey < \count($terms) - 1) {
				$searchQuery .= ' ' . $termOperator . ' ';
			}
			$searchQuery .= PHP_EOL;
		}

		return '(' . $searchQuery . ')';
	}

	public function setStateFromParams(Registry $params): void
	{
		// Filter for author
		$author = $params->get('calendar_filter_author', $params->get('list_filter_author'));
		if ((int)$author !== 0) {
			$this->setState('filter.author', $author);
		}

		// Filter for locations
		$this->setState('filter.locations', $params->get('calendar_filter_locations', $params->get('list_filter_locations')));

		// Filter for tags
		$this->setState('filter.tags', $params->get('calendar_filter_tags', $params->get('list_filter_tags')));
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Initialize variables
		$app    = Factory::getApplication();
		$params = $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');

		// List state information
		if ($app->getInput()->getInt('limit', 0) === 0 && $app instanceof SiteApplication) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
			$this->setState('list.limit', $limit);
		} else {
			$this->setState('list.limit', $app->getInput()->getInt('limit', 0));
		}

		$this->setState('list.start-date', $app->getInput()->get('date-start', DPCalendarHelper::getDate()->format('c')));
		if ($app->getInput()->get('date-end')) {
			$this->setState('list.end-date', $app->getInput()->get('date-end'));
		}

		$limitstart = $app->getInput()->getInt('limitstart', 0);
		$this->setState('list.start', $limitstart);

		$orderCol = $app->getInput()->getCmd('filter_order', 'start_date');
		if (!\in_array($orderCol, $this->filter_fields)) {
			$orderCol = 'start_date';
		}
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->getInput()->getCmd('filter_order_dir', 'ASC');
		if (!\in_array(strtoupper((string)$listOrder), ['ASC', 'DESC', ''])) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$id = $app->getInput()->getString('ids', '');
		if ($id) {
			$id = explode(',', (string)$id);
		}
		if (empty($id)) {
			$id = $params->get('ids');
		}
		$this->setState('category.id', $id);
		$this->setState('category.recursive', $app->getInput()->getString('layout', '') == 'module');

		$user = $this->getCurrentUser();
		if (!$user->authorise('core.edit.state', 'com_dpcalendar') && !$user->authorise('core.edit', 'com_dpcalendar')) {
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.state', [1, 3]);

			// Filter by start and end dates.
			$this->setState('filter.publish_date', true);
		}

		$this->setState(
			'filter.language',
			// On CalDAV requests, the language doesn't exist in the app
			// @phpstan-ignore-next-line
			$app instanceof SiteApplication ? $app->getLanguageFilter() : ($app->getLanguage() ? $app->getLanguage()->getTag() : null)
		);
		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter-search'));

		$this->setState('filter.author', $this->getUserStateFromRequest($this->context . '.filter.author', 'filter_created_by'));

		// Filter for
		$this->setState('filter.expand', true);

		// Filter for featured events
		$this->setState('filter.featured', false);

		$this->setStateFromParams($params);

		// Load the parameters.
		$this->setState('params', $params);

		parent::populateState($ordering, $direction);

		$calendars = (array)$this->getUserStateFromRequest($this->context . '.filter.calendars', 'filter_calendars');
		if ($calendars !== []) {
			$this->setState('filter.calendars', array_filter($calendars, static fn ($c): bool => !empty($c)));
		}
	}

	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		parent::preprocessForm($form, $data, $group);

		FieldsHelper::prepareForm('com_dpcalendar.event', $form, new \stdClass());

		foreach ($form->getGroup('com_fields') as $field) {
			if ($field->type === 'Text') {
				$form->setFieldAttribute($field->fieldname, 'disabled', false, $field->group);
				$form->setFieldAttribute($field->fieldname, 'readonly', false, $field->group);

				if (!$field->hint) {
					$form->setFieldAttribute($field->fieldname, 'hint', trim(strip_tags($field->label)), $field->group);
				}

				continue;
			}

			$form->removeField($field->fieldname, $field->group);
		}
	}

	public function getActiveFilters()
	{
		$filters = parent::getActiveFilters();

		if ($startDate = $this->getState('list.start-date')) {
			$filters['start-date'] = $startDate;
		}

		if ($endDate = $this->getState('list.end-date')) {
			$filters['end-date'] = $endDate;
		}

		if (\array_key_exists('com_fields', $filters)) {
			$filters['com_fields'] = array_filter($filters['com_fields']);
		}

		if (\array_key_exists('state', $filters) && $filters['state'] === [1, 3]) {
			unset($filters['state']);
		}

		return $filters;
	}

	protected function loadFormData()
	{
		$data = parent::loadFormData();
		if ($data instanceof \stdClass && !empty($data->filter) && \array_key_exists('com_fields', $data->filter)) {
			$data->com_fields = $data->filter['com_fields'];
		}

		return $data;
	}

	private function searchInFields(QueryInterface $mainQuery): void
	{
		$fields = $this->getState('filter.com_fields');
		if (!$fields) {
			return;
		}

		$customFields = array_filter(FieldsHelper::getFields('com_dpcalendar.event'), fn ($f): bool => \array_key_exists($f->name, $fields));
		if ($customFields === []) {
			return;
		}

		$params   = $this->getState('params') ?: new Registry();
		$fieldIds = array_map(fn ($f) => $f->id, $customFields);

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);
		$query->select('item_id')->from('#__fields_values');

		$conditions = [];
		foreach ($fields as $value) {
			// When no search value, then ignore
			if (!$value) {
				continue;
			}

			// Ensure value is an array
			if (!\is_array($value)) {
				$value = [$value];
			}

			// Search in value column
			$condition = 'field_id in (' . implode(',', $fieldIds) . ')';

			// The search for the actual value
			$fieldConditions = [];
			foreach ($value as $v) {
				$c = '(value like ' . $db->quote('%' . $v . '%');

				// Is needed for subform search which is stored in ASCI
				// Umlauts like ä are stored like \u00e4 so the value needs to be converted into this format
				$c .= ' or value like ' . $db->quote('%' . str_replace('\\', '\\\\', trim(json_encode($v) ?: '', '"')) . '%') . ')';

				$fieldConditions[] = $c;
			}

			// When or combined then do normal in
			if ($params->get('conditions_glue_lists', 'or') === 'or') {
				$condition .= ' and ' . implode(' or ', $fieldConditions);
			} else {
				// And combined needs a subquery
				foreach ($fieldConditions as $c) {
					$c .= ' and field_id in (' . implode(',', $fieldIds) . ')';
					$condition .= ' and item_id in (SELECT item_id FROM #__fields_values where ' . $c . ')';
				}
			}

			// Create the values array
			$conditions[] = '(' . $condition . ')';
		}

		if ($conditions !== []) {
			// When or combined then do normal in
			if ($params->get('conditions_glue_fields', 'or') === 'or') {
				$query->where(implode(' or ', $conditions));
			} else {
				// And combined needs a subquery
				foreach ($conditions as $condition) {
					$query->where('item_id in (SELECT item_id FROM #__fields_values where ' . $condition . ')');
				}
			}

			// Group them for unique values
			$query->group('item_id');

			$db->setQuery($query);

			// Get the article ids
			$tmp = array_values(ArrayHelper::flatten($db->loadAssocList()));
			if ($tmp === []) {
				$tmp = [-1];
			}

			$mainQuery->where('a.id in (' . implode(',', $tmp) . ')');
		}
	}

	private function compareEvent(\stdClass $event1, \stdClass $event2): int
	{
		$first  = $event1;
		$second = $event2;
		if (strtolower((string)$this->getState('list.direction', 'ASC')) === 'desc') {
			$first  = $event2;
			$second = $event1;
		}

		return strcmp((string)$first->start_date, (string)$second->start_date);
	}
}
