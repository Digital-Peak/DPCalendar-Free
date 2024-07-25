<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;

class EventsModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
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
				'calendars',
				'a.calendars',
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
				'tag',
				'original_title'
			];
		}

		parent::__construct($config, $factory);
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

		$calendars = $this->getUserStateFromRequest($this->context . '.filter.calendars', 'filter_calendars');
		$this->setState('filter.calendars', $calendars);

		$level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');
		$this->setState('filter.level', $level);

		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');
		$this->setState('filter.tag', $tag);

		// Load the parameters.
		$app = Factory::getApplication();
		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));

		// List state information.
		parent::populateState('a.start_date', 'asc');

		$format          = DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y');
		$listRequestData = $this->getUserStateFromRequest($this->context . 'list', 'list', null, 'array');
		if (empty($listRequestData)) {
			$listRequestData = [];
		}

		// Joomla resets the start and end date
		$search = $listRequestData['start-date'] ?? DPCalendarHelper::getDate()->format($format, true);
		try {
			DPCalendarHelper::getDateFromString($search, null, true, $format);
		} catch (\Exception $exception) {
			if ($search) {
				$app->enqueueMessage($exception->getMessage(), 'warning');
			}
			$search                        = DPCalendarHelper::getDate()->format($format, true);
			$listRequestData['start-date'] = '';

			if ($app instanceof CMSWebApplicationInterface) {
				$app->setUserState($this->context . '.list', $listRequestData);
			}
		}
		$this->setState('list.start-date', $search);

		$search = $listRequestData['end-date'] ?? '';
		if ($search) {
			try {
				DPCalendarHelper::getDateFromString($search, null, true, $format);
			} catch (\Exception $e) {
				$app->enqueueMessage($e->getMessage(), 'warning');
				$search = '';

				$listRequestData['end-date'] = '';

				if ($app instanceof CMSWebApplicationInterface) {
					$app->setUserState($this->context . '.list', $listRequestData);
				}
			}
		}
		$this->setState('list.end-date', $search);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . implode(',', (array)$this->getState('filter.state', []));
		$id .= ':' . $this->getState('filter.event_type');
		$id .= ':' . implode(',', (array)$this->getState('filter.calendars', []));
		$id .= ':' . $this->getState('filter.language');

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		$items = parent::getItems();

		$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.ordering', 'ordering');
		$model->setState('list.direction', 'asc');

		foreach ($items as $item) {
			// Add the locations
			$item->locations = [];
			if (!empty($item->location_ids) && $item->locations === []) {
				$model->setState('filter.search', 'ids:' . $item->location_ids);
				$item->locations = $model->getItems();
			}

			$item->color = str_replace('#', '', (string)$item->color);

			// If the event has no color, use the one from the calendar
			$calendar = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($item->catid);
			if ($item->color === '' || $item->color === '0') {
				$item->color = $calendar instanceof CalendarInterface ? str_replace('#', '', $calendar->getColor()) : '3366CC';
			}

			// Check if it is a valid color
			if ((\strlen($item->color) !== 6 && \strlen($item->color) !== 3) || !ctype_xdigit($item->color)) {
				$item->color = '3366CC';
			}
		}

		return $items;
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);
		$user  = $this->getCurrentUser();

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from('#__dpcalendar_events AS a');

		// Join over the language
		$query->select('l.title AS language_title');
		$query->join('LEFT', '#__languages AS l ON l.lang_code = a.language');

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

		// Join over the original
		$query->select('o.title as original_title, o.rrule as original_rrule');
		$query->join('LEFT', '#__dpcalendar_events AS o ON o.id = a.original_id');

		// Join locations
		$query->select("GROUP_CONCAT(DISTINCT v.id SEPARATOR ', ') location_ids");
		$query->join('LEFT', '#__dpcalendar_events_location AS rel ON a.id = rel.event_id');
		$query->join('LEFT', '#__dpcalendar_locations AS v ON rel.location_id = v.id');
		$query->group('a.id');

		// Don't show original events
		$eventType = $this->getState('filter.event_type');
		if ($eventType == 0) {
			$query->where('a.original_id > -1');
		} elseif ($eventType == 1) {
			$query->where('(a.original_id = -1 or a.original_id = 0)');
		}

		// Filter by access level.
		if ($access = $this->getState('filter.access')) {
			$query->where('a.access = ' . (int)$access);
		}

		// Implement View Level Access
		if (!$user->authorise('core.admin', 'com_dpcalendar')) {
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')');

			$query->select(
				'CASE WHEN a.access_content IN (' . $groups . ") THEN a.title ELSE '" . Text::_('COM_DPCALENDAR_EVENT_BUSY') . "' END as title"
			);
		} else {
			$query->select('a.title');
		}

		// Filter by id
		$ids = $this->getState('filter.ids', []);
		if (is_array($ids) && $ids !== []) {
			$query->where('a.id IN (' . implode(',', ArrayHelper::toInteger($ids)) . ')');
		}

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$published = [$published];
		}

		if ($published) {
			$published = ArrayHelper::toInteger($published);
			$query->where('a.state in (' . implode(',', $published) . ')');
		} elseif ($published === '') {
			$query->where('a.state IN (0, 1, 3)');
		}

		// Filter only published categories
		$query->where('c.published IN (0, 1)');

		// Filter by a single or group of categories.
		$baselevel = 1;
		$calendars = array_filter((array)$this->getState('filter.calendars', []), static fn ($c): bool => !empty($c));
		if (count($calendars) === 1) {
			$cat_tbl = $this->bootComponent('categories')->getMVCFactory()->createTable('Category', 'Administrator');
			$cat_tbl->load(reset($calendars));
			$rgt       = $cat_tbl->rgt;
			$lft       = $cat_tbl->lft;
			$baselevel = (int)$cat_tbl->level;
			$query->where('c.lft >= ' . (int)$lft);
			$query->where('c.rgt <= ' . (int)$rgt);
		} elseif (count($calendars) > 1) {
			$calendars = ArrayHelper::toInteger($calendars);
			$calendars = implode(',', $calendars);
			$query->where('a.catid IN (' . $calendars . ')');
		}

		// Filter on the level.
		if ($level = $this->getState('filter.level')) {
			$query->where('c.level <= ' . ((int)$level + $baselevel - 1));
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
			if (stripos((string)$search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr((string)$search, 3));
			} elseif (stripos((string)$search, 'author:') === 0) {
				$search = $db->quote('%' . $db->escape(substr((string)$search, 7), true) . '%');
				$query->where('(ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search . ')');
			} else {
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ' OR a.description LIKE ' . $search . ')');
			}
		}

		$search = $this->getState('list.start-date');
		if (!empty($search)) {
			$search = $search instanceof Date ? $search : DPCalendarHelper::getDateFromString(
				$search,
				null,
				true,
				DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y')
			);
			$search->setTime(0, 0);
			$search = $db->quote($db->escape($search->toSql(), true));
			$query->where('a.start_date >= ' . $search);
		}
		$search = $this->getState('list.end-date');
		if (!empty($search)) {
			$search = $search instanceof Date ? $search : DPCalendarHelper::getDateFromString(
				$search,
				null,
				true,
				DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y')
			);
			$search = $db->quote($db->escape($search->toSql(), true));
			$query->where('a.end_date <= ' . $search);
		}

		if ($this->getState('filter.children', 0) > 0) {
			$query->where('a.original_id = ' . (int)$this->getState('filter.children', 0));
		}

		if ($modified = $this->getState('filter.modified')) {
			$query->where('(a.modified != ' . $db->quote($modified) . " and a.modified is not null)");
		}

		if ($reference = $this->getState('filter.xreference')) {
			$query->where('a.xreference like ' . $db->quote($reference));
		}

		// Filter on the language.
		if ($language = $this->getState('filter.language')) {
			$query->where('a.language = ' . $db->quote($language));
		}

		// Filter by a single tag.
		$tagId = $this->getState('filter.tag');

		if (is_numeric($tagId)) {
			$query->where('tagmap.tag_id = ' . (int)$tagId)
				->join(
					'LEFT',
					'#__contentitem_tag_map tagmap ON tagmap.content_item_id = a.id AND tagmap.type_alias = ' . $db->quote('com_dpcalendar.event')
				);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'start_date');
		$orderDirn = $this->state->get('list.direction', 'asc');
		if ($orderCol == 'category_title') {
			$orderCol = 'c.title ' . $orderDirn . ', a.start_date';
		}
		if ($orderCol == 'original_title') {
			$orderCol = 'o.title ' . $orderDirn . ', a.start_date';
		}
		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}

	public function getAuthors(): array
	{
		// Create a new query object.
		$db    = $this->getDatabase();
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

		if ($data instanceof \stdClass && !empty($data->filter)) {
			// Ensure filter is an array
			$data->filter = (array)$data->filter;
			if (!empty($data->filter['published'])) {
				$data->filter['state'] = $data->filter['published'];
			}
			if (!empty($data->filter['calendars'])) {
				$data->filter['cat_id'] = is_array($data->filter['calendars']) ? reset($data->filter['calendars']) : $data->filter['calendars'];
			}
		}

		return $data instanceof BasicTable ? $data->getData() : $data;
	}
}
