<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewCpanel extends \DPCalendar\View\BaseView
{
	protected $title = 'COM_DPCALENDAR_VIEW_CPANEL';

	protected function init()
	{
		$this->getModel()->refreshUpdateSite();

		$model = $this->getModel();

		$this->upcomingEvents     = $model->getEvents(\DPCalendar\Helper\DPCalendarHelper::getDate());
		$this->newEvents          = $model->getEvents(0, 'a.created', 'desc');
		$this->lastModifiedEvents = $model->getEvents(0, 'a.modified', 'desc');

		$this->totalEvents       = $model->getTotalEvents();
		$this->totalBookings     = $model->getTotalBookings();
		$this->calendars         = $model->getCalendars();
		$this->calendarsInternal = [];
		$this->calendarsExternal = [];

		foreach ($this->calendars as $calendar) {
			if ($calendar->external) {
				$this->calendarsExternal[] = $calendar;
			} else {
				$this->calendarsInternal[] = $calendar;
			}
		}

		parent::init();
	}
}
