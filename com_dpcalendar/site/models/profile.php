<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modellist');

class DPCalendarModelProfile extends JModelList
{

	private $items = null;

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
		if ($this->items === null) {
			$calendars = parent::getItems();
			if (!is_array($calendars)) {
				return $calendars;
			}

			$myUri = 'principals/' . JFactory::getUser()->username;

			$myCalendars       = array();
			$externalCalendars = array();
			foreach ($calendars as $calendar) {
				if (empty($calendar->calendarcolor)) {
					$calendar->calendarcolor = '3366CC';
				}
				if ($myUri == $calendar->principaluri) {
					$calendar->member_principal_access = null;
				}

				$key   = str_replace('principals/', 'calendars/', $calendar->principaluri) . '/' . $calendar->uri;
				$read  = strpos($calendar->member_principal_access, '/calendar-proxy-read') !== false;
				$write = strpos($calendar->member_principal_access, '/calendar-proxy-write') !== false;
				if (!key_exists($key, $myCalendars) || !key_exists($key, $externalCalendars) || $read) {
					if (empty($calendar->member_principal_access)) {
						$calendar->canEdit = true;
						$myCalendars[$key] = $calendar;
					} else {
						$calendar->canEdit       = $write;
						$externalCalendars[$key] = $calendar;
					}
				}
				if (!empty($calendar->member_principal_access) && $write) {
					$externalCalendars[$key]->canEdit = $write;
				}
			}
			$this->items = array_merge($myCalendars, $externalCalendars);
		}

		return $this->items;
	}

	protected function getListQuery()
	{
		$user = JFactory::getUser();

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('c.*,mainp.uri as member_principal_uri, mainp.displayname as member_principal_name, mp.linkuri member_principal_access');
		$query->from($db->quoteName('#__dpcalendar_caldav_calendarinstances') . ' c');
		$query->join('left outer', "#__dpcalendar_caldav_principals mainp on mainp.uri = c.principaluri");
		$query->join('left outer',
			"(select memberp.external_id member_external_id, linkp.uri linkuri from #__dpcalendar_caldav_principals memberp
				inner join #__dpcalendar_caldav_groupmembers m on memberp.id = m.member_id
				inner join #__dpcalendar_caldav_principals linkp on m.principal_id = linkp.id) as mp
				on mp.linkuri LIKE CONCAT(c.principaluri, '/%')");

		$query->where('(mainp.external_id = ' . (int)$user->id . ' or mp.member_external_id = ' . (int)$user->id . ')');

		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('c.id = ' . (int)substr($search, 3));
			} else {
				$search = $db->Quote('%' . $db->escape(\Joomla\String\StringHelper::strtolower($search), true) . '%');
				$query->where('(LOWER(c.displayname) LIKE ' . $search . ' OR LOWER(c.description) LIKE ' . $search . ')');
			}
		}

		$query->order($db->escape($this->getState('list.ordering', 'c.displayname')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		// Echo str_replace('#__', 'a_', $query); die();
		return $query;
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app    = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_dpcalendar');

		if (JFactory::getApplication()->input->getInt('limit', null) === null) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
			$this->setState('list.limit', $limit);
		} else {
			$this->setState('list.limit', JFactory::getApplication()->input->getInt('limit', 0));
		}

		$limitstart = JFactory::getApplication()->input->getVar('limitstart', 0, '', 'int');
		$this->setState('list.start', $limitstart);

		$orderCol = JFactory::getApplication()->input->getCmd('filter_order', 'c.displayname');
		if (!in_array($orderCol, $this->filter_fields)) {
			$orderCol = 'c.displayname';
		}
		$this->setState('list.ordering', $orderCol);

		$listOrder = JFactory::getApplication()->input->getCmd('filter_order_Dir', 'ASC');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$this->setState('filter.search', JFactory::getApplication()->input->getVar('filter-search'));

		$this->setState('params', $params);
	}

	public function getReadMembers()
	{
		$user = JFactory::getUser();

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('p.id AS value, p.displayname AS text');
		$query->from($db->quoteName('#__dpcalendar_caldav_principals') . ' AS p');
		$query->join('right', '#__dpcalendar_caldav_groupmembers m on p.id = m.member_id');
		$query->where(
			'm.principal_id = (select id from #__dpcalendar_caldav_principals where uri = ' . $db->quote(
				'principals/' . $user->username . '/calendar-proxy-read') . ')');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function getWriteMembers()
	{
		$user = JFactory::getUser();

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('p.id AS value, p.displayname AS text');
		$query->from($db->quoteName('#__dpcalendar_caldav_principals') . ' AS p');
		$query->join('right', '#__dpcalendar_caldav_groupmembers m on p.id = m.member_id');
		$query->where(
			'm.principal_id = (select id from #__dpcalendar_caldav_principals where uri = ' . $db->quote(
				'principals/' . $user->username . '/calendar-proxy-write') . ')');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function getUsers()
	{
		$user = JFactory::getUser();

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('p.id AS value, p.displayname AS text');
		$query->from($db->quoteName('#__dpcalendar_caldav_principals') . ' AS p');
		$query->where('p.external_id != ' . (int)$user->id);
		$query->where('p.uri not like ' . $db->quote('%calendar-proxy-read%'));
		$query->where('p.uri not like ' . $db->quote('%calendar-proxy-write%'));

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function getEvents()
	{
		$calendars = $this->getItems();
		if (empty($calendars)) {
			return array();
		}
		$ids = array();
		foreach ($calendars as $calendar) {
			$ids['cd-' . $calendar->id] = 'cd-' . $calendar->id;
		}

		$model = JModelLegacy::getInstance('Calendar', 'DPCalendarModel');
		$model->getState();
		$model->setState('filter.parentIds', array('root'));
		$ids = array();
		foreach ($model->getItems() as $calendar) {
			$ids[$calendar->id] = $calendar->id;
		}

		$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
		$model->getState();
		$model->setState('category.id', $ids);
		$model->setState('category.recursive', true);
		$model->setState('list.limit', 5);
		$model->setState('list.start-date', DPCalendarHelper::getDate()->format('U'));
		$model->setState('list.ordering', 'start_date');
		$model->setState('filter.my', 1);

		return $model->getItems();
	}

	public function change($action, $id, $type)
	{
		$db = JFactory::getDbo();

		$type == 'read' ? 'read' : 'write';
		if ($action == 'add') {
			$query = 'insert into #__dpcalendar_caldav_groupmembers (member_id, principal_id)
					select ' . (int)$id . ' as member_id, id as principal_id from #__dpcalendar_caldav_principals
							where uri=' . $db->quote('principals/' . JFactory::getUser()->username . '/calendar-proxy-' . $type);
		} else {
			$query = 'delete from #__dpcalendar_caldav_groupmembers
					where member_id = ' . (int)$id . ' and principal_id = (select id from #__dpcalendar_caldav_principals
							where uri=' . $db->quote('principals/' . JFactory::getUser()->username . '/calendar-proxy-' . $type) . ')';
		}
		$db->setQuery($query);

		return $db->execute();
	}
}
