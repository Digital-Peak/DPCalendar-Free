<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class LocationsModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
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
			];
		}

		parent::__construct($config, $factory);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Load the filter state
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);
		$authorId = $this->getUserStateFromRequest($this->context . '.filter.author', 'filter_created_by');
		$this->setState('filter.author', $authorId);
		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$this->setState('filter.longitude', null);
		$this->setState('filter.longitude', null);

		$app = Factory::getApplication();
		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));

		// List state information
		parent::populateState('a.title', 'asc');
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.language');
		$id .= ':' . $this->getState('filter.latitude');
		$id .= ':' . $this->getState('filter.longitude');
		$id .= ':' . $this->getState('filter.xreference');

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		$locations = parent::getItems();

		if (!$locations) {
			return [];
		}

		$user = $this->getCurrentUser();
		foreach ($locations as $location) {
			if (empty($location->color)) {
				$location->color = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getColor($location);
			}

			$location->params = new Registry($location->params);
			$location->params->set(
				'access-edit',
				$user->authorise('core.edit', 'com_dpcalendar')
				|| ($user->authorise('core.edit.own', 'com_dpcalendar') && $location->created_by == $user->id)
			);
			$location->params->set(
				'access-delete',
				$user->authorise('core.delete', 'com_dpcalendar')
				|| ($user->authorise('core.edit.own', 'com_dpcalendar') && $location->created_by == $user->id)
			);

			if ($location->country) {
				$country = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator')->getItem($location->country);
				if ($country) {
					Factory::getApplication()->getLanguage()->load(
						'com_dpcalendar.countries',
						JPATH_ADMINISTRATOR . '/components/com_dpcalendar'
					);
					$location->country_code       = $country->short_code;
					$location->country_code_value = Text::_('COM_DPCALENDAR_COUNTRY_' . $country->short_code);
				}
			}

			// When cached items, then this method is called again
			if (is_array($location->rooms) || is_object($location->rooms)) {
				continue;
			}

			if (!is_string($location->rooms) || $location->rooms === '{}') {
				$location->rooms = [];
				continue;
			}
			$location->rooms = json_decode($location->rooms);
		}

		return $locations;
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);
		$user  = $this->getCurrentUser();

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from('#__dpcalendar_locations AS a');

		// Join over the language
		$query->select('l.title AS language_title');
		$query->join('LEFT', '#__languages AS l ON l.lang_code = a.language');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

		// Filter by tags
		$tagIds = (array)$this->getState('filter.tags');
		if ($tagIds !== []) {
			$query->join(
				'LEFT',
				'#__contentitem_tag_map tagmap ON tagmap.content_item_id = a.id AND tagmap.type_alias = ' . $db->quote('com_dpcalendar.location')
			);

			ArrayHelper::toInteger($tagIds);
			$query->where('tagmap.tag_id in (' . implode(',', $tagIds) . ')');
		}

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int)$published);
		} elseif ($published === '') {
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by reference
		if ($reference = $this->getState('filter.xreference')) {
			$query->where('xreference = ' . $db->quote($reference));
		}

		// Filter by search in title
		if ($search = $this->getState('filter.search')) {
			if (stripos((string)$search, 'ids:') === 0) {
				$ids = explode(',', substr((string)$search, 4));
				ArrayHelper::toInteger($ids);
				$query->where('a.id in (' . implode(',', $ids) . ')');
			} elseif (stripos((string)$search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr((string)$search, 3));
			} else {
				$search = $db->quote('%' . $db->escape($search, true) . '%');

				$query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
			}
		}

		// Filter on the language
		if ($language = $this->getState('filter.language')) {
			$query->where('a.language = ' . $db->quote($language));
		}

		$latitude  = trim((string)$this->getState('filter.latitude', ''));
		$longitude = trim((string)$this->getState('filter.longitude', ''));
		if ($latitude && $longitude) {
			$latitude  = round((float)str_replace('+', '', $latitude), 8);
			$longitude = round((float)str_replace('+', '', $longitude), 8);
			$query->where(
				'((a.latitude = ' . $latitude . ' or a.latitude like ' . $db->quote('+' . $latitude) . ') and (a.longitude = ' .
				$longitude . ' or a.longitude like ' . $db->quote('+' . $longitude) . '))'
			);
		}

		$location = $this->getState('filter.location');
		$radius   = (int)$this->getState('filter.radius');
		if (!empty($location)) {
			if (is_object($location)) {
				$data = $location;
			} elseif (str_contains((string)$location, 'latitude=') && str_contains((string)$location, 'longitude=')) {
				[$latitude, $longitude] = explode(';', (string)$location);
				$data                   = new \stdClass();
				$data->latitude         = str_replace('latitude=', '', $latitude);
				$data->longitude        = str_replace('longitude=', '', $longitude);
			} else {
				$data = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($location, false);
			}

			if ($radius > -1 && !empty($data->latitude) && !empty($data->longitude)) {
				$latitude  = (float)$data->latitude;
				$longitude = (float)$data->longitude;

				if ($this->getState('filter.length-type') == 'mile') {
					$radius *= 1.60934;
				}

				$longitudeMin = $longitude - rad2deg(asin($radius / 6371) / cos(deg2rad($latitude)));
				$longitudeMax = $longitude + rad2deg(asin($radius / 6371) / cos(deg2rad($latitude)));
				$latitudeMin  = $latitude - rad2deg($radius / 6371);
				$latitudeMax  = $latitude + rad2deg($radius / 6371);

				$query->where(
					'a.longitude > ' . $longitudeMin . " AND
						a.longitude < " . $longitudeMax . " AND
						a.latitude > " . $latitudeMin . " AND
						a.latitude < " . $latitudeMax
				);
			} elseif ($radius > -1) {
				$query->where('1 = 0');
			}
		}

		if ($author = $this->getState('filter.author')) {
			$query->where('a.created_by = ' . (int)($author == '-1' ? $user->id : $author));
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
