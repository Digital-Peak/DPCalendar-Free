<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Helper;

\JTable::addIncludePath(JPATH_ADMINISTRATOR . 'components/com_dpcalendar/tables');

class Location
{

	private static $locationCache = null;

	private static $googleLanguages = array(
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
	);

	public static function getMapLink($locations)
	{
		if (!$locations) {
			return '';
		}

		return 'http://maps.google.com/?q=' . urlencode(self::format($locations));
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

		$lang = \DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, self::$googleLanguages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}
		if (!in_array($lang, self::$googleLanguages)) {
			$lang = '';
		} else {
			$lang = '&language=' . $lang;
		}
		$url = 'https://maps.google.com/maps/api/geocode/json?';

		if ($key = trim(\DPCalendarHelper::getComponentParameter('map_api_google_key'))) {
			$url .= 'key=' . $key . '&';
		}
		$url .= 'address=' . urlencode($location) . $lang;

		$content = '';
		if ($key != '-1') {
			$content = \DPCalendarHelper::fetchContent($url);
		}
		if ($content instanceof \Exception) {
			\JFactory::getApplication()->enqueueMessage((string)$content->getMessage(), 'warning');
		}

		if (!$title) {
			$title = $location;
		}

		if (!isset($locObject)) {
			$locObject            = \JTable::getInstance('Location', 'DPCalendarTable');
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
		} else {
			$tmp       = $locObject;
			$locObject = \JTable::getInstance('Location', 'DPCalendarTable');
			$locObject->bind((array)$tmp);
		}
		if (!empty($content) && !($content instanceof \Exception)) {
			$tmp = json_decode($content);

			if ($tmp) {
				if ($tmp->status == 'OK') {
					if (!empty($tmp->results)) {
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
				} else if (isset($tmp->error_message)) {
					\JFactory::getApplication()->enqueueMessage($tmp->error_message, 'warning');
				}
			}
		}

		if ($fill) {
			try {
				if (!$locObject->store()) {
					\JFactory::getApplication()->enqueueMessage($locObject->getError(), 'warning');
				}
			} catch (\Exception $e) {
				\JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			}

			self::$locationCache = \JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
		}

		return $locObject;
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
}
