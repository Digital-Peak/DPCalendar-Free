<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Helper;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Model\GeoModel;
use Joomla\CMS\Factory;

class Location
{
	public static function getDirectionsLink(\stdClass $location, int $zoom = 6): string
	{
		return self::getModel()->getDirectionsLink($location, $zoom);
	}

	public static function getMapLink(\stdClass $location, int $zoom = 6): string
	{
		return self::getModel()->getMapLink($location, $zoom);
	}

	public static function format(array|\stdClass $locations): string
	{
		return self::getModel()->format($locations);
	}

	public static function get(string $location, bool $fill = true, ?string $title = null): \stdClass
	{
		return self::getModel()->getLocation($location, $fill, $title);
	}

	public static function search(string $address): array
	{
		return self::getModel()->search($address);
	}

	public static function getLocations(array $locationIds): array
	{
		return self::getModel()->getLocations($locationIds);
	}

	public static function within(\stdClass $location, float $latitude, float $longitude, float $radius): bool
	{
		return self::getModel()->within($location, $latitude, $longitude, $radius);
	}

	public static function getColor(\stdClass $location): string
	{
		return self::getModel()->getColor($location);
	}

	public static function getCountryForIp(): ?\stdClass
	{
		return self::getModel()->getCountryForIp();
	}

	private static function getModel(): GeoModel
	{
		return Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator');
	}
}
