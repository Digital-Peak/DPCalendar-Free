<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarModelTools extends \Joomla\CMS\MVC\Model\BaseDatabaseModel
{
	public function getResourcesFromTransifex()
	{
		$data = \DPCalendar\Helper\Transifex::getData('resources')->data;
		usort($data, function ($r1, $r2) {
			return strcmp($r1->name, $r2->name);
		});

		return $data;
	}
}
