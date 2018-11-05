<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\Location;
use Joomla\Utilities\ArrayHelper;
use League\Pipeline\StageInterface;

class FetchLocationData implements StageInterface
{
	public function __invoke($payload)
	{
		// Fetch the latitude/longitude
		$location = Location::format(array(ArrayHelper::toObject($payload->data)));
		if ($location && (!isset($data['longitude']) || !$payload->data['longitude'])) {
			$data['latitude']  = null;
			$data['longitude'] = null;
			$location          = Location::get($location, false);
			if ($location->latitude) {
				$data['latitude']  = $location->latitude;
				$data['longitude'] = $location->longitude;
			}
		}

		return $payload;
	}
}
