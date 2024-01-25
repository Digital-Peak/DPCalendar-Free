<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Router\Rules\DPCalendarRules;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

class DPCalendarRouter extends RouterView
{
	public $app;
	public $views;
	public $menu;
	public function __construct($app = null, $menu = null)
	{
		$params = ComponentHelper::getParams('com_dpcalendar');

		$calendar = new RouterViewConfiguration('calendar');
		$calendar->setKey('ids');
		$this->registerView($calendar);

		$list = new RouterViewConfiguration('list');
		$list->setKey('ids');
		$list->addLayout('blog');
		$this->registerView($list);

		$map = new RouterViewConfiguration('map');
		$map->setKey('ids');
		$this->registerView($map);

		$event = new RouterViewConfiguration('event');
		$event->setKey('id');
		$event->setParent($calendar, 'calid');
		$this->registerView($event);

		$form = new RouterViewConfiguration('form');
		$this->registerView($form);

		$bookings = new RouterViewConfiguration('bookings');
		$this->registerView($bookings);

		$tickets = new RouterViewConfiguration('tickets');
		$this->registerView($tickets);

		$ticket = new RouterViewConfiguration('ticket');
		$ticket->setKey('uid');
		$ticket->setParent($tickets, 'id');
		$this->registerView($ticket);

		$locations = new RouterViewConfiguration('locations');
		$locations->setKey('ids');
		$this->registerView($locations);

		$location = new RouterViewConfiguration('location');
		$location->setKey('id');
		$location->setParent($locations, 'id');
		$this->registerView($location);

		$form = new RouterViewConfiguration('locationform');
		$form->setKey('l_id');
		$this->registerView($form);

		$profile = new RouterViewConfiguration('profile');
		$this->registerView($profile);

		parent::__construct($app, $menu);

		if ($params->get('sef_advanced', 1) || version_compare(4, JVERSION, '<')) {
			JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

			$this->attachRule(new DPCalendarRules($this));
			$this->attachRule(new StandardRules($this));
			$this->attachRule(new NomenuRules($this));
		} else {
			JLoader::register('DPCalendarRouterLegacy', __DIR__ . '/helpers/legacyrouter.php');
			$this->attachRule(new DPCalendarRouterLegacy());
		}
	}

	public function build(&$query)
	{
		$this->updateEventParentForQuery($query);

		$active = $this->app->input->get('view');

		// When active is event details view, it should act as parent of the tickets and bookings view
		if ($active === 'event' && !empty($query['view']) && in_array($query['view'], ['tickets', 'bookings'])) {
			$this->views[$query['view']]->setParent($this->views['event'], 'e_id');
		}

		return parent::build($query);
	}

	public function parse(&$segments)
	{
		$active = $this->menu->getActive();

		// Set the active as parent for the event view when it contains calendars
		if ((is_countable($segments) ? count($segments) : 0) === 1 && !empty($active->query['view']) && in_array($active->query['view'], ['calendar', 'list', 'map'])) {
			$this->views['event']->setParent($this->views[$active->query['view']], 'calid');
		}

		return parent::parse($segments);
	}

	private function updateEventParentForQuery($query): void
	{
		// Check if the query is an event view
		if (empty($query['view']) || $query['view'] != 'event' || empty($query['Itemid'])) {
			return;
		}

		// It should have already a correct Itemid
		$items = $this->menu->getItems('id', $query['Itemid']);
		if (!$items) {
			return;
		}

		// The item
		$item = reset($items);

		// Check if the item can fit as parent
		if (!in_array($item->query['view'], ['calendar', 'list', 'map'])) {
			return;
		}

		// Set the active the the current active parent if applicable
		$this->views['event']->setParent($this->views[$item->query['view']], 'calid');
	}

	public function getCalendarSegment($id, $query)
	{
		$category = Categories::getInstance($this->getName())->get($id);

		if (!$category) {
			return [];
		}

		$path    = array_reverse($category->getPath(), true);
		$path[0] = '1:root';

		foreach ($path as &$segment) {
			[$id, $segment] = explode(':', $segment, 2);
		}

		return $path;
	}

	public function getMapSegment($id, $query)
	{
		return $this->getCalendarSegment($id, $query);
	}

	public function getListSegment($id, $query)
	{
		return $this->getCalendarSegment($id, $query);
	}

	public function getEventSegment($id, $query)
	{
		// Hack when no menu item is available for the NomenuRules
		if (empty($query['Itemid'])) {
			return [0 => $id];
		}

		// Return the id when an external event, except when the id is numeric, then it is database caching
		if (!empty($query['calid']) && !is_numeric($query['calid']) && !is_numeric($id)) {
			return [0 => $id];
		}

		if (strpos($id, ':') === 0 || strpos($id, ':') === false) {
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('alias'))
				->from($dbquery->qn('#__dpcalendar_events'))
				->where('id = ' . $dbquery->quote($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		[$void, $segment] = explode(':', $id, 2);

		return [$void => $segment];
	}

	public function getFormSegment($id, $query)
	{
		return $this->getEventSegment($id, $query);
	}

	public function getTicketSegment($uid, $query)
	{
		if (is_numeric($uid)) {
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('uid'))
				->from($dbquery->qn('#__dpcalendar_tickets'))
				->where('id = ' . (int)$uid);
			$db->setQuery($dbquery);
			$uid = $db->loadResult();
		}

		return [$uid => $uid];
	}

	public function getLocationSegment($id, $query)
	{
		// Hack when no menu item is available for the NomenuRules
		if (empty($query['Itemid'])) {
			return [0 => $id];
		}

		if (strpos($id, ':') === 0 || strpos($id, ':') === false) {
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('alias'))
				->from($dbquery->qn('#__dpcalendar_locations'))
				->where('id = ' . $dbquery->quote($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		[$void, $segment] = explode(':', $id, 2);

		return [$void => $segment];
	}

	public function getLocationsSegment($id, $query)
	{
		if (strpos($id, ':') === 0 || strpos($id, ':') === false) {
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('alias'))
				->from($dbquery->qn('#__dpcalendar_locations'))
				->where('id = ' . $dbquery->quote($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		[$void, $segment] = explode(':', $id, 2);

		return [$void => $segment];
	}

	public function getLocationformSegment($id, $query)
	{
		return $this->getLocationSegment($id, $query);
	}

	public function getCalendarId($segment, $query)
	{
		if (isset($query['id'])) {
			$category = Categories::getInstance($this->getName(), ['access' => false])->get($query['id']);

			if ($category) {
				foreach ($category->getChildren() as $child) {
					if ($child->alias == $segment) {
						return $child->id;
					}
				}
			}
		}

		return false;
	}

	public function getListId($segment, $query)
	{
		return $this->getCalendarId($segment, $query);
	}

	public function getMapId($segment, $query)
	{
		return $this->getCalendarId($segment, $query);
	}

	public function getEventId($segment, $query)
	{
		$calIds = [];

		// Get the active calendars
		if ($active = $this->menu->getActive()) {
			$calIds = $active->getParams()->get('ids');
		}

		// Load also the external calendars when all are fetched
		if ((is_countable($calIds) ? count($calIds) : 0) === 1 && $calIds[0] == -1) {
			// Fetch external calendars
			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$calIds[] = $calendar->id;
					}
				}
			}
		}

		foreach ($calIds as $calId) {
			// If the event belongs to the external calendar, return the segment as it is the id
			if (is_numeric($calId)) {
				continue;
			}
			if (strpos($segment, (string) $calId) !== 0) {
				continue;
			}
			return $segment;
		}

		$db      = Factory::getDbo();
		$dbquery = $db->getQuery(true);
		$dbquery->select($dbquery->qn('id'))
			->from($dbquery->qn('#__dpcalendar_events'))
			->where('(alias = ' . $dbquery->quote($segment) . (is_numeric($segment) ? ' or id = ' . (int)$segment : '') . ')');

		if ($calIds && !in_array('-1', $calIds)) {
			// Loop over the calids, they can be string as with DB cache of external events
			$condition = 'catid in (';
			foreach ($calIds as $calId) {
				$condition .= $db->quote($calId) . ',';

				$cal = DPCalendarHelper::getCalendar($calId);
				if (!$cal || !method_exists($cal, 'getChildren')) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$condition .= $db->quote($child->id) . ',';
				}
			}
			$dbquery->where(trim($condition, ',') . ')');
		}

		// Set the query on the database instance
		$db->setQuery($dbquery);

		// Get the result
		$result = (int)$db->loadResult();

		// Can be an external event when there is no menu item for the calendar
		if ($result === 0 && !$calIds) {
			return $segment;
		}

		// Return the result
		return $result;
	}

	public function getTicketId($segment, $query)
	{
		// An uid
		if (!is_numeric($segment)) {
			return $segment;
		}

		$db      = Factory::getDbo();
		$dbquery = $db->getQuery(true);
		$dbquery->select($dbquery->qn('uid'))
			->from($dbquery->qn('#__dpcalendar_tickets'))
			->where('id = ' . (int)$segment);
		$db->setQuery($dbquery);

		return $db->loadResult();
	}

	public function getLocationId($segment, $query)
	{
		$db      = Factory::getDbo();
		$dbquery = $db->getQuery(true);
		$dbquery->select($dbquery->qn('id'))
			->from($dbquery->qn('#__dpcalendar_locations'))
			->where('(alias = ' . $dbquery->quote($segment) . (is_numeric($segment) ? ' or id = ' . (int)$segment : '') . ')');
		$db->setQuery($dbquery);

		return (int)$db->loadResult();
	}
}
