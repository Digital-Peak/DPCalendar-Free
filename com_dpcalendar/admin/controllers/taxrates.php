<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerTaxrates extends JControllerAdmin
{
	protected $text_prefix = 'COM_DPCALENDAR_TAXRATE';

	public function getModel($name = 'Taxrate', $prefix = 'DPCalendarModel', $config = ['ignore_request' => true])
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
}
