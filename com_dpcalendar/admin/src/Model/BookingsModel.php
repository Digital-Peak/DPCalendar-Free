<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\Utilities\ArrayHelper;

class BookingsModel extends ListModel
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'id',
				'a.id',
				'a.uid',
				'name',
				'a.name',
				'state',
				'a.state',
				'book_date',
				'a.book_date',
				'user_name',
				'created_by',
				'a.created_by',
				'event_id',
				'a.event_id',
				'a.price'
			];
		}

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Initialize variables.
		$app = Factory::getApplication();

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);

		$this->setState('filter.my', 0);

		$params = $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');
		$this->setState('params', $params);

		parent::populateState('a.book_date', 'desc');

		$format          = $params->get('event_form_date_format', 'd.m.Y');
		$listRequestData = $this->getUserStateFromRequest($this->context . 'list', 'list', null, 'array', false);
		if (empty($listRequestData)) {
			$listRequestData = [];
		}

		// Joomla resets the start and end date
		$search = $listRequestData['date_start'] ?? '';
		try {
			DPCalendarHelper::getDateFromString($search, null, true, $format);
		} catch (\Exception $exception) {
			if ($search) {
				$app->enqueueMessage($exception->getMessage(), 'warning');
			}
			$search                        = '';
			$listRequestData['date_start'] = '';

			if ($app instanceof CMSWebApplicationInterface) {
				$app->setUserState($this->context . '.list', $listRequestData);
			}
		}
		$this->setState('list.date_start', $search);

		$search = $listRequestData['date_end'] ?? '';
		if ($search) {
			try {
				DPCalendarHelper::getDateFromString($search, null, true, $format);
			} catch (\Exception $e) {
				$app->enqueueMessage($e->getMessage(), 'warning');
				$search = '';

				$listRequestData['date_end'] = '';

				if ($app instanceof CMSWebApplicationInterface) {
					$app->setUserState($this->context . '.list', $listRequestData);
				}
			}
		}
		$this->setState('list.date_end', $search);
	}

	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$items = parent::_getList($query, $limitstart, $limit);

		if (!$items) {
			return $items;
		}

		/** @var \stdClass $item */
		foreach ($items as $item) {
			$ticketsModel = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Tickets', 'Administrator', ['ignore_request' => true]);
			$ticketsModel->setState('filter.booking_id', $item->id);
			$ticketsModel->setState('list.limit', 10000);
			$item->tickets = $ticketsModel->getItems();

			// @deprecated
			$item->processor = $item->payment_provider;

			if (!$item->country) {
				continue;
			}

			$country = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator', ['ignore_request' => true])->getItem($item->country);
			if ($country) {
				Factory::getApplication()->getLanguage()->load(
					'com_dpcalendar.countries',
					JPATH_ADMINISTRATOR . '/components/com_dpcalendar'
				);
				$item->country_code       = $country->short_code;
				$item->country_code_value = Text::_('COM_DPCALENDAR_COUNTRY_' . $country->short_code);
			}

			if ($item->price == '0.00') {
				$item->price = 0;
			}
		}

		return $items;
	}

	protected function getListQuery()
	{
		$user = $this->getCurrentUser();

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select($this->getState('list.select', 'a.*'));
		$query->from('#__dpcalendar_bookings AS a');

		// Join over the tickets
		$query->select('count(t.id) as amount_tickets');
		$query->join('LEFT', '#__dpcalendar_tickets AS t ON t.booking_id = a.id');

		// Join over the users for the author.
		$query->select('ua.name AS user_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = a.user_id');

		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos((string)$search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr((string)$search, 3));
			} elseif (stripos((string)$search, 'author:') === 0) {
				$search = $db->quote('%' . $db->escape(substr((string)$search, 7), true) . '%');
				$query->where('(a.name LIKE ' . $search . ' OR ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search . ')');
			} else {
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(a.name LIKE ' . $search . ' OR a.email LIKE ' . $search . ' OR a.uid LIKE ' . $search . ')');
			}
		}

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int)$published);
		} elseif (\is_array($published)) {
			$query->where('a.state IN (' . implode(',', ArrayHelper::toInteger($published)) . ')');
		} elseif ($published === '') {
			$query->where('a.state IN (0, 1, 2, 3, 4, 5, 6, 7, 8)');
		}

		// Filter by author
		$authorId = $this->getState('filter.created_by');
		if (is_numeric($authorId)) {
			$type = $this->getState('filter.created_by.include', true) ? '= ' : '<>';
			$query->where('a.user_id ' . $type . (int)$authorId);
		}

		$eventId = $this->getState('filter.event_id');
		if ($eventId && is_numeric($eventId)) {
			$eventId = [$eventId];
		}
		if (\is_array($eventId)) {
			$eventId = ArrayHelper::toInteger($eventId);

			// Also search in original events and instances
			$this->getDatabase()->setQuery(
				'select id,original_id from #__dpcalendar_events where (id in (' . implode(',', $eventId) . ') and original_id > 0) or original_id in (' . implode(',', $eventId) . ')'
			);
			foreach ($this->getDatabase()->loadObjectList() as $e) {
				if ($e->original_id > 0 && \in_array($e->id, $eventId)) {
					$eventId[] = $e->original_id;
				}

				if ($e->id > 0 && \in_array($e->original_id, $eventId)) {
					$eventId[] = $e->id;
				}
			}

			$query->where('t.event_id in (' . implode(',', array_unique($eventId)) . ')');
		}

		// Access rights
		if ($user->guest !== 0) {
			// Don't allow to list bookings as guest
			$query->where('1 > 1');
		}

		if ($this->getState('filter.my', 0) == 1) {
			$query->where('a.user_id = ' . (int)$user->id);
		}

		if ($payment_provider = $this->getState('filter.payment_provider')) {
			$query->where('a.payment_provider like ' . $this->getDatabase()->quote($payment_provider . '%'));
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

		// On front end if we are not an admin only bookings are visible where we are the author of the event
		if ($this->getState('filter.my', 0) != 1
			&& Factory::getApplication() instanceof SiteApplication
			&& !$user->authorise('dpcalendar.admin.book', 'com_dpcalendar' . ($catId ? '.category.' . $catId : ''))
			&& $this->getState('ignore.access') !== true
		) {
			// Join over the events
			$query->join('LEFT', '#__dpcalendar_events AS e ON e.id = t.event_id');
			$query->where('e.created_by = ' . (int)$user->id);
		}

		$search = $this->getState('list.date_start');
		if (!empty($search)) {
			$search = DPCalendarHelper::getDateFromString(
				$search,
				null,
				true,
				DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y')
			);
			$search->setTime(0, 0);
			$search = $db->quote($db->escape($search->toSql(), true));
			$query->where('a.book_date >= ' . $search);
		}

		$search = $this->getState('list.date_end');
		if (!empty($search)) {
			$search = DPCalendarHelper::getDateFromString(
				$search,
				null,
				true,
				DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y')
			);
			$search = $db->quote($db->escape($search->toSql(), true));
			$query->where('a.book_date <= ' . $search);
		}

		// Add the list ordering clause.
		$query->order($db->escape($this->getState('list.ordering', 'a.book_date')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		$query->group('a.id');

		return $query;
	}

	protected function _getListCount($query): int
	{
		if ($query instanceof QueryInterface) {
			$query = clone $query;
			$query->clear('select')
				->clear('order')
				->clear('limit')
				->clear('offset')
				->clear('group')
				->select('COUNT(distinct a.id)');
		}

		$this->getDatabase()->setQuery($query);

		return (int)$this->getDatabase()->loadResult();
	}

	public function updateFirstNameFromField(int $fieldId): int
	{
		$values = $this->getDatabase()
			->setQuery("select * from #__fields_values where value > '' and field_id = " . $fieldId)->loadObjectList();
		foreach ($values as $value) {
			$this->getDatabase()->setQuery(
				"update #__dpcalendar_bookings set name = trim(concat(COALESCE(first_name, ''), ' ', name)), first_name = " . $this->getDatabase()->quote($value->value)
				. ' where id = ' . $value->item_id
			)->execute();
			$this->getDatabase()->setQuery('delete from #__fields_values where field_id = ' . $value->field_id . ' and item_id = ' . $value->item_id)->execute();
		}

		return \count($values);
	}
}
