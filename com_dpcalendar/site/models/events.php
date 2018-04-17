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

JLoader::import('joomla.application.component.modellist');
JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JLoader::import('components.com_dpcalendar.tables.event', JPATH_ADMINISTRATOR);

class DPCalendarModelEvents extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'title',
				'a.title',
				'hits',
				'a.hits'
			);
		}

		parent::__construct($config);
	}

	public function getItems()
	{
		$items       = array();
		$categoryIds = $this->getState('category.id');
		if (!is_array($categoryIds)) {
			$categoryIds = array($categoryIds);
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

		// Add location filter
		$options->set('location', $this->getState('filter.location', null));
		$options->set('location_ids', $this->getState('filter.locations', null));
		$options->set('radius', $this->getState('filter.radius', 20));
		$options->set('length-type', $this->getState('filter.length-type', 'm'));

		$containsExternalEvents = false;

		if (in_array('root', $categoryIds)) {
			JPluginHelper::importPlugin('dpcalendar');
			$tmp = JFactory::getApplication()->triggerEvent('onCalendarsFetch');
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$categoryIds[] = $calendar->id;
					}
				}
			}
		}
		foreach ($categoryIds as $catId) {
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

			JPluginHelper::importPlugin('dpcalendar');
			$tmp = JFactory::getApplication()->triggerEvent('onEventsFetch', array($catId, $startDate, $endDate, $options));
			if (!empty($tmp)) {
				$containsExternalEvents = true;
				foreach ($tmp as $events) {
					foreach ($events as $event) {
						$items[] = $event;
					}
				}
			}
		}
		if ($containsExternalEvents) {
			$dbItems = array();
			if ($categoryIds) {
				$dbItems = parent::getItems();
			}
			$items = array_merge($dbItems, $items);
			usort($items, array($this, "compareEvent"));
			if ($this->getState('list.limit') > 0) {
				$items = array_slice($items, 0, $this->getState('list.limit'));
			}
		} else {
			$items = parent::getItems();
			if ($items) {
				usort($items, array($this, "compareEvent"));
			}
		}

		if (empty($items)) {
			return array();
		}

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
		$model->getState();
		$model->setState('list.ordering', 'ordering');
		$model->setState('list.direction', 'asc');

		foreach ($items as $key => $item) {
			// Initialize the parameters
			if (!isset($this->_params)) {
				$params = new Registry();
				$params->loadString($item->params);
				$item->params = $params;
			}

			// Add the locations
			if (!empty($item->location_ids) && empty($item->locations)) {
				$model->setState('filter.search', 'ids:' . $item->location_ids);
				$item->locations = $model->getItems();
			}

			// Set up the rooms
			if (!empty($item->rooms)) {
				$item->rooms = explode(',', $item->rooms);
			} else {
				$item->rooms = [];
			}

			// If the event has no color, use the one from the calendar
			$calendar = DPCalendarHelper::getCalendar($item->catid);
			if (empty($item->color)) {
				if (!$calendar) {
					$item->color = '3366CC';
				} else {
					$item->color = $calendar->color;
				}
			}

			if (is_string($item->price)) {
				$item->price = json_decode($item->price);
			}

			// Implement View Level Access
			if (!JFactory::getUser()->authorise('core.admin', 'com_dpcalendar')
				&& !in_array($item->access_content, JFactory::getUser()->getAuthorisedViewLevels())
			) {
				$item->title       = JText::_('COM_DPCALENDAR_EVENT_BUSY');
				$item->location    = '';
				$item->url         = '';
				$item->description = '';
				$item->price       = array();
			}
		}

		return $items;
	}

	protected function getListQuery()
	{
		$user   = JFactory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select required fields from the categories.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from($db->quoteName('#__dpcalendar_events') . ' AS a');
		$query->where('a.access IN (' . $groups . ')');

		// Don't show original events
		if ($this->getState('filter.expand')) {
			$query->where('a.original_id > -1');
		} else {
			$query->where('(a.original_id in (-1, 0) or (a.original_id > 0 and a.modified > ' . $this->getDbo()->quote($this->getDbo()->getNullDate()) . '))');
		}

		// Join over the categories.
		$query->join('LEFT', '#__categories AS c ON c.id = a.catid');

		if ($user->id > 0 && !DPCalendarHelper::isFree()) {
			// Join to tickets to add a field if the logged in user has a ticket
			// for the event
			$query->select('t.id as booking');
			$query->join('LEFT', '#__dpcalendar_tickets AS t ON t.event_id = a.id and t.user_id = ' . (int)$user->id);
		}

		// Join locations
		$query->select("GROUP_CONCAT(v.id SEPARATOR ', ') location_ids");
		$query->join('LEFT', '#__dpcalendar_events_location AS rel ON a.id = rel.event_id');
		$query->join('LEFT', '#__dpcalendar_locations AS v ON rel.location_id = v.id');
		$query->group('a.id');

		// Filter by category.
		if ($categoryIds = $this->getState('category.id', 0)) {
			if (!is_array($categoryIds)) {
				$categoryIds = array($categoryIds);
			}
			$cats = array();
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
		$state = $this->getState('filter.state');
		if (is_numeric($state)) {
			$query->where('a.state = ' . (int)$state);
		} else if (is_array($state)) {
			ArrayHelper::toInteger($state);
			$query->where('a.state in (' . implode(',', $state) . ')');
		}
		// Do not show trashed links on the front-end
		$query->where('a.state != -2');

		// Filter by start and end dates.
		$nullDate = $db->Quote($db->getNullDate());
		$date     = JFactory::getDate();
		$nowDate  = $db->Quote($date->toSql());

		if ($this->getState('filter.publish_date')) {
			$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
			$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
		}

		$startDate     = $db->Quote(DPCalendarHelper::getDate($this->getState('list.start-date'))->toSql());
		$dateCondition = 'a.start_date  >= ' . $startDate;
		if ($this->getState('list.end-date', null) !== null) {
			$endDate       = $db->Quote(DPCalendarHelper::getDate($this->getState('list.end-date'))->toSql());
			$dateCondition = '(a.end_date between ' . $startDate . ' and ' . $endDate . ' or a.start_date between ' . $startDate . ' and ' . $endDate .
				' or (a.start_date < ' . $startDate . ' and a.end_date > ' . $endDate . '))';
		}

		if ($this->getState('filter.ongoing', 0) == 1) {
			$now           = DPCalendarHelper::getDate();
			$dateCondition .= ' or ' . $db->quote($now->toSql()) . ' between a.start_date and a.end_date';
			$dateCondition .= ' or (a.start_date=' . $db->quote($now->format('Y-m-d')) . ' and all_day=1)';
			$dateCondition .= ' or (a.end_date=' . $db->quote($now->format('Y-m-d')) . ' and all_day=1)';
		}
		$query->where('(' . $dateCondition . ')');

		// Filter by language
		if ($this->getState('filter.language')) {
			$query->where('a.language in (' . $db->Quote(JFactory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')');
		}

		// Filter for featured events
		if ($this->getState('filter.featured')) {
			$query->where("a.featured = 1");
		}

		// Filter by title
		$searchString = $this->getState('filter.search');
		if (!empty($searchString)) {
			if (stripos($searchString, 'id:') === 0) {
				$ids = ArrayHelper::toInteger(explode(',', substr($searchString, 3)));
				$query->where('a.id in (' . implode(',', $ids) . ')');
			} else {
				// Immitating simple boolean search
				$searchColumns = array(
					'v.title',
					'CONCAT_WS(v.`country`,",",v.`province`,",",v.`city`,",",v.`zip`,",",v.`street`)',
					'a.title',
					'a.alias',
					'a.description',
					'a.metakey',
					'a.metadesc'
				);

				// Search in custom fields
				// Join over Fields.
				$query->join('LEFT', '#__fields_values AS jf ON jf.item_id = ' . $query->castAsChar('a.id'))
					->join('LEFT', '#__fields AS f ON f.id = jf.field_id')
					->where('(f.context IS NULL OR f.context = ' . $db->q('com_dpcalendar.event') . ')')
					->where('(f.state IS NULL OR f.state = 1)')
					->where('(f.access IS NULL OR f.access IN (' . $groups . '))');
				$searchColumns[] = 'jf.value';

				// Creating the search terms
				$searchTerms = explode(' ', \Joomla\String\StringHelper::strtolower($searchString));
				natsort($searchTerms);

				// Filtering the terms based on + - or none operators
				$must    = array();
				$mustNot = array();
				$can     = array();
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
		$locationsFilter = $this->getState('filter.locations', array());

		// Search for a location
		$location = $this->getState('filter.location');
		if ($location) {
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
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
			$query->join('LEFT',
				$db->quoteName('#__contentitem_tag_map', 'tagmap') . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' .
				$db->quoteName('a.id') . ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_dpcalendar.event'));

			ArrayHelper::toInteger($tagIds);
			$query->where($db->quoteName('tagmap.tag_id') . ' in (' . implode(',', $tagIds) . ')');
		}

		if ($this->getState('filter.my')) {
			$cond = 'a.created_by = ' . (int)$user->id;

			if ($user->id > 0 && !DPCalendarHelper::isFree()) {
				$cond .= ' or t.id is not null';
			}
			$query->where('(' . $cond . ')');
		}
		if ($this->getState('filter.children', 0) > 0) {
			$query->where('a.original_id = ' . (int)$this->getState('filter.children', 0));
		}
		$search = $this->getState('filter.search_start');
		if (!empty($search)) {
			$search = $db->Quote($db->escape($search, true));
			$query->where('a.start_date >= ' . $search);
		}
		$search = $this->getState('filter.search_end');
		if (!empty($search)) {
			$search = $db->Quote($db->escape($search, true));
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
		$db = JFactory::getDbo();

		$searchQuery = '';
		foreach ($terms as $termsKey => $search) {
			$searchQuery .= '(';
			$operator    = ' LIKE ';
			$condition   = ' OR ';
			if (strpos($search, '-') === 0) {
				$search    = substr($search, 1);
				$operator  = ' NOT LIKE ';
				$condition = ' AND ';
			} else if (strpos($search, '+') === 0) {
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

	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->_db->setQuery($query, $limitstart, $limit);
		$result = $this->_db->loadObjectList();

		return $result;
	}

	public function setStateFromParams(Registry $params)
	{
		// Filter for my
		$this->setState('filter.my', $params->get('show_my_only_calendar', $params->get('show_my_only_list')));

		// Filter for locations
		$this->setState('filter.locations', $params->get('calendar_filter_locations', $params->get('list_filter_locations')));

		// Filter for tags
		$this->setState('filter.tags', $params->get('calendar_filter_tags', $params->get('list_filter_tags')));
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app    = JFactory::getApplication();
		$params = $app->getParams();

		// List state information
		if ($app->input->getInt('limit', null) === null) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
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
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$id = $app->input->getVar('ids', null);
		if (!is_array($id)) {
			$id = explode(',', $id);
		}
		if (empty($id)) {
			$id = $params->get('ids');
		}
		$this->setState('category.id', $id);
		$this->setState('category.recursive', $app->input->getVar('layout') == 'module');

		$user = JFactory::getUser();
		if ((!$user->authorise('core.edit.state', 'com_dpcalendar')) && (!$user->authorise('core.edit', 'com_dpcalendar'))) {
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.state', 1);

			// Filter by start and end dates.
			$this->setState('filter.publish_date', true);
		}

		$this->setState('filter.language', $app->getLanguageFilter());
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
