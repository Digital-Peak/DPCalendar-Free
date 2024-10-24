<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Cpanel;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\CpanelModel;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;

class HtmlView extends BaseView
{
	/** @var array */
	protected $upcomingEvents;

	/** @var array */
	protected $newEvents;

	/** @var array */
	protected $lastModifiedEvents;

	/** @var int */
	protected $totalEvents;

	/** @var array */
	protected $totalBookings;

	/** @var array */
	protected $calendars;

	/** @var array */
	protected $calendarsInternal;

	/** @var array */
	protected $extensionsWithDifferentVersion;

	/** @var array */
	protected $calendarsExternal;

	/** @var bool */
	protected $needsGeoDBUpdate;

	/** @var string */
	protected $title = 'COM_DPCALENDAR_VIEW_CPANEL';

	protected function init(): void
	{
		/** @var CpanelModel $model */
		$model = $this->getModel();

		$this->upcomingEvents     = $model->getEvents(DPCalendarHelper::getDate());
		$this->newEvents          = $model->getEvents('0', 'a.created', 'desc');
		$this->lastModifiedEvents = $model->getEvents('0', 'a.modified', 'desc');

		$this->totalEvents                    = $model->getTotalEvents();
		$this->totalBookings                  = $model->getTotalBookings();
		$this->calendars                      = $model->getCalendars();
		$this->extensionsWithDifferentVersion = $model->getExtensionsWithDifferentVersion($this->input->getString('DPCALENDAR_VERSION', ''));
		$this->calendarsInternal              = [];
		$this->calendarsExternal              = [];

		foreach ($this->calendars as $calendar) {
			if ($calendar instanceof ExternalCalendarInterface) {
				$this->calendarsExternal[] = $calendar;
			} else {
				$this->calendarsInternal[] = $calendar;
			}
		}

		$geoDBDirectory = JPATH_CACHE . '/com_dpcalendar-geodb';

		// Don't update when the file was fetched 10 days ago
		$files                  = is_dir($geoDBDirectory) ? (scandir($geoDBDirectory) ?: []) : [];
		$this->needsGeoDBUpdate = (\count($files) <= 2 || (time() - filemtime($geoDBDirectory . '/' . $files[2]) > (60 * 60 * 24 * 10))) && !DPCalendarHelper::isFree();

		// If no taxes are available no geo DB update is needed
		if (!$model->getTotalTaxRates()) {
			$this->needsGeoDBUpdate = false;
		}

		parent::init();
	}
}
