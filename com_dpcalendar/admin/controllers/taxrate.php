<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerTaxrate extends JControllerForm
{
	protected $text_prefix = 'COM_DPCALENDAR_TAXRATE';

	public function save($key = null, $urlVar = 'r_id')
	{
		return parent::save($key, $urlVar);
	}

	public function edit($key = null, $urlVar = 'r_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'r_id')
	{
		return parent::cancel($key);
	}
}
