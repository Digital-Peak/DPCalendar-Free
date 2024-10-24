<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;

$this->document->setMimeEncoding('application/json');

$data = [];
foreach ($this->items as $event) {
	$displayData          = $this->displayData;
	$displayData['event'] = $event;
	$description          = trim((string) $this->layoutHelper->renderLayout('event.tooltip', $displayData));
	$description          = DPCalendarHelper::fixImageLinks($description);

	$locations = [];
	if (!empty($event->locations)) {
		foreach ($event->locations as $location) {
			$locations[] = [
				'id'        => $event->id,
				'location'  => $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo','Administrator')->format($location),
				'latitude'  => $location->latitude,
				'longitude' => $location->longitude
			];
		}
	}
	$data[] = [
		'id'          => $event->id,
		'title'       => htmlspecialchars_decode(($event->state == 3 ? '[' . $this->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED') . '] ' : '') . $event->title),
		'start'       => DPCalendarHelper::getDate($event->start_date, $event->all_day)->format('c', true),
		'end'         => DPCalendarHelper::getDate($event->end_date, $event->all_day)->format('c', true),
		'url'         => RouteHelper::getEventRoute($event->id, $event->catid),
		'editable'    => $this->getCurrentUser()->authorise('core.edit', 'com_dpcalendar.category.' . $event->catid),
		'color'       => '#' . $event->color,
		'allDay'      => (bool)$event->all_day,
		'description' => $description,
		'location'    => $locations
	];
}

// Echo the data
ob_clean();
DPCalendarHelper::sendMessage('', false, ['events' => $data, 'location' => $this->state->get('filter.location')]);
