<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.helper');
JLoader::import('joomla.application.categories');

class DPCalendarCategories extends JCategories
{

	public function __construct($options = [])
	{
		$options['table'] = '#__dpcalendar_events';
		$options['extension'] = 'com_dpcalendar';
		$options['countItems'] = false;
		parent::__construct($options);
	}
}
