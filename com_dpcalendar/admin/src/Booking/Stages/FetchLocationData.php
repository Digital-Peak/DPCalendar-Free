<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Model\CountryModel;
use DigitalPeak\Component\DPCalendar\Administrator\Model\GeoModel;
use Joomla\Utilities\ArrayHelper;
use League\Pipeline\StageInterface;

class FetchLocationData implements StageInterface
{
	public function __construct(private readonly CountryModel $model, private readonly GeoModel $geoModel)
	{
	}

	public function __invoke($payload)
	{
		// Convert the country id to string
		$locationData = ArrayHelper::toObject($payload->data);
		if (!empty($locationData->country)) {
			$country               = $this->model->getItem($locationData->country);
			$locationData->country = $country ? $country->short_code : '';
		}

		// Fetch the latitude/longitude
		$location = $this->geoModel->format([$locationData]);
		if ($location && empty($payload->data['latitude'])) {
			$payload->data['latitude']  = null;
			$payload->data['longitude'] = null;

			$location = $this->geoModel->getLocation($location, false);
			if ($location->latitude) {
				$payload->data['latitude']  = $location->latitude;
				$payload->data['longitude'] = $location->longitude;
			}
		}

		return $payload;
	}
}
