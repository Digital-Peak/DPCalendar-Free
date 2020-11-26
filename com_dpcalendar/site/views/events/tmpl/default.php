<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

// Set the mime encoding
JFactory::getDocument()->setMimeEncoding('application/json');

$data = [];
foreach ($this->items as $event) {
	$displayData          = $this->displayData;
	$displayData['event'] = $event;
	$description          = trim($this->layoutHelper->renderLayout('event.tooltip', $displayData));
	$description          = \DPCalendar\Helper\DPCalendarHelper::fixImageLinks($description);

	// Set up the locations
	$locations   = [];
	$resourceIds = [];
	if (!empty($event->locations)) {
		foreach ($event->locations as $location) {
			$locations[] = [
				'location'  => \DPCalendar\Helper\Location::format($location),
				'latitude'  => $location->latitude,
				'longitude' => $location->longitude
			];

			if (!$event->rooms) {
				$resourceIds[] = $location->id;
			}

			foreach ($event->rooms as $room) {
				if (strpos($room, $location->id . '-') === false) {
					continue;
				}
				$resourceIds[] = $room;
			}
		}
	}

	$fgcolor = null;

	// Inverse the color
	if ($this->params->get('adjust_fg_color', '0') == '1') {
		$fgcolor = $event->color;
		$rgb     = '';
		for ($x = 0; $x < 3; $x++) {
			$c   = 255 - hexdec(substr($fgcolor, (2 * $x), 2));
			$c   = ($c < 0) ? 0 : dechex($c);
			$rgb .= (strlen($c) < 2) ? '0' . $c : $c;
		}
		$fgcolor = '#' . $rgb;
	}

	// Black or white computation
	if ($this->params->get('adjust_fg_color', '0') == '2') {
		$fgcolor = '#' . \DPCalendar\Helper\DPCalendarHelper::getOppositeBWColor($event->color);
	}

	// Format the dates depending on the all day flag
	$format = $event->all_day ? 'Y-m-d' : 'Y-m-d\TH:i:s';

	$calendar = DPCalendarHelper::getCalendar($event->catid);

	// Add the data
	$eventData = [
		'id'              => $event->id,
		'title'           => $this->compactMode == 0 ? htmlspecialchars_decode($event->title) : utf8_encode(chr(160)),
		'start'           => DPCalendarHelper::getDate($event->start_date, $event->all_day)->format($format, true),
		'url'             => DPCalendarHelperRoute::getEventRoute($event->id, $event->catid),
		'editable'        => $calendar->canEdit || ($calendar->canEditOwn && $event->created_by == $this->user->id),
		'backgroundColor' => '#' . $event->color,
		'borderColor'     => '#' . $event->color,
		'textColor'       => $fgcolor,
		'allDay'          => (bool)$event->all_day,
		'description'     => $description,
		'location'        => $locations,
		'classNames'      => [
			'dp-event',
			'dp-event-' . $event->id,
			'dp-event-calendar-' . $event->catid,
			'dp-event_' . ($event->capacity == $event->capacity_used ? 'booked-out' : 'not-booked-out')
		]
	];

	if ($event->state == 3 && $this->compactMode == 0) {
		$eventData['title'] = '[' . $this->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED') . '] ' . $eventData['title'];
	}
	if ($event->state == 0 && $this->compactMode == 0) {
		$eventData['title'] = '[' . $this->translate('JUNPUBLISHED') . '] ' . $eventData['title'];
	}

	if ($event->show_end_time || $event->all_day) {
		$eventData['end'] = DPCalendarHelper::getDate($event->end_date, $event->all_day)->format($format, true);
	}

	if ($resourceIds) {
		$eventData['resourceIds'] = $resourceIds;
	}
	$data[] = $eventData;
}

$messages = JFactory::getApplication()->getMessageQueue();

// Build the sorted messages list
$lists = [];
if (is_array($messages) && count($messages)) {
	foreach ($messages as $message) {
		if (isset($message['type']) && isset($message['message'])) {
			$lists[$message['type']][] = $message['message'];
		}
	}
}

// Echo the data
ob_clean();
\DPCalendar\Helper\DPCalendarHelper::sendMessage(null, false, $data);

// Close the request
JFactory::getApplication()->close();
