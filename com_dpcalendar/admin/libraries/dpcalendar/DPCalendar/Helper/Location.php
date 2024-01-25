<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Helper;

defined('_JEXEC') or die();

use DigitalPeak\ThinHTTP as HTTP;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

Table::addIncludePath(JPATH_ADMINISTRATOR . 'components/com_dpcalendar/tables');

class Location
{
	private static $locationCache;
	private static array $nominatimLanguages = ['en', 'de', 'it', 'fr'];
	private static array $googleLanguages    = [
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

	public static function getDirectionsLink($location, $zoom = 6): string
	{
		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'openstreetmap') {
			return 'https://www.openstreetmap.org/directions?route=;' . $location->latitude . ',' . $location->longitude
				. '#map=' . (int)$zoom . '/' . $location->latitude . '/' . $location->longitude;
		}

		return 'https://www.google.com/maps/dir/?api=1&destination=' . self::format([$location]);
	}

	public static function getMapLink($location, $zoom = 6): string
	{
		if (!$location || DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'none') {
			return '';
		}

		$locationString = urlencode(self::format($location));

		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'openstreetmap') {
			return 'https://www.openstreetmap.org/search?query=' . $locationString . '#map=' . (int)$zoom . '/';
		}

		return 'http://maps.google.com/?q=' . $locationString;
	}

	public static function format($locations)
	{
		if (!$locations) {
			return '';
		}

		$format = DPCalendarHelper::getComponentParameter('location_format', 'format_us');
		$format = str_replace('.php', '', $format);

		return DPCalendarHelper::renderLayout('location.' . $format, ['locations' => $locations]);
	}

	/**
  * Returns a location table for the given location. If the title is set it will use that one instead of the location.
  *
  * @param string $location
  * @param bool   $fill
  * @param string $title
  *
  * @return bool|\Joomla\CMS\Table\Table
  */
	public static function get($location, $fill = true, $title = null)
	{
		$location = trim($location);

		if (self::$locationCache == null) {
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			self::$locationCache = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
		}

		if ($fill) {
			try {
				$coordinates = explode(',', $location);
				if (count($coordinates) == 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
					self::$locationCache->setState('filter.latitude', $coordinates[0]);
					self::$locationCache->setState('filter.longitude', $coordinates[1]);
				} else {
					self::$locationCache->setState('filter.xreference', $location);
				}

				$locations = self::$locationCache->getItems();

				// When no items search in alias as it can be the same for different locations because of stripping out characters like double whitespaces
				if (!$locations && self::$locationCache->getState('filter.xreference')) {
					self::$locationCache->setState('filter.xreference', null);
					self::$locationCache->setState('filter.search', ApplicationHelper::stringURLSafe($location));
					$locations = self::$locationCache->getItems();
				}

				if ($locations) {
					$locObject = $locations[0];
					if ((int)$locObject->latitude !== 0) {
						return $locObject;
					}
				}
			} catch (\Exception $e) {
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			}
		}

		if (!$title) {
			$title = $location;
		}

		if (!isset($locObject)) {
			$locObject              = new \stdClass();
			$locObject->id          = 0;
			$locObject->title       = $title;
			$locObject->alias       = ApplicationHelper::stringURLSafe($title);
			$locObject->state       = 1;
			$locObject->language    = '*';
			$locObject->country     = 0;
			$locObject->province    = '';
			$locObject->city        = '';
			$locObject->zip         = '';
			$locObject->street      = '';
			$locObject->number      = '';
			$locObject->url         = '';
			$locObject->description = '';
			$locObject->latitude    = 0;
			$locObject->longitude   = 0;
			$locObject->color       = self::getColor($locObject);
			$locObject->params      = new Registry();
			$locObject->xreference  = $location;
		}

		$provider = DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap');
		if ($provider == 'google') {
			self::fillObjectFromGoogle($location, $locObject);
		}
		if ($provider == 'mapbox') {
			self::fillObjectFromMapbox($location, $locObject);
		}
		if ($provider == 'openstreetmap') {
			self::fillObjectFromOpenStreetMap($location, $locObject);
		}

		// Reset coordinates, so we have them always the same. Providers can shift them
		$coordinates = explode(',', $location);
		if (count($coordinates) == 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
			$locObject->latitude  = $coordinates[0];
			$locObject->longitude = $coordinates[1];
		}

		if ($fill) {
			try {
				$table = Table::getInstance('Location', 'DPCalendarTable');
				if (!$table->save((array)$locObject)) {
					Factory::getApplication()->enqueueMessage($table->getError(), 'warning');
				}
				$locObject->id = $table->id;
			} catch (\Exception $e) {
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			}

			self::$locationCache = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
		}

		return $locObject;
	}

	public static function search($address): array
	{
		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'none') {
			return [];
		}

		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'google') {
			return self::searchInGoogle($address);
		}

		return self::searchInOpenStreetMap($address);
	}

	public static function getLocations($locationIds)
	{
		if (empty($locationIds)) {
			return [];
		}
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel');
		$model->getState();
		$model->setState('filter.search', 'ids:' . implode(',', $locationIds));

		return $model->getItems();
	}

	public static function within($location, $latitude, $longitude, $radius): bool
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

	public static function getColor($location): string
	{
		return substr(md5($location->latitude . '-' . $location->longitude . '-' . $location->title), 0, 6);
	}

	public static function getCountryForIp()
	{
		$geoDBDirectory = Factory::getApplication()->get('tmp_path') . '/DPCalendar-Geodb';
		$files          = is_dir($geoDBDirectory) ? scandir($geoDBDirectory) : [];

		// Check if the data is available
		if ((is_countable($files) ? count($files) : 0) < 3) {
			return '';
		}

		// Determine the IP address of the current user
		$ip = $_SERVER['REMOTE_ADDR'];

		// Compile the file name
		$fileName = $geoDBDirectory . '/' . current(explode('.', $ip)) . '.php';
		if (!file_exists($fileName)) {
			return '';
		}

		try {
			// Read the data from the file
			$data = require $fileName;
		} catch (\Throwable|\Exception $e) {
			return '';
		}

		// Convert the IP to long
		$number = ip2long($ip);
		foreach ($data as $range) {
			if ($number < $range[0] || $number > $range[1]) {
				continue;
			}

			// Check if a special IP address like localhost
			if ($range[2] == 'ZZ') {
				return '';
			}

			// Get the country by short code
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$model = BaseDatabaseModel::getInstance('Country', 'DPCalendarModel', ['ignore_request' => true]);

			return $model->getItem(['short_code' => $range[2]]);
		}

		return '';
	}

	/**
	 * @return mixed[]
	 */
	private static function searchInGoogle($address): array
	{
		$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?key=' . trim(\DPCalendarHelper::getComponentParameter('map_api_google_key')) . '&';

		$lang = \DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, self::$googleLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}

		if (!in_array($lang, self::$googleLanguages)) {
			$url .= 'language=' . $lang . '&';
		}

		$tmp = (new HTTP())->get($url . 'input=' . urlencode($address));
		if (!empty($tmp->error_message)) {
			Factory::getApplication()->enqueueMessage($tmp->error_message, 'warning');

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

	/**
	 * @return mixed[]
	 */
	private static function searchInOpenStreetMap($address): array
	{
		$url = Uri::getInstance(DPCalendarHelper::getComponentParameter('map_api_openstreetmap_geocode_url', 'https://nominatim.openstreetmap.org'));
		$url->setVar('format', 'json');
		$url->setVar('addressdetails', 1);
		$url->setVar('limit', 5);

		$coordinates = explode(',', $address);
		if (count($coordinates) == 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
			$url->setPath('/reverse');
			$url->setVar('lat', urlencode($coordinates[0]));
			$url->setVar('lon', urlencode($coordinates[1]));
		} else {
			$url->setPath($url->getPath() . '/search');
			$url->setVar('q', urlencode($address));
		}

		$lang = DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, self::$nominatimLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}

		if (in_array($lang, self::$nominatimLanguages)) {
			$url->setVar('accept-language', $lang);
		}

		$tmp = (new HTTP())->get(
			$url->toString(),
			null,
			null,
			['Accept: application/json, text/html']
		);
		if (!$tmp instanceof \stdClass || (empty($tmp->address) && (empty($tmp->data) || empty($tmp->data[0]->address)))) {
			return [];
		}

		$data = [];
		foreach ($tmp->data as $address) {
			if (!$address->address) {
				continue;
			}

			$item = new \stdClass();

			if (empty($address->name)) {
				$item->title = [];
				if (!empty($address->address->country)) {
					$item->title[] = $address->address->country;
				}
				if (!empty($address->address->state)) {
					$item->title[] = $address->address->state;
				}
				if (!empty($address->address->county)) {
					$item->title[] = $address->address->county;
				}
				if (!empty($address->address->town)) {
					$item->title[] = $address->address->town;
				}
				if (!empty($address->address->postcode)) {
					$item->title[] = $address->address->postcode;
				}
				if (!empty($address->address->road)) {
					$item->title[] = $address->address->road;
				}
				if (!empty($address->address->house_number)) {
					$item->title[] = $address->address->house_number;
				}
				$item->title = implode(', ', $item->title);
			}

			$item->value = $address->lat . ',' . $address->lon;
			$item->title = $address->name ?? $item->title;
			// $address->display_name is a comma separated list, so when the name is empty that list is the title
			$item->details = empty($address->name) ? '' : ($address->display_name ?? '');

			$data[] = $item;
		}

		return $data;
	}

	private static function fillObjectFromGoogle($location, $locObject): void
	{
		$url = 'https://maps.google.com/maps/api/geocode/json?key=' . trim(DPCalendarHelper::getComponentParameter('map_api_google_key')) . '&';

		$lang = DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, self::$googleLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}

		if (!in_array($lang, self::$googleLanguages)) {
			$url .= 'language=' . $lang . '&';
		}

		$tmp = (new HTTP())->get($url . 'address=' . urlencode($location));
		if (!empty($tmp->error_message)) {
			Factory::getApplication()->enqueueMessage($tmp->error_message, 'warning');

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
					// Get the country by short code
					BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
					$model = BaseDatabaseModel::getInstance('Country', 'DPCalendarModel', ['ignore_request' => true]);

					$loc = $model->getItem(['short_code' => strtoupper($part->short_name)]);
					if ($loc && $loc->id) {
						$locObject->country = $loc->id;
					}
					break;
				case 'administrative_area_level_1':
					$locObject->province = $part->long_name;
					break;
				case 'locality':
				case 'postal_town':
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

	private static function fillObjectFromOpenStreetMap($location, $locObject): void
	{
		$url = Uri::getInstance(DPCalendarHelper::getComponentParameter('map_api_openstreetmap_geocode_url', 'https://nominatim.openstreetmap.org'));
		$url->setVar('format', 'json');
		$url->setVar('addressdetails', 1);
		$url->setVar('limit', 1);

		$coordinates = explode(',', $location);
		if (count($coordinates) == 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
			$url->setPath($url->getPath() . '/reverse');
			$url->setVar('lat', urlencode($coordinates[0]));
			$url->setVar('lon', urlencode($coordinates[1]));

			$locObject->latitude  = $coordinates[0];
			$locObject->longitude = $coordinates[1];
		} else {
			$url->setPath($url->getPath() . '/search');
			$url->setVar('q', urlencode($location));
		}

		$lang = DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, self::$nominatimLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}

		if (in_array($lang, self::$nominatimLanguages)) {
			$url->setVar('accept-language', $lang);
		}

		$tmp = (new HTTP())->get(
			$url->toString(),
			null,
			null,
			['Accept: application/json, text/html']
		);
		if (!$tmp instanceof \stdClass || (empty($tmp->address) && (empty($tmp->data) || empty($tmp->data[0]->address)))) {
			return;
		}

		if (!empty($tmp->data) && is_array($tmp->data)) {
			$tmp = $tmp->data[0];
		}

		if (!empty($tmp->address->country_code)) {
			// Get the country by short code
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$model = BaseDatabaseModel::getInstance('Country', 'DPCalendarModel', ['ignore_request' => true]);

			$loc = $model->getItem(['short_code' => strtoupper($tmp->address->country_code)]);
			if ($loc && $loc->id) {
				$locObject->country = $loc->id;
			}
		}
		if (!empty($tmp->address->county)) {
			$locObject->province = $tmp->address->county;
		}
		if (!empty($tmp->address->state)) {
			$locObject->province = $tmp->address->state;
		}
		if (!empty($tmp->address->village)) {
			$locObject->city = $tmp->address->village;
		}
		if (!empty($tmp->address->city)) {
			$locObject->city = $tmp->address->city;
		}
		if (!empty($tmp->address->town)) {
			$locObject->city = $tmp->address->town;
		}
		if (!empty($tmp->address->postcode)) {
			$locObject->zip = $tmp->address->postcode;
		}
		if (!empty($tmp->address->road)) {
			$locObject->street = $tmp->address->road;
		}
		if (!empty($tmp->address->house_number)) {
			$locObject->number = $tmp->address->house_number;
		}
		if (!empty($tmp->lat)) {
			$locObject->latitude = $tmp->lat;
		}
		if (!empty($tmp->lon)) {
			$locObject->longitude = $tmp->lon;
		}
	}

	private static function fillObjectFromMapbox($location, $locObject): void
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

		$lang = DPCalendarHelper::getFrLanguage();
		$lang = substr($lang, 0, strpos($lang, '-'));
		$url .= '&language=' . $lang;

		$tmp = (new HTTP())->get($url);
		if (!$tmp instanceof \stdClass || empty($tmp->features)) {
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
			if (strpos($c->id, 'country') === 0 && !empty($c->short_code)) {
				// Get the country by short code
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
				$model = BaseDatabaseModel::getInstance('Country', 'DPCalendarModel', ['ignore_request' => true]);

				$loc = $model->getItem(['short_code' => strtoupper($c->short_code)]);
				if ($loc && $loc->id) {
					$locObject->country = $loc->id;
				}
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
