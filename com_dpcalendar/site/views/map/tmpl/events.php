<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;

$this->document->setMimeEncoding('application/json');

$data = [];
foreach ($this->items as $event) {
	$displayData          = $this->displayData;
	$displayData['event'] = $event;
	$description          = trim($this->layoutHelper->renderLayout('event.tooltip', $displayData));
	$description          = \DPCalendar\Helper\DPCalendarHelper::fixImageLinks($description);

	$locations = [];
	if (!empty($event->locations)) {
		foreach ($event->locations as $location) {
			$locations[] = [
				'id'        => $event->id,
				'location'  => \DPCalendar\Helper\Location::format($location),
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
		'url'         => DPCalendarHelperRoute::getEventRoute($event->id, $event->catid),
		'editable'    => Factory::getUser()->authorise('core.edit', 'com_dpcalendar.category.' . $event->catid),
		'color'       => '#' . $event->color,
		'allDay'      => (bool)$event->all_day,
		'description' => $description,
		'location'    => $locations
	];
}

$messages = $this->app->getMessageQueue();

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
echo json_encode(['data' => ['events' => $data, 'location' => $this->state->get('filter.location')], 'messages' => $lists]);

// Close the request
$this->app->close();
