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

$tmp = [];
foreach ($this->items as $event) {
	$start = DPCalendarHelper::getDate($event->start_date, $event->all_day == 1);
	$start->setTime(0,0,0);
	$end   = DPCalendarHelper::getDate($event->end_date, $event->all_day == 1);
	$end->setTime(0,0,0);

	do {
		$date = $start->format('Y-m-d', true);
		if (!array_key_exists($date, $tmp)) {
			$tmp[$date] = [];
		}
		$tmp[$date][] = $event;
		$start->modify('+1 day');
	} while ($start <= $end);
}

$data = [];
foreach ($tmp as $date => $events) {
	$linkIDs = [];
	$itemId  = '';
	foreach ($events as $event) {
		$linkIDs[$event->catid] = $event->catid;
		if ($itemId != null) {
			continue;
		}
		$needles             = ['event' => [(int)$event->id]];
		$needles['calendar'] = [(int)$event->catid];
		$needles['list']     = [(int)$event->catid];
		if (($item = RouteHelper::findItem($needles)) === '') {
			continue;
		}
		if (($item = RouteHelper::findItem($needles)) === '0') {
			continue;
		}
		$itemId = '&Itemid=' . $item;
	}

	$parts = explode('-', $date);
	$day   = $parts[2];
	$month = $parts[1];
	$year  = $parts[0];
	$url   = $this->router->route(
		'index.php?option=com_dpcalendar&view=calendar&id=0&ids=' . implode(',', $linkIDs) . $itemId .
		'#year=' . $year . '&month=' . $month . '&day=' . $day . '&view=' . $this->app->getInput()->get('openview', 'day')
	);

	$eventData = [];
	$description = '<ul class="dp-events-list dp-list">';
	foreach ($events as $event) {
		$start  = DPCalendarHelper::getDate($event->start_date, $event->all_day == 1,);
		$end    = DPCalendarHelper::getDate($event->end_date, $event->all_day == 1);

		// Format the dates depending on the all day flag
		$format      = $event->all_day ? 'Y-m-d' : 'Y-m-d\TH:i:s';
		$eventData[] = [
			'id'         => $event->id,
			'start_date' => $start->format($format, true),
			'end_date'   => $end->format($format, true),
			'all_day'    => $event->all_day
		];
		$description .= '<li>' . htmlspecialchars((string) $event->title) . '</li>';
	}
	$description .= '</ul>';

	$data[] = [
		'id'              => $date,
		'title'           => ' ',
		'start'           => DPCalendarHelper::getDate($date)->format('Y-m-d'),
		'end'             => DPCalendarHelper::getDate($date)->format('Y-m-d'),
		'url'             => $url,
		'allDay'          => true,
		'description'     => $this->app->getInput()->get('desc', 1) ? $description : '',
		'view_class'      => 'dp-event-compact',
		'backgroundColor' => $this->params->get('event_color', '#135CAE'),
		'borderColor'     => $this->params->get('event_color', '#135CAE'),
		'textColor'       => '#' . DPCalendarHelper::getOppositeBWColor($this->params->get('event_color', '#135CAE')),
		'display'         => 'background',
		'eventData'       => $eventData
	];
}

ob_clean();
DPCalendarHelper::sendMessage('', false, ['events' => $data]);

$this->app->close();
