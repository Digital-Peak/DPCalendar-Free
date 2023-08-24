<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Factory;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewCpanel extends BaseView
{
	protected $title = 'COM_DPCALENDAR_VIEW_CPANEL';

	protected function init()
	{
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

		$geoDBDirectory = Factory::getApplication()->get('tmp_path') . '/DPCalendar-Geodb';

		// Don't update when the file was fetched 10 days ago
		$files                  = is_dir($geoDBDirectory) ? scandir($geoDBDirectory) : [];
		$this->needsGeoDBUpdate = ((is_countable($files) ? count($files) : 0) <= 2 || (time() - filemtime($geoDBDirectory . '/' . $files[2]) > (60 * 60 * 24 * 10))) && !DPCalendarHelper::isFree();

		// If no taxes are available no geo DB update is needed
		if (!$model->getTotalTaxRates()) {
			$this->needsGeoDBUpdate = false;
		}

		parent::init();
	}
}
