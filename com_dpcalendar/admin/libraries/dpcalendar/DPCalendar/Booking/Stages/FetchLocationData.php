<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\Location;
use Joomla\Utilities\ArrayHelper;
use League\Pipeline\StageInterface;

class FetchLocationData implements StageInterface
{
	private \DPCalendarModelCountry $model;

	public function __construct(\DPCalendarModelCountry $model)
	{
		$this->model = $model;
	}

	public function __invoke($payload)
	{
		// Convert the country id to string
		$locationData = ArrayHelper::toObject($payload->data);
		if (!empty($locationData->country)) {
			$locationData->country = $this->model->getItem($locationData->country)->short_code;
		}

		// Fetch the latitude/longitude
		$location = Location::format([$locationData]);
		if ($location && empty($payload->data['latitude'])) {
			$payload->data['latitude']  = null;
			$payload->data['longitude'] = null;

			$location = Location::get($location, false);
			if ($location->latitude) {
				$payload->data['latitude']  = $location->latitude;
				$payload->data['longitude'] = $location->longitude;
			}
		}

		return $payload;
	}
}
