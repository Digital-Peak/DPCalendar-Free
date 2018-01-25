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

class DPCalendarModelLocations extends JModelList
{

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'title',
				'a.title',
				'alias',
				'a.alias',
				'checked_out',
				'a.checked_out',
				'checked_out_time',
				'a.checked_out_time',
				'state',
				'a.state',
				'created',
				'a.created',
				'created_by',
				'a.created_by',
				'ordering',
				'a.ordering',
				'language',
				'a.language',
				'publish_up',
				'a.publish_up',
				'publish_down',
				'a.publish_down',
				'url',
				'a.url'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);
		$authorId = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $authorId);
		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$this->setState('filter.longitude', null);
		$this->setState('filter.longitude', null);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_dpcalendar');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.title', 'asc');
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.language');
		$id .= ':' . $this->getState('filter.latitude');
		$id .= ':' . $this->getState('filter.longitude');

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		$locations = parent::getItems();

		foreach ($locations as $location) {
			if (!is_string($location->rooms)) {
				continue;
			}
			$location->rooms = json_decode($location->rooms);
		}

		return $locations;
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$user  = JFactory::getUser();

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from($db->quoteName('#__dpcalendar_locations') . ' AS a');

		// Join over the language
		$query->select('l.title AS language_title');
		$query->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = a.language');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int)$published);
		} elseif ($published === '') {
			$query->where('(a.state IN (0, 1))');
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
			if (stripos($search, 'ids:') === 0) {
				$ids = explode(',', substr($search, 4));
				ArrayHelper::toInteger($ids);
				$query->where('a.id in (' . implode(',', $ids) . ')');
			} else if (stripos($search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr($search, 3));
			} else {
				$search = $db->Quote('%' . $db->escape($search, true) . '%');

				$query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
			}
		}

		// Filter on the language.
		if ($language = $this->getState('filter.language')) {
			$query->where('a.language = ' . $db->quote($language));
		}

		// Filter on the author.
		if ($createdBy = $this->getState('filter.created_by')) {
			$query->where('a.created_by = ' . $db->quote($createdBy));
		}

		$latitude  = $this->getState('filter.latitude');
		$longitude = $this->getState('filter.longitude');
		if ($latitude && $longitude) {
			$latitude  = str_replace('+', '', round($latitude, 6));
			$longitude = str_replace('+', '', round($longitude, 6));
			$query->where(
				'((a.latitude like ' . $db->quote($latitude) . ' or a.latitude like ' . $db->quote('+' . $latitude) . ') and (a.longitude like ' .
				$db->quote($longitude) . ' or a.longitude like ' . $db->quote('+' . $longitude) . '))');
		}

		$location = $this->getState('filter.location');
		$radius   = $this->getState('filter.radius');
		if (!empty($location)) {
			if (strpos($location, 'latitude=') !== false && strpos($location, 'longitude=') !== false) {
				list ($latitude, $longitude) = explode(';', $location);
				$data            = new stdClass();
				$data->latitude  = str_replace('latitude=', '', $latitude);
				$data->longitude = str_replace('longitude=', '', $longitude);
			} else {
				$data = \DPCalendar\Helper\Location::get($location, false);
			}
			if (!empty($data->latitude) && !empty($data->longitude)) {
				$latitude  = (float)$data->latitude;
				$longitude = (float)$data->longitude;

				if ($radius == -1) {
					$radius = PHP_INT_MAX;
				}

				if ($this->getState('filter.length-type') == 'm') {
					$radius = $radius * 0.62137119;
				}

				$longitudeMin = $longitude - $radius / abs(cos(deg2rad($longitude)) * 69);
				$longitudeMax = $longitude + $radius / abs(cos(deg2rad($longitude)) * 69);
				$latitudeMin  = $latitude - ($radius / 69);
				$latitudeMax  = $latitude + ($radius / 69);

				$query->where(
					'a.longitude > ' . $db->quote($longitudeMin) . " AND
						a.longitude < " . $db->quote($longitudeMax) . " AND
						a.latitude > " . $db->quote($latitudeMin) . " AND
						a.latitude < " . $db->quote($latitudeMax));
			} else {
				$query->where('1 = 0');
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');
		if (!empty($orderCol)) {
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		// Echo nl2br(str_replace('#__', 'j_', $query));// die;
		return $query;
	}
}
