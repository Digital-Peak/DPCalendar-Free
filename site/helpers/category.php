<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
