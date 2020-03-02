<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JFactory::getDocument()->setMimeEncoding('application/json');

$tmp = [];
foreach ($this->items as $event) {
	$start = DPCalendarHelper::getDate($event->start_date, $event->all_day == 1);
	$end   = DPCalendarHelper::getDate($event->end_date, $event->all_day == 1);

	do {
		$date = $start->format('Y-m-d', true);
		if (!key_exists($date, $tmp)) {
			$tmp[$date] = [];
		}
		$tmp[$date][] = $event;
		$start->modify("+1 day");
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

		if ($item = DPCalendarHelperRoute::findItem($needles)) {
			$itemId = '&Itemid=' . $item;
		}
	}

	$parts = explode('-', $date);
	$day   = $parts[2];
	$month = $parts[1];
	$year  = $parts[0];
	$url   = JRoute::_(
		'index.php?option=com_dpcalendar&view=calendar&id=0&ids=' . implode(',', $linkIDs) . $itemId .
		'#year=' . $year . '&month=' . $month . '&day=' . $day . '&view=' . JFactory::getApplication()->input->get('openview', 'agendaDay')
	);

	$description = '<ul class="dp-events-list">';
	foreach ($events as $event) {
		$description .= '<li>' . htmlspecialchars($event->title) . '</li>';
	}
	$description .= '</ul>';

	$data[] = [
		'id'          => $date,
		'title'       => utf8_encode(chr(160)), // Space only works in IE, empty only in Chrome
		'start'       => DPCalendarHelper::getDate($date)->format('Y-m-d'),
		'end'         => DPCalendarHelper::getDate($date)->format('Y-m-d'),
		'url'         => $url,
		'allDay'      => true,
		'description' => $description,
		'view_class'  => 'dp-event-compact',
		'rendering'   => 'background'
	];
}

ob_clean();
\DPCalendar\Helper\DPCalendarHelper::sendMessage(null, false, $data);

JFactory::getApplication()->close();
