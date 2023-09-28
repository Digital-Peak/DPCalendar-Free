<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;

JLoader::import('components.com_dpcalendar.tables.booking', JPATH_ADMINISTRATOR);

class DPCalendarModelTickets extends ListModel
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'id',
				'a.id',
				'uid',
				'a.uid',
				'name',
				'a.name',
				'price',
				'a.price',
				'state',
				'a.state',
				'booking_name',
				'event_title',
				'event_id'
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);
		$bookingId = $this->getUserStateFromRequest($this->context . '.filter.booking_id', 'filter_booking_id');
		$this->setState('filter.booking_id', !$bookingId ? Factory::getApplication()->input->get('b_id') : $bookingId);
		$eventId = $this->getUserStateFromRequest($this->context . '.filter.event_id', 'filter_event_id');
		$this->setState('filter.event_id', !$eventId ? Factory::getApplication()->input->get('e_id') : $eventId);

		$app = Factory::getApplication();
		$this->setState('params', method_exists($app, 'getParams') ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));

		parent::populateState($ordering, $direction);
	}

	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$items = parent::_getList($query, $limitstart, $limit);
		if (!$items) {
			return $items;
		}

		static $countryCache = null;

		foreach ($items as $item) {
			if ($item->country) {
				if ($countryCache === null) {
					$countryCache = [];
					Factory::getApplication()->getLanguage()->load(
						'com_dpcalendar.countries',
						JPATH_ADMINISTRATOR . '/components/com_dpcalendar'
					);
				}

				if (!array_key_exists($item->country, $countryCache)) {
					$countryCache[$item->country] = BaseDatabaseModel::getInstance('Country', 'DPCalendarModel')->getItem($item->country);
				}

				if ($countryCache[$item->country]) {
					$item->country_code       = $countryCache[$item->country]->short_code;
					$item->country_code_value = Text::_('COM_DPCALENDAR_COUNTRY_' . $countryCache[$item->country]->short_code);
				}
			}

			if ($item->price == '0.00') {
				$item->price = 0;
			}

			if ($item->event_payment_provider) {
				$item->event_payment_provider = explode(',', $item->event_payment_provider);
			} else {
				$item->event_payment_provider = [];
			}
		}

		return $items;
	}

	protected function getListQuery()
	{
		$user = Factory::getUser();

		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select($this->getState('list.select', 'distinct a.*'));
		$query->from($db->quoteName('#__dpcalendar_tickets') . ' AS a');

		// Join over the bookings
		$query->select('b.name as booking_name, b.price as booking_price, b.token as booking_token');
		$query->join('LEFT', $db->quoteName('#__dpcalendar_bookings') . ' AS b ON b.id = a.booking_id');

		// Join over the events
		$query->select('e.catid AS event_calid, e.title as event_title, e.start_date, e.end_date, e.all_day, e.show_end_time, e.price as event_prices, e.booking_options as event_options, e.payment_provider as event_payment_provider, e.terms as event_terms, e.created_by as event_author, e.original_id as event_original_id, e.rrule as event_rrule, e.booking_cancel_closing_date as event_booking_cancel_closing_date');
		$query->join('LEFT', $db->quoteName('#__dpcalendar_events') . ' AS e ON e.id = a.event_id');
		// Join over the hosts
		$query->select('GROUP_CONCAT(h.user_id) as event_host_ids');
		$query->join('LEFT', $db->quoteName('#__dpcalendar_events_hosts') . ' AS h ON h.event_id = a.event_id');
		$query->group(['a.id']);

		// Join over the users for the author.
		$query->select('ua.name AS user_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = b.user_id');

		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr($search, 3));
			} elseif (stripos($search, 'author:') === 0) {
				$search = $db->quote('%' . $db->escape(substr($search, 7), true) . '%');
				$query->where(
					'(a.name LIKE ' . $search . ' OR b.name LIKE ' . $search . ' OR ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search .
					')'
				);
			} else {
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where(
					'(a.name LIKE ' . $search . ' OR a.uid LIKE ' . $search . ' OR b.name LIKE ' . $search . ' OR b.email LIKE ' . $search .
					' OR e.title LIKE ' . $search . ')'
				);
			}
		}

		$bookingId = $this->getState('filter.booking_id');
		if ($bookingId) {
			$query->where('a.booking_id = ' . (int)$bookingId);
		}

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int)$published);
		} elseif (is_array($published)) {
			$query->where('(a.state IN (' . implode(',', ArrayHelper::toInteger($published)) . '))');
		} elseif ($published === '') {
			$query->where('a.state IN (0, 1, 2, 3, 4, 5, 6, 7, 8, 9)');
		}

		// Filter by author
		$authorId = $this->getState('filter.ticket_holder');
		if (is_numeric($authorId)) {
			$type = $this->getState('filter.ticket_holder.include', true) ? '= ' : '<>';
			$query->where('a.user_id ' . $type . (int)$authorId);
		} elseif ($this->getState('filter.public')) {
			$query->where('public = 1');
		} elseif ($authorId !== false && !$user->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			if ($user->guest) {
				$query->where('public = 1');
			}

			if (Factory::getApplication()->isClient('site')) {
				$query->where('(e.created_by = ' . (int)$user->id . ' or a.user_id = ' . (int)$user->id . ' or h.user_id = ' . (int)$user->id . ')');
			}
		}

		$eventId = $this->getState('filter.event_id');
		if (is_numeric($eventId)) {
			$eventId = [$eventId];
		}
		if (is_array($eventId)) {
			ArrayHelper::toInteger($eventId);

			// Also search in original events
			$this->getDbo()->setQuery(
				'select original_id from #__dpcalendar_events where id in (' . implode(',', $eventId) . ') and original_id > 0'
			);
			foreach ($this->getDbo()->loadObjectList() as $orig) {
				$eventId[] = $orig->original_id;
			}

			$query->where('e.id in (' . implode(',', $eventId) . ')');
		}

		if ($this->getState('filter.my', 0) == 1) {
			$query->where('a.user_id = ' . (int)$user->id);
		}

		if ($this->getState('filter.future')) {
			$query->where('e.start_date >= ' . $db->q(DPCalendarHelper::getDate()->toSql()));
		}

		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'e.start_date')) . ' ' . $db->escape($this->getState('list.direction', 'asc')));

		// echo nl2br(str_replace('#__', 'j_', $query)); die();

		return $query;
	}

	public function getEvent($eventId = null, $force = false)
	{
		if ($eventId == null) {
			$eventId = Factory::getApplication()->input->get('e_id');
		}
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel');

		return $model->getItem($eventId);
	}

	public function getActiveFilters()
	{
		$activeFilters = parent::getActiveFilters();

		// Reset default filters from view
		if (!empty($activeFilters['state']) && $activeFilters['state'] === [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]) {
			unset($activeFilters['state']);
		}

		return $activeFilters;
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.booking_id');
		$id .= ':' . implode(',', (array)$this->getState('filter.event_id'));

		return parent::getStoreId($id);
	}
}
