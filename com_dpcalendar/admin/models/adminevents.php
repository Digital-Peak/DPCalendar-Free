<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

JLoader::import('joomla.application.component.modellist');

class DPCalendarModelAdminEvents extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'title',
				'a.title',
				'start_date',
				'a.start_date',
				'end_date',
				'a.end_date',
				'alias',
				'a.alias',
				'checked_out',
				'a.checked_out',
				'checked_out_time',
				'a.checked_out_time',
				'category_id',
				'a.category_id',
				'category_title',
				'state',
				'a.state',
				'access',
				'a.access',
				'access_level',
				'created',
				'a.created',
				'created_by',
				'a.created_by',
				'featured',
				'a.featured',
				'language',
				'a.language',
				'hits',
				'a.hits',
				'color',
				'a.color',
				'publish_up',
				'a.publish_up',
				'publish_down',
				'a.publish_down',
				'url',
				'a.url',
				'event_type',
				'level',
				'tag'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$accessId = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		$this->setState('filter.access', $accessId);

		$authorId = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by');
		$this->setState('filter.author_id', $authorId);

		$eventType = $this->getUserStateFromRequest($this->context . '.filter.event_type', 'filter_event_type', '', 'string');
		$this->setState('filter.event_type', $eventType);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '');
		$this->setState('filter.state', $published);

		$categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
		$this->setState('filter.category_id', $categoryId);

		$level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');
		$this->setState('filter.level', $level);

		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');
		$this->setState('filter.tag', $tag);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_dpcalendar');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.start_date', 'asc');

		// Joomla resets the start and end date
		$search = $this->getUserStateFromRequest($this->context . '.filter.search_start', 'filter_search_start',
			DPCalendarHelper::getDate()->format(DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y')), 'none', false);
		$this->setState('filter.search_start', $search);

		$search = $this->getUserStateFromRequest($this->context . '.filter.search_end', 'filter_search_end', '', 'none', false);
		$this->setState('filter.search_end', $search);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.event_type');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . $this->getState('filter.language');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$user  = JFactory::getUser();

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from($db->quoteName('#__dpcalendar_events') . ' AS a');

		// Join over the language
		$query->select('l.title AS language_title');
		$query->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = a.language');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

		// Join over the categories.
		$query->select('c.title AS category_title');
		$query->join('LEFT', '#__categories AS c ON c.id = a.catid');

		// Join over the users for the author.
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');

		// Don't show original events
		$eventType = $this->getState('filter.event_type');
		if ($eventType == 0) {
			$query->where('a.original_id > -1');
		} else if ($eventType == 1) {
			$query->where('(a.original_id = -1 or a.original_id = 0)');
		}

		// Filter by access level.
		if ($access = $this->getState('filter.access')) {
			$query->where('a.access = ' . (int)$access);
		}

		// Implement View Level Access
		if (!$user->authorise('core.admin', 'com_dpcalendar', 'com_dpcalendar')) {
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')');

			$query->select(
				'CASE WHEN a.access_content IN (' . $groups . ") THEN a.title ELSE '" . JText::_('COM_DPCALENDAR_EVENT_BUSY') . "' END as title");
		} else {
			$query->select('a.title');
		}

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int)$published);
		} elseif ($published === '') {
			$query->where('a.state IN (0, 1)');
		}

		// Filter only published categories
		$query->where('c.published IN (0, 1)');

		// Filter by a single or group of categories.
		$baselevel  = 1;
		$categoryId = $this->getState('filter.category_id');
		if (is_numeric($categoryId)) {
			$cat_tbl = JTable::getInstance('Category', 'JTable');
			$cat_tbl->load($categoryId);
			$rgt       = $cat_tbl->rgt;
			$lft       = $cat_tbl->lft;
			$baselevel = (int)$cat_tbl->level;
			$query->where('c.lft >= ' . (int)$lft);
			$query->where('c.rgt <= ' . (int)$rgt);
		} elseif (is_array($categoryId)) {
			ArrayHelper::toInteger($categoryId);
			$categoryId = implode(',', $categoryId);
			$query->where('a.catid IN (' . $categoryId . ')');
		}

		// Filter on the level.
		if ($level = $this->getState('filter.level')) {
			$query->where('c.level <= ' . ((int)$level + (int)$baselevel - 1));
		}

		// Filter by author
		$authorId = $this->getState('filter.author_id');
		if (is_numeric($authorId)) {
			$type = $this->getState('filter.author_id.include', true) ? '= ' : '<>';
			$query->where('a.created_by ' . $type . (int)$authorId);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr($search, 3));
			} elseif (stripos($search, 'author:') === 0) {
				$search = $db->quote('%' . $db->escape(substr($search, 7), true) . '%');
				$query->where('(ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search . ')');
			} else {
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ' OR a.description LIKE ' . $search . ')');
			}
		}
		$search = $this->getState('filter.search_start');
		if (!empty($search)) {
			$search = DPCalendarHelper::getDateFromString(
				$search,
				null,
				true,
				DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y')
			);
			$search->setTime(0, 0);
			$search = $db->quote($db->escape($search->toSql(), true));
			$query->where('a.start_date >= ' . $search);
		}
		$search = $this->getState('filter.search_end');
		if (!empty($search)) {
			$search = DPCalendarHelper::getDateFromString(
				$search,
				null,
				true,
				DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y')
			);
			$search = $db->quote($db->escape($search->toSql(), true));
			$query->where('a.end_date <= ' . $search);
		}

		if ($this->getState('filter.children', 0) > 0) {
			$query->where('a.original_id = ' . (int)$this->getState('filter.children', 0));
		}

		// Filter on the language.
		if ($language = $this->getState('filter.language')) {
			$query->where('a.language = ' . $db->quote($language));
		}

		// Filter by a single tag.
		$tagId = $this->getState('filter.tag');

		if (is_numeric($tagId)) {
			$query->where($db->quoteName('tagmap.tag_id') . ' = ' . (int)$tagId)
				->join('LEFT',
					$db->quoteName('#__contentitem_tag_map', 'tagmap') . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' .
					$db->quoteName('a.id') . ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_dpcalendar.event')
				);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'start_date');
		$orderDirn = $this->state->get('list.direction', 'asc');
		if ($orderCol == 'category_title') {
			$orderCol = 'c.title ' . $orderDirn . ', a.start_date';
		}
		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}

	public function getAuthors()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Construct the query
		$query->select('u.id AS value, u.name AS text');
		$query->from('#__users AS u');
		$query->join('INNER', '#__dpcalendar_events AS c ON c.created_by = u.id');
		$query->group('u.id, u.name');
		$query->order('u.name');

		// Setup the query
		$db->setQuery($query->__toString());

		// Return the result
		return $db->loadObjectList();
	}

	protected function loadFormData()
	{
		$data = parent::loadFormData();

		if ($data instanceof stdClass && $data->filter) {
			if (!empty($data->filter['published'])) {
				$data->filter['state'] = $data->filter['published'];
			}
			if (!empty($data->filter['category_id'])) {
				$data->filter['cat_id'] = $data->filter['category_id'];
			}
		}

		return $data;
	}
}
