<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('controllers.locations', JPATH_COMPONENT_ADMINISTRATOR);

class DPCalendarControllerLocationForms extends DPCalendarControllerLocations
{

	public function __construct ($config = array())
	{
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models/');
		JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables/');

		parent::__construct($config);
	}
}
