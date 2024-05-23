<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;

class TicketsModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
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

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);
		$bookingId = $this->getUserStateFromRequest($this->context . '.filter.booking_id', 'filter_booking_id');
		$this->setState('filter.booking_id', $bookingId ?: Factory::getApplication()->getInput()->get('b_id'));
		$eventId = $this->getUserStateFromRequest($this->context . '.filter.event_id', 'filter_event_id');
		$this->setState('filter.event_id', $eventId ?: Factory::getApplication()->getInput()->get('e_id'));

		$app = Factory::getApplication();
		$this->setState('params', $app instanceof SiteApplication && $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));

		parent::populateState($ordering, $direction);
	}

	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$items = parent::_getList($query, $limitstart, $limit);
		if (!$items) {
			return $items;
		}

		static $countryCache = null;

		/** @var \stdClass $item */
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
					$countryCache[$item->country] = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator')->getItem($item->country);
				}

				if ($countryCache[$item->country]) {
					$item->country_code       = $countryCache[$item->country]->short_code;
					$item->country_code_value = Text::_('COM_DPCALENDAR_COUNTRY_' . $countryCache[$item->country]->short_code);
				}
			}

			if ($item->price == '0.00') {
				$item->price = 0;
			}

			$item->type_label = '';
			if (!empty($item->event_prices)) {
				$prices = json_decode((string)$item->event_prices);
				if (!empty($prices->label) && !empty($prices->label[$item->type])) {
					$item->type_label = $prices->label[$item->type];
				}
			}

			$item->event_payment_provider = $item->event_payment_provider ? explode(',', (string)$item->event_payment_provider) : [];
		}

		return $items;
	}

	protected function getListQuery()
	{
		$user = $this->getCurrentUser();

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select($this->getState('list.select', 'distinct a.*'));
		$query->from('#__dpcalendar_tickets AS a');

		// Join over the bookings
		$query->select('b.name as booking_name, b.price as booking_price, b.token as booking_token');
		$query->join('LEFT', '#__dpcalendar_bookings AS b ON b.id = a.booking_id');

		// Join over the events
		$query->select('e.catid AS event_calid, e.title as event_title, e.start_date, e.end_date, e.all_day, e.show_end_time, e.price as event_prices, e.booking_options as event_options, e.payment_provider as event_payment_provider, e.terms as event_terms, e.created_by as event_author, e.original_id as event_original_id, e.rrule as event_rrule, e.booking_cancel_closing_date as event_booking_cancel_closing_date');
		$query->join('LEFT', '#__dpcalendar_events AS e ON e.id = a.event_id');

		// Join over the hosts
		$query->select('GROUP_CONCAT(h.user_id) as event_host_ids');
		$query->join('LEFT', '#__dpcalendar_events_hosts AS h ON h.event_id = a.event_id');
		$query->group(['a.id']);

		// Join over the users for the author.
		$query->select('ua.name AS user_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = b.user_id');

		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos((string)$search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr((string)$search, 3));
			} elseif (stripos((string)$search, 'author:') === 0) {
				$search = $db->quote('%' . $db->escape(substr((string)$search, 7), true) . '%');
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

		$catId = 0;
		// Get the category id for access check
		if ($this->getState('filter.event_id')) {
			$this->getDatabase()->setQuery(
				'select catid from #__dpcalendar_events where id = ' . (int)$this->getState('filter.event_id')
			);
			$catId = $this->getDatabase()->loadRow();

			if ($catId) {
				$catId = $catId[0];
			}
		}

		// Filter by author
		$authorId = $this->getState('filter.ticket_holder');
		if (is_numeric($authorId)) {
			$type = $this->getState('filter.ticket_holder.include', true) ? '= ' : '<>';
			$query->where('a.user_id ' . $type . (int)$authorId);
		} elseif ($this->getState('filter.public')) {
			$query->where('public = 1');
		} elseif ($authorId !== false && !$user->authorise('dpcalendar.admin.book', 'com_dpcalendar' . ($catId ? '.category.' . $catId : ''))) {
			if ($user->guest !== 0) {
				$query->where('public = 1');
			}

			if (Factory::getApplication()->isClient('site')) {
				$query->where('(e.created_by = ' . (int)$user->id . ' or a.user_id = ' . (int)$user->id . ' or h.user_id = ' . (int)$user->id . ')');
			}
		}

		$eventId = $this->getState('filter.event_id');
		if ($eventId && is_numeric($eventId)) {
			$eventId = [$eventId];
		}
		if (is_array($eventId)) {
			ArrayHelper::toInteger($eventId);

			// Also search in original events
			$this->getDatabase()->setQuery(
				'select original_id from #__dpcalendar_events where id in (' . implode(',', $eventId) . ') and original_id > 0'
			);
			foreach ($this->getDatabase()->loadObjectList() as $orig) {
				$eventId[] = $orig->original_id;
			}

			$query->where('e.id in (' . implode(',', $eventId) . ')');
		}

		if ($this->getState('filter.my', 0) == 1) {
			$query->where('a.user_id = ' . (int)$user->id);
		}

		if ($this->getState('filter.future')) {
			$query->where('e.start_date >= ' . $db->quote(DPCalendarHelper::getDate()->toSql()));
		}

		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'e.start_date')) . ' ' . $db->escape($this->getState('list.direction', 'asc')));

		// echo nl2br(str_replace('#__', 'j_', $query)); die();

		return $query;
	}

	/**
	 * @return ?\stdClass
	 */
	public function getEvent(?string $eventId = null)
	{
		if ($eventId == null) {
			$eventId = Factory::getApplication()->getInput()->get('e_id');
		}

		$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator');

		return $model->getItem($eventId) ?: null;
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
