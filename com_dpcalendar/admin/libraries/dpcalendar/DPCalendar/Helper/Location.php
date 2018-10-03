<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Helper;

defined('_JEXEC') or die();

\JTable::addIncludePath(JPATH_ADMINISTRATOR . 'components/com_dpcalendar/tables');

class Location
{
	private static $locationCache = null;
	private static $nomatimLanguages = ['en', 'de', 'it', 'fr'];
	private static $googleLanguages = [
		'ar',
		'eu',
		'bg',
		'bn',
		'ca',
		'cs',
		'da',
		'de',
		'el',
		'en',
		'en-AU',
		'en-GB',
		'es',
		'eu',
		'fa',
		'fi',
		'fil',
		'fr',
		'gl',
		'gu',
		'hi',
		'hr',
		'hu',
		'id',
		'it',
		'iw',
		'ja',
		'kn',
		'ko',
		'lt',
		'lv',
		'nl',
		'ml',
		'mr',
		'nl',
		'nn',
		'no',
		'or',
		'pl',
		'pt',
		'pt-BR',
		'pt-PT',
		'rm',
		'ro',
		'ru',
		'sk',
		'sl',
		'sr',
		'sv',
		'tl',
		'ta',
		'te',
		'th',
		'tr',
		'uk',
		'vi',
		'zh-CN',
		'zh-TW'
	];

	public static function getMapLink($locations)
	{
		if (!$locations) {
			return '';
		}

		$location = urlencode(self::format($locations));

		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'openstreetmap') {
			return 'https://www.openstreetmap.org/search?query=' . $location;
		}

		return 'http://maps.google.com/?q=' . $location;
	}

	public static function format($locations)
	{
		if (!$locations) {
			return '';
		}

		$format = \DPCalendarHelper::getComponentParameter('location_format', 'format_us');
		$format = str_replace('.php', '', $format);

		return \DPCalendarHelper::renderLayout('location.' . $format, array('locations' => $locations));
	}

	/**
	 * Returns a location table for the given location. If the title is set it will use that one instead of the location.
	 *
	 * @param string $location
	 * @param bool   $fill
	 * @param string $title
	 *
	 * @return bool|\JTable
	 */
	public static function get($location, $fill = true, $title = null)
	{
		if (self::$locationCache == null) {
			\JLoader::import('joomla.application.component.model');
			\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			self::$locationCache = \JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
		}

		if ($fill) {
			try {
				self::$locationCache->setState('filter.search', \JApplicationHelper::stringURLSafe($location));
				$locations = self::$locationCache->getItems();
				if ($locations) {
					$locObject = $locations[0];
					if ((int)$locObject->latitude) {
						return $locObject;
					}
				}
			} catch (\Exception $e) {
				\JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			}
		}

		if (!$title) {
			$title = $location;
		}

		if (!isset($locObject)) {
			$locObject            = new \stdClass();
			$locObject->id        = 0;
			$locObject->title     = $title;
			$locObject->alias     = \JApplicationHelper::stringURLSafe($location);
			$locObject->state     = 1;
			$locObject->language  = '*';
			$locObject->country   = '';
			$locObject->province  = '';
			$locObject->city      = '';
			$locObject->zip       = '';
			$locObject->street    = '';
			$locObject->number    = '';
			$locObject->latitude  = 0;
			$locObject->longitude = 0;
		}

		$provider = DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap');
		if ($provider == 'google') {
			self::fillObjectFromGoogle($location, $locObject);
		} else if ($provider == 'mapbox') {
			self::fillObjectFromMapbox($location, $locObject);
		} else {
			self::fillObjectFromOpenStreetMap($location, $locObject);
		}

		if ($fill) {
			try {
				$table = \JTable::getInstance('Location', 'DPCalendarTable');
				$table->bind((array)$locObject);
				if (!$table->store()) {
					\JFactory::getApplication()->enqueueMessage($table->getError(), 'warning');
				}
				$locObject->id = $table->id;
			} catch (\Exception $e) {
				\JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			}

			self::$locationCache = \JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
		}

		return $locObject;
	}

	public static function search($address)
	{
		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'google') {
			return self::searchInGoogle($address);
		}

		return self::searchInOpenStreetMap($address);
	}

	public static function getLocations($locationIds)
	{
		if (empty($locationIds)) {
			return array();
		}
		\JLoader::import('joomla.application.component.model');
		\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$model = \JModelLegacy::getInstance('Locations', 'DPCalendarModel');
		$model->getState();
		$model->setState('filter.search', 'ids:' . implode(',', $locationIds));

		return $model->getItems();
	}

	public static function within($location, $latitude, $longitude, $radius)
	{
		if ($radius == -1) {
			return true;
		}

		if (empty($location->latitude) || empty($location->longitude) || empty($latitude) || empty($longitude)) {
			return false;
		}
		$latitude  = (float)$latitude;
		$longitude = (float)$longitude;

		$longitudeMin = $longitude - $radius / abs(cos(deg2rad($longitude)) * 69);
		$longitudeMax = $longitude + $radius / abs(cos(deg2rad($longitude)) * 69);
		$latitudeMin  = $latitude - ($radius / 69);
		$latitudeMax  = $latitude + ($radius / 69);

		return $location->longitude > $longitudeMin && $location->longitude < $longitudeMax && $location->latitude > $latitudeMin &&
			$location->latitude < $latitudeMax;
	}

	public static function getColor($location)
	{
		return substr(md5($location->latitude . '-' . $location->longitude . '-' . $location->title), 0, 6);
	}

	private static function searchInGoogle($address)
	{
		$key = trim(\DPCalendarHelper::getComponentParameter('map_api_google_key'));

		if (!$key || $key == '-1') {
			return [];
		}

		$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?key=' . $key . '&';

		$lang = \DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, self::$googleLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}

		if (!in_array($lang, self::$googleLanguages)) {
			$url .= 'language=' . $lang . '&';
		}

		$content = \DPCalendar\Helper\DPCalendarHelper::fetchContent($url . 'input=' . urlencode($address));

		if (empty($content)) {
			return [];
		}

		if ($content instanceof \Exception) {
			\JFactory::getApplication()->enqueueMessage((string)$content->getMessage(), 'warning');

			return [];
		}

		$tmp = json_decode($content);

		if (!$tmp) {
			return [];
		}

		if (isset($tmp->error_message)) {
			\JFactory::getApplication()->enqueueMessage($tmp->error_message, 'warning');

			return [];
		}

		if ($tmp->status != 'OK' || empty($tmp->predictions)) {
			return [];
		}

		$data = [];
		foreach ($tmp->predictions as $prediction) {
			$item          = new \stdClass();
			$item->title   = $prediction->description;
			$item->value   = $prediction->description;
			$item->details = '';

			$data[] = $item;
		}

		return $data;
	}

	private static function searchInOpenStreetMap($address)
	{
		$url = 'http://photon.komoot.de/api/?limit=5&';

		$lang = \DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, self::$nomatimLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}

		if (in_array($lang, self::$nomatimLanguages)) {
			$url .= 'lang=' . $lang . '&';
		}

		$content = \DPCalendar\Helper\DPCalendarHelper::fetchContent($url . 'q=' . urlencode($address));

		if (empty($content)) {
			return [];
		}

		if ($content instanceof \Exception) {
			\JFactory::getApplication()->enqueueMessage((string)$content->getMessage(), 'warning');

			return [];
		}

		$tmp = json_decode($content);

		$data = [];
		foreach ($tmp->features as $feature) {
			if (!$feature->properties) {
				continue;
			}

			// Normalize some data
			if ($feature->properties->osm_key == 'place' && $feature->properties->osm_value == 'city' && empty($feature->properties->city)) {
				$feature->properties->city = $feature->properties->name;
				$feature->properties->name = null;
			}
			if ($feature->properties->osm_key == 'place' && $feature->properties->osm_value == 'county' && empty($feature->properties->county)) {
				$feature->properties->county = $feature->properties->name;
				$feature->properties->name = null;
			}

			$item        = new \stdClass();
			$item->value = $feature->geometry->coordinates[1] . ',' . $feature->geometry->coordinates[0];

			$item->title = [];
			if (!empty($feature->properties->country)) {
				$item->title[] = $feature->properties->country;
			}
			if (!empty($feature->properties->state)) {
				$item->title[] = $feature->properties->state;
			}
			if (!empty($feature->properties->county)) {
				$item->title[] = $feature->properties->county;
			}
			if (!empty($feature->properties->city)) {
				$item->title[] = $feature->properties->city;
			}
			if (!empty($feature->properties->postcode)) {
				$item->title[] = $feature->properties->postcode;
			}
			if (!empty($feature->properties->street)) {
				$item->title[] = $feature->properties->street;
			}
			if (!empty($feature->properties->housenumber)) {
				$item->title[] = $feature->properties->housenumber;
			}
			$item->title = implode(', ', $item->title);

			$item->details = [];
			if (!empty($feature->properties->name)) {
				$item->details[] = $feature->properties->name;
			}
			$item->details = implode(' ', $item->details);

			$data[] = $item;
		}

		return $data;
	}

	private static function fillObjectFromGoogle($location, $locObject)
	{
		$key = trim(\DPCalendarHelper::getComponentParameter('map_api_google_key'));

		if (!$key || $key == '-1') {
			return;
		}

		$url = 'https://maps.google.com/maps/api/geocode/json?key=' . $key . '&';

		$lang = \DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, self::$googleLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}

		if (!in_array($lang, self::$googleLanguages)) {
			$url .= 'language=' . $lang . '&';
		}

		$content = \DPCalendarHelper::fetchContent($url . 'address=' . urlencode($location));

		if (empty($content)) {
			return;
		}

		if ($content instanceof \Exception) {
			\JFactory::getApplication()->enqueueMessage((string)$content->getMessage(), 'warning');

			return;
		}
		$tmp = json_decode($content);

		if (!$tmp) {
			return;
		}

		if (isset($tmp->error_message)) {
			\JFactory::getApplication()->enqueueMessage($tmp->error_message, 'warning');

			return;
		}

		if ($tmp->status != 'OK' || empty($tmp->results)) {
			return;
		}

		foreach ($tmp->results[0]->address_components as $part) {
			if (empty($part->types)) {
				continue;
			}
			switch ($part->types[0]) {
				case 'country':
					$locObject->country = $part->long_name;
					break;
				case 'administrative_area_level_1':
					$locObject->province = $part->long_name;
					break;
				case 'locality':
					$locObject->city = $part->long_name;
					break;
				case 'postal_code':
					$locObject->zip = $part->long_name;
					break;
				case 'route':
					$locObject->street = $part->long_name;
					break;
				case 'street_number':
					$locObject->number = $part->long_name;
					break;
			}
		}

		$locObject->latitude  = $tmp->results[0]->geometry->location->lat;
		$locObject->longitude = $tmp->results[0]->geometry->location->lng;
	}

	private static function fillObjectFromOpenStreetMap($location, $locObject)
	{
		$url = DPCalendarHelper::getComponentParameter(
			'map_api_openstreetmap_geocode_url',
			'https://nominatim.openstreetmap.org/search/?format=json&addressdetails=1&limit=1&q={address}'
		);

		$coordinates = explode(',', $location);
		if (count($coordinates) == 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
			$url = str_replace('/search/', '/reverse/', $url);
			$url = str_replace('&q={address}', '', $url);
			$url .= '&lat=' . urlencode($coordinates[0]) . '&lon=' . urlencode($coordinates[1]);
		} else {
			$url = str_replace('{address}', urlencode($location), $url);
		}

		$content = \DPCalendarHelper::fetchContent($url);
		if ($content instanceof \Exception) {
			\JFactory::getApplication()->enqueueMessage((string)$content->getMessage(), 'warning');

			return;
		}

		if (empty($content)) {
			return;
		}

		if ($content instanceof \Exception) {
			\JFactory::getApplication()->enqueueMessage((string)$content->getMessage(), 'warning');

			return;
		}

		$tmp = json_decode($content);

		if (!$tmp || (empty($tmp->address) && empty($tmp[0]->address))) {
			return;
		}

		$addr = !empty($tmp->address) ? $tmp->address : $tmp[0]->address;

		if (!empty($addr->country)) {
			$locObject->country = $addr->country;
		}
		if (!empty($addr->county)) {
			$locObject->province = $addr->county;
		}
		if (!empty($addr->state)) {
			$locObject->province = $addr->state;
		}
		if (!empty($addr->village)) {
			$locObject->city = $addr->village;
		}
		if (!empty($addr->city)) {
			$locObject->city = $addr->city;
		}
		if (!empty($addr->postcode)) {
			$locObject->zip = $addr->postcode;
		}
		if (!empty($addr->road)) {
			$locObject->street = $addr->road;
		}
		if (!empty($addr->house_number)) {
			$locObject->number = $addr->house_number;
		}

		if (empty($tmp->address)) {
			$locObject->latitude  = $tmp[0]->lat;
			$locObject->longitude = $tmp[0]->lon;
		} else if ($coordinates) {
			$locObject->latitude  = $coordinates[0];
			$locObject->longitude = $coordinates[1];
		}
	}

	private static function fillObjectFromMapbox($location, $locObject)
	{
		$coordinates = explode(',', $location);
		if (count($coordinates) == 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
			$location = $coordinates[1] . ',' . $coordinates[0];
		}

		$url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . urlencode($location) . '.json?limit=1&access_token=';

		$url .= DPCalendarHelper::getComponentParameter(
			'map_api_mapbox_token',
			'pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw'
		);

		$lang = \DPCalendarHelper::getFrLanguage();
		$lang = substr($lang, 0, strpos($lang, '-'));
		$url  .= '&language=' . $lang;

		$content = \DPCalendarHelper::fetchContent($url);
		if ($content instanceof \Exception) {
			\JFactory::getApplication()->enqueueMessage((string)$content->getMessage(), 'warning');

			return;
		}

		if (empty($content)) {
			return;
		}

		if ($content instanceof \Exception) {
			\JFactory::getApplication()->enqueueMessage((string)$content->getMessage(), 'warning');

			return;
		}

		$tmp = json_decode($content);

		if (!$tmp || empty($tmp->features)) {
			return;
		}

		$addr = $tmp->features[0];

		if (strpos($addr->id, 'address') === 0) {
			$locObject->street = $addr->text;
		}
		if (!empty($addr->address)) {
			$locObject->number = $addr->address;
		}

		foreach ($addr->context as $c) {
			if (strpos($c->id, 'country') === 0) {
				$locObject->country = $c->text;
			}
			if (strpos($c->id, 'region') === 0) {
				$locObject->province = $c->text;
			}
			if (strpos($c->id, 'place') === 0) {
				$locObject->city = $c->text;
			}
			if (strpos($c->id, 'postcode') === 0) {
				$locObject->zip = $c->text;
			}
		}

		if (!empty($addr->geometry) && !empty($addr->geometry->coordinates)) {
			$locObject->latitude  = $addr->geometry->coordinates[1];
			$locObject->longitude = $addr->geometry->coordinates[0];
		}
	}
}
