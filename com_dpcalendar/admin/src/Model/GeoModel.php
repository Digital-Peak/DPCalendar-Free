<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\ThinHTTP\CurlClient;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class GeoModel extends BaseDatabaseModel
{
	private ?LocationsModel $locationCache = null;
	private array $nominatimLanguages      = ['en', 'de', 'it', 'fr'];
	private array $googleLanguages         = [
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

	public function getDirectionsLink(\stdClass $location, int $zoom = 6): string
	{
		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'openstreetmap') {
			return 'https://www.openstreetmap.org/directions?route=;' . $location->latitude . ',' . $location->longitude
				. '#map=' . $zoom . '/' . $location->latitude . '/' . $location->longitude;
		}

		return 'https://www.google.com/maps/dir/?api=1&destination=' . $this->format([$location]);
	}

	public function getMapLink(\stdClass $location, int $zoom = 6): string
	{
		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'none') {
			return '';
		}

		$locationString = urlencode($this->format($location));

		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'openstreetmap') {
			return 'https://www.openstreetmap.org/search?query=' . $locationString . '#map=' . $zoom . '/';
		}

		return 'http://maps.google.com/?q=' . $locationString;
	}

	public function format(array|\stdClass $locations): string
	{
		if (!$locations) {
			return '';
		}

		$format = DPCalendarHelper::getComponentParameter('location_format', 'format_us');
		$format = str_replace('.php', '', (string)$format);

		return DPCalendarHelper::renderLayout('location.' . $format, ['locations' => $locations]);
	}

	/**
	 * Returns a location table for the given location. If the title is set it will use that one instead of the location.
	 */
	public function getLocation(string $location, bool $fill = true, ?string $title = null): \stdClass
	{
		/** @var MVCFactoryInterface $factory */
		$factory = $this->bootComponent('dpcalendar')->getMVCFactory();

		$location = trim($location);

		if (!$this->locationCache instanceof LocationsModel) {
			$this->locationCache = $factory->createModel('Locations', 'Administrator', ['ignore_request' => true]);
		}

		if ($fill) {
			try {
				$coordinates = explode(',', $location);
				if (count($coordinates) == 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
					$this->locationCache->setState('filter.latitude', $coordinates[0]);
					$this->locationCache->setState('filter.longitude', $coordinates[1]);
					$this->locationCache->setState('filter.xreference', null);
					$this->locationCache->setState('filter.search', null);
				} else {
					$this->locationCache->setState('filter.latitude', 0);
					$this->locationCache->setState('filter.longitude', 0);
					$this->locationCache->setState('filter.xreference', $location);
					$this->locationCache->setState('filter.search', null);
				}

				$locations = $this->locationCache->getItems();

				// When no items search in alias as it can be the same for different locations because of stripping out characters like double whitespaces
				if (!$locations && $this->locationCache->getState('filter.xreference')) {
					$this->locationCache->setState('filter.latitude', 0);
					$this->locationCache->setState('filter.longitude', 0);
					$this->locationCache->setState('filter.xreference', null);
					$this->locationCache->setState('filter.search', ApplicationHelper::stringURLSafe($location));
					$locations = $this->locationCache->getItems();
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

		if ($title === null || $title === '' || $title === '0') {
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
			$locObject->color       = $this->getColor($locObject);
			$locObject->params      = new Registry();
			$locObject->xreference  = $location;
		}

		$provider = DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap');
		if ($provider == 'google') {
			$this->fillObjectFromGoogle($location, $locObject);
		}
		if ($provider == 'mapbox') {
			$this->fillObjectFromMapbox($location, $locObject);
		}
		if ($provider == 'openstreetmap') {
			$this->fillObjectFromOpenStreetMap($location, $locObject);
		}

		// Reset coordinates, so we have them always the same. Providers can shift them
		$coordinates = explode(',', $location);
		if (count($coordinates) == 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
			$locObject->latitude  = $coordinates[0];
			$locObject->longitude = $coordinates[1];
		}

		if ($fill) {
			try {
				$table = $factory->createTable('Location', 'Administrator');
				if (!$table->save((array)$locObject)) {
					Factory::getApplication()->enqueueMessage($table->getError(), 'warning');
				}
				$locObject->id = $table->id;
			} catch (\Exception $e) {
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			}

			$this->locationCache = $factory->createModel('Locations', 'Administrator', ['ignore_request' => true]);
		}

		return $locObject;
	}

	public function search(string $address): array
	{
		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'none') {
			return [];
		}

		if (DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap') == 'google') {
			return $this->searchInGoogle($address);
		}

		return $this->searchInOpenStreetMap($address);
	}

	public function getLocations(array $locationIds): array
	{
		if ($locationIds === []) {
			return [];
		}

		$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator');
		$model->getState();
		$model->setState('filter.search', 'ids:' . implode(',', $locationIds));

		return $model->getItems();
	}

	public function within(\stdClass $location, float $latitude, float $longitude, float $radius): bool
	{
		if ($radius == -1) {
			return true;
		}

		if (empty($location->latitude) || empty($location->longitude) || empty($latitude) || empty($longitude)) {
			return false;
		}

		$longitudeMin = $longitude - $radius / abs(cos(deg2rad($longitude)) * 69);
		$longitudeMax = $longitude + $radius / abs(cos(deg2rad($longitude)) * 69);
		$latitudeMin  = $latitude - ($radius / 69);
		$latitudeMax  = $latitude + ($radius / 69);

		return $location->longitude > $longitudeMin && $location->longitude < $longitudeMax && $location->latitude > $latitudeMin &&
			$location->latitude < $latitudeMax;
	}

	public function getColor(\stdClass $location): string
	{
		return substr(md5($location->latitude . '-' . $location->longitude . '-' . $location->title), 0, 6);
	}

	public function getCountryForIp(): ?\stdClass
	{
		$geoDBDirectory = Factory::getApplication()->get('tmp_path') . '/DPCalendar-Geodb';
		$files          = is_dir($geoDBDirectory) ? scandir($geoDBDirectory) : [];

		// Check if the data is available
		if ((is_countable($files) ? count($files) : 0) < 3) {
			return null;
		}

		// Determine the IP address of the current user
		$ip = $_SERVER['REMOTE_ADDR'];

		// Compile the file name
		$fileName = $geoDBDirectory . '/' . current(explode('.', (string)$ip)) . '.php';
		if (!file_exists($fileName)) {
			return null;
		}

		try {
			// Read the data from the file
			$data = require $fileName;
		} catch (\Throwable|\Exception) {
			return null;
		}

		// Convert the IP to long
		$number = ip2long($ip);
		foreach ($data as $range) {
			if ($number < $range[0] || $number > $range[1]) {
				continue;
			}

			// Check if a special IP address like localhost
			if ($range[2] == 'ZZ') {
				return null;
			}

			// Get the country by short code
			$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator', ['ignore_request' => true]);

			return $model->getItem(['short_code' => $range[2]]) ?: null;
		}

		return null;
	}

	private function searchInGoogle(string $address): array
	{
		$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?key=' . trim((string)DPCalendarHelper::getComponentParameter('map_api_google_key')) . '&';

		$lang = DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, $this->googleLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-') ?: 0);
		}

		if (!in_array($lang, $this->googleLanguages)) {
			$url .= 'language=' . $lang . '&';
		}

		$tmp = (new CurlClient())->get($url . 'input=' . urlencode($address));
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

	private function searchInOpenStreetMap(string $address): array
	{
		$url = Uri::getInstance(DPCalendarHelper::getComponentParameter('map_api_openstreetmap_geocode_url', 'https://nominatim.openstreetmap.org'));
		$url->setVar('format', 'json');
		$url->setVar('addressdetails', '1');
		$url->setVar('limit', '5');

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
		if (!in_array($lang, $this->nominatimLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-') ?: 0);
		}

		if (in_array($lang, $this->nominatimLanguages)) {
			$url->setVar('accept-language', $lang);
		}

		$tmp = (new CurlClient())->get(
			$url->toString(),
			null,
			null,
			['Accept: application/json, text/html']
		);
		if (empty($tmp->address) && (empty($tmp->data) || empty($tmp->data[0]->address))) {
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

	private function fillObjectFromGoogle(string $location, \stdClass $locObject): void
	{
		$url = 'https://maps.google.com/maps/api/geocode/json?key=' . trim((string)DPCalendarHelper::getComponentParameter('map_api_google_key')) . '&';

		$lang = DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, $this->googleLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-') ?: 0);
		}

		if (!in_array($lang, $this->googleLanguages)) {
			$url .= 'language=' . $lang . '&';
		}

		$tmp = (new CurlClient())->get($url . 'address=' . urlencode($location));
		if (!empty($tmp->error_message)) {
			Factory::getApplication()->enqueueMessage($tmp->error_message, 'warning');

			return;
		}

		if ($tmp->status != 'OK' || empty($tmp->results)) {
			return;
		}

		/** @var \stdClass $part */
		foreach ($tmp->results[0]->address_components as $part) {
			if (empty($part->types)) {
				continue;
			}
			switch ($part->types[0]) {
				case 'country':
					// Get the country by short code

					$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator', ['ignore_request' => true]);

					$loc = $model->getItem(['short_code' => strtoupper((string)$part->short_name)]);
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

	private function fillObjectFromOpenStreetMap(string $location, \stdClass $locObject): void
	{
		$url = Uri::getInstance(DPCalendarHelper::getComponentParameter('map_api_openstreetmap_geocode_url', 'https://nominatim.openstreetmap.org'));
		$url->setVar('format', 'json');
		$url->setVar('addressdetails', '1');
		$url->setVar('limit', '1');

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
		if (!in_array($lang, $this->nominatimLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-') ?: 0);
		}

		if (in_array($lang, $this->nominatimLanguages)) {
			$url->setVar('accept-language', $lang);
		}

		$tmp = (new CurlClient())->get(
			$url->toString(),
			null,
			null,
			['Accept: application/json, text/html']
		);
		if (empty($tmp->address) && (empty($tmp->data) || empty($tmp->data[0]->address))) {
			return;
		}

		if (!empty($tmp->data) && is_array($tmp->data)) {
			$tmp = $tmp->data[0];
		}

		if (!empty($tmp->address->country_code)) {
			// Get the country by short code
			$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator', ['ignore_request' => true]);

			$loc = $model->getItem(['short_code' => strtoupper((string)$tmp->address->country_code)]);
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

	private function fillObjectFromMapbox(string $location, \stdClass $locObject): void
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
		$lang = substr($lang, 0, strpos($lang, '-') ?: 0);
		$url .= '&language=' . $lang;

		$tmp = (new CurlClient())->get($url);
		if (empty($tmp->features)) {
			return;
		}

		$addr = $tmp->features[0];
		if (str_starts_with((string)$addr->id, 'address')) {
			$locObject->street = $addr->text;
		}
		if (!empty($addr->address)) {
			$locObject->number = $addr->address;
		}

		foreach ($addr->context as $c) {
			if (str_starts_with((string)$c->id, 'country') && !empty($c->short_code)) {
				// Get the country by short code
				$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator', ['ignore_request' => true]);

				$loc = $model->getItem(['short_code' => strtoupper((string)$c->short_code)]);
				if ($loc && $loc->id) {
					$locObject->country = $loc->id;
				}
			}
			if (str_starts_with((string)$c->id, 'region')) {
				$locObject->province = $c->text;
			}
			if (str_starts_with((string)$c->id, 'place')) {
				$locObject->city = $c->text;
			}
			if (str_starts_with((string)$c->id, 'postcode')) {
				$locObject->zip = $c->text;
			}
		}

		if (!empty($addr->geometry) && !empty($addr->geometry->coordinates)) {
			$locObject->latitude  = $addr->geometry->coordinates[1];
			$locObject->longitude = $addr->geometry->coordinates[0];
		}
	}
}
