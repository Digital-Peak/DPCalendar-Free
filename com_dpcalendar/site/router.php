<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.categories');
JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

class DPCalendarRouter extends JComponentRouterView
{
	public function __construct($app = null, $menu = null)
	{
		$params = JComponentHelper::getParams('com_dpcalendar');

		$calendar = new JComponentRouterViewconfiguration('calendar');
		$calendar->setKey('ids');
		$this->registerView($calendar);

		$list = new JComponentRouterViewconfiguration('list');
		$list->setKey('ids');
		$list->addLayout('blog');
		$this->registerView($list);

		$map = new JComponentRouterViewconfiguration('map');
		$map->setKey('ids');
		$this->registerView($map);

		$event = new JComponentRouterViewconfiguration('event');
		$event->setKey('id');
		$event->setParent($calendar, 'calid');
		$this->registerView($event);

		$form = new JComponentRouterViewconfiguration('form');
		$form->setKey('e_id');
		$this->registerView($form);

		$locations = new JComponentRouterViewconfiguration('locations');
		$locations->setKey('ids');
		$this->registerView($locations);

		$location = new JComponentRouterViewconfiguration('location');
		$location->setKey('id');
		$location->setParent($locations, 'id');
		$this->registerView($location);

		$form = new JComponentRouterViewconfiguration('locationform');
		$form->setKey('l_id');
		$this->registerView($form);

		$bookings = new JComponentRouterViewconfiguration('bookings');
		$this->registerView($bookings);

		$tickets = new JComponentRouterViewconfiguration('tickets');
		$this->registerView($tickets);

		$profile = new JComponentRouterViewconfiguration('profile');
		$this->registerView($profile);

		parent::__construct($app, $menu);

		if ($params->get('sef_advanced', 1)) {
			$this->attachRule(new \DPCalendar\Router\Rules\DPCalendarRules($this));
			$this->attachRule(new JComponentRouterRulesStandard($this));
			$this->attachRule(new JComponentRouterRulesNomenu($this));
		} else {
			JLoader::register('DPCalendarRouterLegacy', __DIR__ . '/helpers/legacyrouter.php');
			$this->attachRule(new DPCalendarRouterLegacy());
		}
	}

	public function build(&$query)
	{
		$this->updateEventParentForQuery($query);

		return parent::build($query);
	}

	public function parse(&$segments)
	{
		$active = $this->menu->getActive();
		if (count($segments) == 1 && !empty($active->query['view']) && in_array($active->query['view'], ['calendar', 'list', 'map'])) {
			$this->views['event']->setParent($this->views[$active->query['view']], 'calid');
		}

		return parent::parse($segments);
	}

	private function updateEventParentForQuery($query)
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
		$category = JCategories::getInstance($this->getName())->get($id);

		if (!$category) {
			return [];
		}

		$path    = array_reverse($category->getPath(), true);
		$path[0] = '1:root';

		foreach ($path as &$segment) {
			list($id, $segment) = explode(':', $segment, 2);
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

		if (!strpos($id, ':')) {
			$db      = JFactory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('alias'))
				->from($dbquery->qn('#__dpcalendar_events'))
				->where('id = ' . $dbquery->q($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		list($void, $segment) = explode(':', $id, 2);

		return array($void => $segment);
	}

	public function getFormSegment($id, $query)
	{
		return $this->getEventSegment($id, $query);
	}

	public function getLocationSegment($id, $query)
	{
		// Hack when no menu item is available for the NomenuRules
		if (empty($query['Itemid'])) {
			return [0 => $id];
		}

		if (!strpos($id, ':')) {
			$db      = JFactory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('alias'))
				->from($dbquery->qn('#__dpcalendar_locations'))
				->where('id = ' . $dbquery->q($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		list($void, $segment) = explode(':', $id, 2);

		return array($void => $segment);
	}

	public function getLocationsSegment($id, $query)
	{
		if (!strpos($id, ':')) {
			$db      = JFactory::getDbo();
			$dbquery = $db->getQuery(true);
			$dbquery->select($dbquery->qn('alias'))
				->from($dbquery->qn('#__dpcalendar_locations'))
				->where('id = ' . $dbquery->q($id));
			$db->setQuery($dbquery);

			$id .= ':' . $db->loadResult();
		}

		list($void, $segment) = explode(':', $id, 2);

		return array($void => $segment);
	}

	public function getLocationformSegment($id, $query)
	{
		return $this->getLocationSegment($id, $query);
	}

	public function getCalendarId($segment, $query)
	{
		if (isset($query['id'])) {
			$category = JCategories::getInstance($this->getName(), array('access' => false))->get($query['id']);

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

		if ($active = $this->menu->getActive()) {
			$calIds = $active->getParams()->get('ids');
		}

		foreach ($calIds as $index => $calId) {
			// If the event belongs to the external calendar, return the segment as it is the id
			if (!is_numeric($calId) && strpos($segment, $calId) === 0) {
				return $segment;
			}
		}

		$db      = JFactory::getDbo();
		$dbquery = $db->getQuery(true);
		$dbquery->select($dbquery->qn('id'))
			->from($dbquery->qn('#__dpcalendar_events'))
			->where('alias = ' . $dbquery->q($segment));

		if ($calIds && !in_array('-1', $calIds)) {
			// Loop over the calids, they can be string as with DB cache of external events
			$condition = 'catid in (';
			foreach ($calIds as $calId) {
				$condition .= $db->q($calId) . ',';

				$cal = DPCalendarHelper::getCalendar($calId);
				if (!$cal || !method_exists($cal, 'getChildren')) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$condition .= $db->q($child->id) . ',';
				}
			}
			$dbquery->where(trim($condition, ',') . ')');
		}

		$db->setQuery($dbquery);

		return (int)$db->loadResult();
	}

	public function getLocationId($segment, $query)
	{
		$db      = JFactory::getDbo();
		$dbquery = $db->getQuery(true);
		$dbquery->select($dbquery->qn('id'))
			->from($dbquery->qn('#__dpcalendar_locations'))
			->where('alias = ' . $dbquery->q($segment));
		$db->setQuery($dbquery);

		return (int)$db->loadResult();
	}
}
