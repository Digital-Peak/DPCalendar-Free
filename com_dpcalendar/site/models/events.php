<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JLoader::import('components.com_dpcalendar.tables.event', JPATH_ADMINISTRATOR);

class DPCalendarModelEvents extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'hits', 'a.hits'];
		}

		parent::__construct($config);
	}

	public function getItems()
	{
		$items       = [];
		$categoryIds = $this->getState('category.id');
		if (!is_array($categoryIds)) {
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
		$options->set('location', $this->getState('filter.location', null));
		$options->set('location_ids', $this->getState('filter.locations', null));
		$options->set('radius', $this->getState('filter.radius', 20));
		$options->set('length-type', $this->getState('filter.length-type', 'm'));

		$containsExternalEvents = false;

		if (in_array('root', $categoryIds)) {
			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$categoryIds[] = $calendar->id;
					}
				}
			}
		}
		foreach ($categoryIds as $catId) {
			if (!$catId) {
				continue;
			}

			$calendar = DPCalendarHelper::getCalendar($catId);
			if (!$calendar || $calendar->native || (is_numeric($catId) && $catId != 'root')) {
				continue;
			}

			$startDate = null;
			if ($this->getState('list.start-date', null) !== null) {
				$startDate = DPCalendarHelper::getDate($this->getState('list.start-date'));
			}
			$endDate = null;
			if ($this->getState('list.end-date', null) !== null) {
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
			if ($categoryIds) {
				$dbItems = parent::getItems();
			}
			$items = array_merge($dbItems, $items);
			usort($items, [$this, 'compareEvent']);
			if ($this->getState('list.limit') > 0) {
				$items = array_slice($items, 0, $this->getState('list.limit'));
			}
		} else {
			$items = parent::getItems();
			if ($items && $this->getState('list.ordering', 'a.start_date') == 'a.start_date') {
				usort($items, [$this, 'compareEvent']);
			}
		}

		if (empty($items)) {
			return [];
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.ordering', 'ordering');
		$model->setState('list.direction', 'asc');

		foreach ($items as $key => $item) {
			// Initialize the parameters
			if (!isset($this->_params)) {
				$params = new Registry();
				$params->loadString($item->params ?: '');
				$item->params = $params;
			}

			// Add the locations
			if (!empty($item->location_ids) && empty($item->locations)) {
				$model->setState('filter.search', 'ids:' . $item->location_ids);
				$item->locations = $model->getItems();
			}

			// Set up the rooms
			$item->roomTitles = [];
			if (!empty($item->rooms)) {
				$item->rooms = is_array($item->rooms) ? $item->rooms : explode(',', $item->rooms);

				if ($item->locations) {
					foreach ($item->locations as $location) {
						if (empty($location->rooms)) {
							continue;
						}

						foreach ($item->rooms as $room) {
							list($locationId, $roomId) = explode('-', $room, 2);

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

			// If the event has no color, use the one from the calendar
			$calendar = DPCalendarHelper::getCalendar($item->catid);
			if (empty($item->color)) {
				$item->color = $calendar ? $calendar->color : '3366CC';
			}

			if (is_string($item->price)) {
				$item->price = json_decode($item->price);
			}

			if (is_string($item->booking_options)) {
				$item->booking_options = json_decode($item->booking_options);

				// Ensure min amount is properly set
				if (is_object($item->booking_options)) {
					foreach ($item->booking_options as $option) {
						if (empty($option->min_amount)) {
							$option->min_amount = 0;
						}
					}
				}
			}
			$item->booking_options = $item->booking_options ?: null;

			if (is_string($item->schedule)) {
				$item->schedule = json_decode($item->schedule);
			}
			$item->schedule = $item->schedule ?: null;

			// Implement View Level Access
			if (!Factory::getUser()->authorise('core.admin', 'com_dpcalendar')
				&& !in_array($item->access_content, Factory::getUser()->getAuthorisedViewLevels())
			) {
				$item->title               = Text::_('COM_DPCALENDAR_EVENT_BUSY');
				$item->location            = '';
				$item->locations           = [];
				$item->location_ids        = null;
				$item->rooms               = [];
				$item->url                 = '';
				$item->description         = '';
				$item->images              = null;
				$item->schedule            = [];
				$item->price               = null;
				$item->capacity            = 0;
				$item->capacity_used       = 0;
				$item->booking_information = '';
				$item->booking_options     = null;
			}

			\DPCalendar\Helper\DPCalendarHelper::parseImages($item);
			\DPCalendar\Helper\DPCalendarHelper::parseReadMore($item);
		}

		return $items;
	}

	protected function getListQuery()
	{
		$user   = Factory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select required fields from the categories.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from($db->quoteName('#__dpcalendar_events') . ' AS a');
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
			$query->select('t.id as booking');
			$query->join('LEFT', '#__dpcalendar_tickets AS t ON (t.event_id = a.id or t.event_id = a.original_id) and t.user_id = ' . (int)$user->id);
		}

		// Join to tickets to check if the event has waiting list tickets
		$query->select('count(DISTINCT wl.id) as waiting_list_count');
		$query->join('LEFT', '#__dpcalendar_tickets AS wl ON (wl.event_id = a.id or wl.event_id = a.original_id) and wl.state = 8');

		// Join on series max/min
		$query->select('ser.min_date AS series_min_start_date, ser.max_date AS series_max_end_date');
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
			if (!is_array($categoryIds)) {
				$categoryIds = [$categoryIds];
			}
			if (in_array('root', $categoryIds)) {
				PluginHelper::importPlugin('dpcalendar');
				$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
				if (!empty($tmp)) {
					foreach ($tmp as $tmpCalendars) {
						foreach ($tmpCalendars as $calendar) {
							$categoryIds[] = $calendar->id;
						}
					}
				}
			}
			$cats = [];
			foreach ($categoryIds as $categoryId) {
				if (!$categoryId) {
					continue;
				}
				$cats[$categoryId] = $db->q($categoryId);

				if (!$this->getState('category.recursive', false) || (!is_numeric($categoryId) && $categoryId != 'root')) {
					continue;
				}

				$cal = DPCalendarHelper::getCalendar($categoryId);
				if ($cal == null) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$cats[$child->id] = $db->q($child->id);
				}
			}
			if (!empty($cats)) {
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
		$state      = $this->getState('filter.state');
		$stateOwner = $user->id && $this->getState('filter.state_owner') ? ' or a.created_by = ' . $user->id : '';
		if (is_numeric($state)) {
			$query->where('(a.state = ' . (int)$state . $stateOwner . ')');
		} elseif (is_array($state)) {
			ArrayHelper::toInteger($state);
			$query->where('(a.state in (' . implode(',', $state) . ')' . $stateOwner . ')');
		}
		// Do not show trashed events on the front-end
		$query->where('a.state != -2');

		// Filter by start and end dates.
		$nullDate = $db->quote($db->getNullDate());
		$date     = Factory::getDate();
		$nowDate  = $db->quote($date->toSql());

		if ($this->getState('filter.publish_date')) {
			$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
			$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
		}

		$startDate     = $db->quote(DPCalendarHelper::getDate($this->getState('list.start-date'))->toSql());
		$dateCondition = 'a.start_date  >= ' . $startDate;
		if ($this->getState('list.end-date', null) !== null) {
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
			$query->where('a.language in (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
		}

		// Filter for featured events
		if ($this->getState('filter.featured')) {
			$query->where('a.featured = 1');
		}

		// Filter by title
		$searchString = $this->getState('filter.search');
		if (!empty($searchString)) {
			if (stripos($searchString, 'uid:') === 0) {
				$query->where('a.uid like ' . $this->getDbo()->quote(substr($searchString, 4)));
			} elseif (stripos($searchString, 'id:') === 0) {
				$ids = ArrayHelper::toInteger(explode(',', substr($searchString, 3)));
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
					$query->join('LEFT', '#__fields_values AS jf ON jf.item_id = ' . $query->castAsChar('a.id'))
						->join(
							'LEFT',
							'#__fields AS f ON f.id = jf.field_id and f.context = ' . $db->q('com_dpcalendar.event') . ' and f.state = 1 and f.access IN (' . $groups . ')'
						);
					$searchColumns[] = 'jf.value';
				}

				// Creating the search terms
				$searchTerms = explode(' ', \Joomla\String\StringHelper::strtolower($searchString));
				natsort($searchTerms);

				// Filtering the terms based on + - or none operators
				$must    = [];
				$mustNot = [];
				$can     = [];
				foreach ($searchTerms as $search) {
					if (!$search) {
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
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
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
				$locationsFilter[] = 0;
			}
		}

		// If we have a location filter apply it
		if ($locationsFilter) {
			$query->where('v.id in (' . implode(',', ArrayHelper::toInteger($locationsFilter)) . ')');
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
		if ($tagIds) {
			$query->join(
				'LEFT',
				$db->quoteName('#__contentitem_tag_map', 'tagmap') . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' .
				$db->quoteName('a.id') . ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_dpcalendar.event')
			);

			ArrayHelper::toInteger($tagIds);
			$query->where($db->quoteName('tagmap.tag_id') . ' in (' . implode(',', $tagIds) . ')');
		}

		if ($author = $this->getState('filter.author')) {
			// My events when author is -1
			$cond = 'a.created_by = ' . (int)($author == '-1' ? $user->id : $author);

			if ($author == '-1' && $user->id > 0 && !DPCalendarHelper::isFree()) {
				$cond .= ' or t.id is not null';
			}
			$query->where('(' . $cond . ')');
		}

		if ($hosts = $this->getState('filter.hosts')) {
			$hosts = is_array($hosts) ? $hosts : [$hosts];
			$query->where('relh.user_id in (' . implode(',', ArrayHelper::toInteger($hosts)) . ')');
		}

		if ($this->getState('filter.children', 0) > 0) {
			$query->where('a.original_id = ' . (int)$this->getState('filter.children', 0));
		}

		$search = $this->getState('filter.search_start');
		if (!empty($search)) {
			$search = $db->quote($db->escape($search, true));
			$query->where('a.start_date >= ' . $search);
		}

		$search = $this->getState('filter.search_end');
		if (!empty($search)) {
			$search = $db->quote($db->escape($search, true));
			$query->where('a.end_date <= ' . $search);
		}

		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'a.start_date')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		if ($this->getState('print.query', false) === true) {
			echo nl2br(str_replace('#__', 'j_', $query));
		}

		return $query;
	}

	private function buildSearchQuery($terms, $searchColumns, $termOperator)
	{
		if (!$terms) {
			return '';
		}
		$db = Factory::getDbo();

		$searchQuery = '';
		foreach ($terms as $termsKey => $search) {
			$searchQuery .= '(';
			$operator  = ' LIKE ';
			$condition = ' OR ';
			if (strpos($search, '-') === 0) {
				$search    = substr($search, 1);
				$operator  = ' NOT LIKE ';
				$condition = ' AND ';
			} elseif (strpos($search, '+') === 0) {
				$search = substr($search, 1);
			}

			$search = $db->q('%' . $db->escape($search, true) . '%');
			foreach ($searchColumns as $key => $column) {
				if ($key > 0) {
					$searchQuery .= $condition;
				}

				$externalColumn = strpos($column, 'a.') !== 0;
				if ($externalColumn && $termOperator == 'AND') {
					$searchQuery .= '(' . $column . ' IS NULL OR LOWER(' . $column . ')' . $operator . $search . ')';
				} else {
					$searchQuery .= 'LOWER(' . $column . ')' . $operator . $search;
				}
			}
			$searchQuery .= ')';

			if ($termsKey < count($terms) - 1) {
				$searchQuery .= ' ' . $termOperator . ' ';
			}
			$searchQuery .= PHP_EOL;
		}

		return '(' . $searchQuery . ')';
	}

	public function setStateFromParams(Registry $params)
	{
		// Filter for author
		$this->setState('filter.author', $params->get('calendar_filter_author', $params->get('list_filter_author', 0)));

		// Filter for locations
		$this->setState('filter.locations', $params->get('calendar_filter_locations', $params->get('list_filter_locations')));

		// Filter for tags
		$this->setState('filter.tags', $params->get('calendar_filter_tags', $params->get('list_filter_tags')));
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables
		$app    = Factory::getApplication();
		$params = method_exists($app, 'getParams') ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');

		// List state information
		if ($app->input->getInt('limit', null) === null) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
			$this->setState('list.limit', $limit);
		} else {
			$this->setState('list.limit', $app->input->getInt('limit', 0));
		}

		$this->setState('list.start-date', $app->input->get('date-start', DPCalendarHelper::getDate()->format('c')));
		if ($app->input->get('date-end')) {
			$this->setState('list.end-date', $app->input->get('date-end'));
		}

		$limitstart = $app->input->getInt('limitstart', 0);
		$this->setState('list.start', $limitstart);

		$orderCol = $app->input->getCmd('filter_order', 'start_date');
		if (!in_array($orderCol, $this->filter_fields)) {
			$orderCol = 'start_date';
		}
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->input->getCmd('filter_order_dir', 'ASC');
		if (!in_array(strtoupper($listOrder), ['ASC', 'DESC', ''])) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$id = $app->input->getString('ids', null);
		if ($id && !is_array($id)) {
			$id = explode(',', $id);
		}
		if (empty($id)) {
			$id = $params->get('ids');
		}
		$this->setState('category.id', $id);
		$this->setState('category.recursive', $app->input->getString('layout') == 'module');

		$user = Factory::getUser();
		if (!$user->authorise('core.edit.state', 'com_dpcalendar') && !$user->authorise('core.edit', 'com_dpcalendar')) {
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.state', [1, 3]);

			// Filter by start and end dates.
			$this->setState('filter.publish_date', true);
		}

		// On CalDAV requests, the language doesn't exist in the app
		$this->setState(
			'filter.language',
			$app->isClient('site') ? $app->getLanguageFilter() : ($app->getLanguage() ? $app->getLanguage()->getTag() : null)
		);
		$this->setState('filter.search', $this->getUserStateFromRequest('com_dpcalendar.filter.search', 'filter-search'));

		// Filter for
		$this->setState('filter.expand', true);

		// Filter for featured events
		$this->setState('filter.featured', false);

		$this->setStateFromParams($params);

		// Load the parameters.
		$this->setState('params', $params);
	}

	public function compareEvent($event1, $event2)
	{
		$first  = $event1;
		$second = $event2;
		if (strtolower($this->getState('list.direction', 'ASC')) == 'desc') {
			$first  = $event2;
			$second = $event1;
		}

		return strcmp($first->start_date, $second->start_date);
	}
}
