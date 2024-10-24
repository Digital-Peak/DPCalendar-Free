<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

// Set the mime encoding
$this->document->setMimeEncoding('application/json');

$data = [];
foreach ($this->items as $event) {
	$displayData          = $this->displayData;
	$displayData['event'] = $event;
	$description          = trim((string)$this->layoutHelper->renderLayout('event.tooltip', $displayData));
	$description          = DPCalendarHelper::fixImageLinks($description);

	// Set up the locations
	$locations   = [];
	$resourceIds = [];

	// Add the calendar as resource in timeline view
	if ($this->getLayout() === 'timeline') {
		$resourceIds[] = $event->catid;
	}

	if (!empty($event->locations)) {
		foreach ($event->locations as $location) {
			$locations[] = [
				'location'  => $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo','Administrator')->format($location),
				'latitude'  => $location->latitude,
				'longitude' => $location->longitude
			];

			// Ignore locations as resources in timeline view
			if ($this->getLayout() === 'timeline') {
				continue;
			}

			if (!$event->rooms) {
				$resourceIds[] = $location->id;
			}

			foreach ($event->rooms as $room) {
				if (!str_contains((string)$room, $location->id . '-')) {
					continue;
				}

				$resourceIds[] = $room;
			}
		}
	}

	$fgcolor = null;

	// Inverse the color
	if ($this->params->get('adjust_fg_color', '2') == '1') {
		$fgcolor = $event->color;
		$rgb     = '';
		for ($x = 0; $x < 3; $x++) {
			$c   = 255 - hexdec(substr((string)$fgcolor, (2 * $x), 2));
			$c   = ($c < 0) ? 0 : dechex($c);
			$rgb .= (strlen($c) < 2) ? '0' . $c : $c;
		}
		$fgcolor = '#' . $rgb;
	}

	// Black or white computation
	if ($this->params->get('adjust_fg_color', '2') == '2') {
		$fgcolor = '#' . DPCalendarHelper::getOppositeBWColor($event->color);
	}

	// Format the dates depending on the all day flag
	$format = $event->all_day ? 'Y-m-d' : 'Y-m-d\TH:i:s';

	$calendar = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);

	// Add the data
	$eventData = [
		'id'              => $event->id,
		'title'           => $this->compactMode ? ' ' : htmlspecialchars_decode((string)$event->title),
		'start'           => $this->dateHelper->getDate($event->start_date, $event->all_day)->format($format, true),
		'editable'        => $calendar->canEdit() || ($calendar->canEditOwn() && $event->created_by == $this->user->id),
		'backgroundColor' => '#' . $event->color,
		'borderColor'     => '#' . $event->color,
		'textColor'       => $fgcolor,
		'allDay'          => (bool)$event->all_day,
		'description'     => $description,
		'location'        => $locations,
		'capacity'        => $this->compactMode ? 0 : $event->capacity,
		'capacity_used'   => $this->compactMode ? 0 : $event->capacity_used,
		'classNames'      => [
			'dp-event',
			'dp-event-' . $event->id,
			'dp-event-calendar-' . $event->catid,
			'dp-event_' . ($event->capacity == $event->capacity_used ? 'booked-out' : 'not-booked-out')
		]
	];

	if ($this->params->get('show_event_as_popup') != 2) {
		$eventData['url'] = $this->router->getEventRoute($event->id, $event->catid);
	}

	if ($event->state == 3 && !$this->compactMode) {
		$eventData['title'] = '[' . $this->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED') . '] ' . $eventData['title'];
	}
	if ($event->state == 0 && !$this->compactMode) {
		$eventData['title'] = '[' . $this->translate('JUNPUBLISHED') . '] ' . $eventData['title'];
	}

	if ($event->show_end_time || $event->all_day) {
		$eventData['end'] = $this->dateHelper->getDate($event->end_date, $event->all_day)->format($format, true);
	}

	$eventData['resourceIds'] = $resourceIds;
	$data[] = $eventData;
}

// Echo the data
ob_clean();
DPCalendarHelper::sendMessage('', false, ['events' => $data, 'location' => $this->state->get('filter.location')]);
