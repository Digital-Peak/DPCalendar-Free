<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Transifex;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DPCalendarModelTools extends BaseDatabaseModel
{
	public function getResourcesFromTransifex()
	{
		$resources = Transifex::getResources();
		if (!$resources) {
			return;
		}

		$data = [];
		foreach ($resources as $resource) {
			$data[] = $resource->attributes;
		}

		usort($data, fn ($r1, $r2) => strcmp($r1->name, $r2->name));

		return $data;
	}
}
