<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerCountry extends JControllerForm
{
	protected $text_prefix = 'COM_DPCALENDAR_COUNTRY    ';

	public function save($key = null, $urlVar = 'c_id')
	{
		return parent::save($key, $urlVar);
	}

	public function edit($key = null, $urlVar = 'c_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'c_id')
	{
		return parent::cancel($key);
	}
}
