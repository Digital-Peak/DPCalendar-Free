<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Service;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Router\Rules\DPCalendarRules;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;

class Router extends RouterView
{
	use DatabaseAwareTrait;

	public function __construct(SiteApplication $app, AbstractMenu $menu, private CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
	{
		ComponentHelper::getParams('com_dpcalendar');

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

		$this->setDatabase($db);

		$this->attachRule(new DPCalendarRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	public function build(&$query)
	{
		$this->updateEventParentForQuery($query);

		$active = $this->app->getInput()->get('view');

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
		if (count($segments) === 1 && !empty($active->query['view']) && in_array($active->query['view'], ['calendar', 'list', 'map'])) {
			$this->views['event']->setParent($this->views[$active->query['view']], 'calid');
		}

		return parent::parse($segments);
	}

	private function updateEventParentForQuery(array $query): void
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

	public function getCalendarSegment(string $id): array
	{
		$category = $this->categoryFactory->createCategory()->get($id);

		if ($category === null) {
			return [];
		}

		$path    = array_reverse($category->getPath(), true);
		$path[0] = '1:root';

		foreach ($path as &$segment) {
			[$id, $segment] = explode(':', (string)$segment, 2);
		}

		return $path;
	}

	public function getMapSegment(string $id): array
	{
		return $this->getCalendarSegment($id);
	}

	public function getListSegment(string $id): array
	{
		return $this->getCalendarSegment($id);
	}

	public function getEventSegment(string $id, array $query): array
	{
		// Hack when no menu item is available for the NomenuRules
		if (empty($query['Itemid'])) {
			return [0 => $id];
		}

		// Return the id when an external event, except when the id is numeric, then it is database caching
		if (!empty($query['calid']) && !is_numeric($query['calid']) && !is_numeric($id)) {
			return [0 => $id];
		}

		if (str_starts_with($id, ':') || !str_contains($id, ':')) {
			$db      = $this->getDatabase();
			$dbquery = $db->getQuery(true);
			$dbquery->select('alias')
				->from('#__dpcalendar_events')
				->where('id = ' . $db->quote($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		[$void, $segment] = explode(':', $id, 2);

		return [$void => $segment];
	}

	public function getFormSegment(string $id, array $query): array
	{
		return $this->getEventSegment($id, $query);
	}

	public function getTicketSegment(string $uid): array
	{
		if (is_numeric($uid)) {
			$db      = $this->getDatabase();
			$dbquery = $db->getQuery(true);
			$dbquery->select('uid')
				->from('#__dpcalendar_tickets')
				->where('id = ' . (int)$uid);
			$db->setQuery($dbquery);
			$uid = $db->loadResult();
		}

		return [$uid => $uid];
	}

	public function getLocationSegment(string $id, array $query): array
	{
		// Hack when no menu item is available for the NomenuRules
		if (empty($query['Itemid'])) {
			return [0 => $id];
		}

		if (str_starts_with($id, ':') || !str_contains($id, ':')) {
			$db      = $this->getDatabase();
			$dbquery = $db->getQuery(true);
			$dbquery->select('alias')
				->from('#__dpcalendar_locations')
				->where('id = ' . $db->quote($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		[$void, $segment] = explode(':', $id, 2);

		return [$void => $segment];
	}

	public function getLocationsSegment(string $id): array
	{
		if (str_starts_with($id, ':') || !str_contains($id, ':')) {
			$db      = $this->getDatabase();
			$dbquery = $db->getQuery(true);
			$dbquery->select('alias')
				->from('#__dpcalendar_locations')
				->where('id = ' . $db->quote($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		[$void, $segment] = explode(':', $id, 2);

		return [$void => $segment];
	}

	public function getLocationformSegment(string $id, array $query): array
	{
		return $this->getLocationSegment($id, $query);
	}

	public function getCalendarId(string $segment, array $query): string
	{
		if (isset($query['id'])) {
			$category = $this->categoryFactory->createCategory(['access' => false])->get($query['id']);

			if ($category !== null) {
				foreach ($category->getChildren() as $child) {
					if ($child->alias == $segment) {
						return (string)$child->id;
					}
				}
			}
		}

		return '';
	}

	public function getListId(string $segment, array $query): string
	{
		return $this->getCalendarId($segment, $query);
	}

	public function getMapId(string $segment, array $query): string
	{
		return $this->getCalendarId($segment, $query);
	}

	public function getEventId(string $segment): string
	{
		$calIds = [];

		// Get the active calendars
		if (($active = $this->menu->getActive()) !== null) {
			$calIds = $active->getParams()->get('ids');
		}

		// Load also the external calendars when all are fetched
		if ((is_countable($calIds) ? count($calIds) : 0) === 1 && $calIds[0] == -1) {
			// Fetch external calendars
			PluginHelper::importPlugin('dpcalendar');
			$tmp = $this->app->triggerEvent('onCalendarsFetch');
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$calIds[] = $calendar->getId();
					}
				}
			}
		}

		foreach ($calIds as $calId) {
			// If the event belongs to the external calendar, return the segment as it is the id
			if (is_numeric($calId)) {
				continue;
			}
			if (!str_starts_with($segment, (string)$calId)) {
				continue;
			}
			return $segment;
		}

		$db      = $this->getDatabase();
		$dbquery = $db->getQuery(true);
		$dbquery->select('id')
			->from('#__dpcalendar_events')
			->where('(alias = ' . $db->quote($segment) . (is_numeric($segment) ? ' or id = ' . (int)$segment : '') . ')');

		if ($calIds && !in_array('-1', $calIds)) {
			// Loop over the calids, they can be string as with DB cache of external events
			$condition = 'catid in (';
			foreach ($calIds as $calId) {
				$condition .= $db->quote($calId) . ',';

				$cal = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($calId);
				if (!$cal instanceof CategoryNode) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$condition .= $db->quote((string)$child->id) . ',';
				}
			}
			$dbquery->where(trim($condition, ',') . ')');
		}

		// Set the query on the database instance
		$db->setQuery($dbquery);

		// Get the result
		$result = $db->loadResult();

		// Can be an external event when there is no menu item for the calendar
		if (empty($result) && !$calIds) {
			return $segment;
		}

		// Return the result
		return $result;
	}

	public function getTicketId(string $segment): string
	{
		// An uid
		if (!is_numeric($segment)) {
			return $segment;
		}

		$db      = $this->getDatabase();
		$dbquery = $db->getQuery(true);
		$dbquery->select('uid')
			->from('#__dpcalendar_tickets')
			->where('id = ' . (int)$segment);
		$db->setQuery($dbquery);

		return $db->loadResult();
	}

	public function getLocationId(string $segment): int
	{
		$db      = $this->getDatabase();
		$dbquery = $db->getQuery(true);
		$dbquery->select('id')
			->from('#__dpcalendar_locations')
			->where('(alias = ' . $db->quote($segment) . (is_numeric($segment) ? ' or id = ' . (int)$segment : '') . ')');
		$db->setQuery($dbquery);

		return (int)$db->loadResult();
	}
}
