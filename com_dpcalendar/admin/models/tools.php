<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Transifex;

JLoader::import('joomla.application.component.modellist');

class DPCalendarModelTools extends JModelLegacy
{

	public function getResourcesFromTransifex()
	{
		$resources = Transifex::getData('resources');

		$data = json_decode($resources['data']);
		usort($data, function ($r1, $r2) {
			return strcmp($r1->name, $r2->name);
		});

		return $data;
	}
}
