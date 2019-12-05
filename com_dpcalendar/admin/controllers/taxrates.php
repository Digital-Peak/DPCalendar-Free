<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerTaxrates extends JControllerAdmin
{
	protected $text_prefix = 'COM_DPCALENDAR_TAXRATE';

	public function getModel ($name = 'Taxrate', $prefix = 'DPCalendarModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
}
