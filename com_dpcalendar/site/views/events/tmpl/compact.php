<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFactory::getDocument()->setMimeEncoding('application/json');

$tmp = array();
foreach ($this->items as $event) {
	$start = DPCalendarHelper::getDate($event->start_date, $event->all_day == 1);
	$end   = DPCalendarHelper::getDate($event->end_date, $event->all_day == 1);

	do {
		$date = $start->format('Y-m-d', true);
		if (!key_exists($date, $tmp)) {
			$tmp[$date] = array();
		}
		$tmp[$date][] = $event;
		$start->modify("+1 day");
	} while ($start <= $end);
}

$data = array();
foreach ($tmp as $date => $events) {
	$linkIDs = array();
	$itemId  = '';
	foreach ($events as $event) {
		$linkIDs[$event->catid] = $event->catid;
		if ($itemId != null) {
			continue;
		}
		$needles             = array('event' => array((int)$event->id));
		$needles['calendar'] = array((int)$event->catid);
		$needles['list']     = array((int)$event->catid);

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

	$description = '<ul>';
	foreach ($events as $event) {
		$description .= '<li>' . htmlspecialchars($event->title) . '</li>';
	}
	$description .= '</ul>';

	$data[] = array(
		'id'          => $date,
		'title'       => utf8_encode(chr(160)), // Space only works in IE, empty only in Chrome
		'start'       => DPCalendarHelper::getDate($date)->format('Y-m-d'),
		'end'         => DPCalendarHelper::getDate($date)->format('Y-m-d'),
		'url'         => $url,
		'allDay'      => true,
		'description' => $description,
		'view_class'  => 'dp-event-compact'
	);
}

$messages = JFactory::getApplication()->getMessageQueue();

// Build the sorted messages list
$lists = array();
if (is_array($messages) && count($messages)) {
	foreach ($messages as $message) {
		if (isset($message['type']) && isset($message['message'])) {
			$lists[$message['type']][] = $message['message'];
		}
	}
}

ob_clean();
echo json_encode(array(array('data' => $data, 'messages' => $lists)));

JFactory::getApplication()->close();
