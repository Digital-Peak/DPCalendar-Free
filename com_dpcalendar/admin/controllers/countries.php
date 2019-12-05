<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerCountries extends JControllerAdmin
{
	protected $text_prefix = 'COM_DPCALENDAR_COUNTRY';

	public function getModel ($name = 'Country', $prefix = 'DPCalendarModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}
