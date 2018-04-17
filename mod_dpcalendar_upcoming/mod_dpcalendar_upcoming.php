<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
	return;
}

// @deprecated Will be removed in the next major version
JLoader::import('components.com_dpcalendar.helpers.schema', JPATH_ADMINISTRATOR);

JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

$params->set('frontend_framework', $params->get('frontend_framework', DPCalendarHelper::getComponentParameter('frontend_framework', 'BS2')));
$params->set('icon_framework', $params->get('icon_framework', DPCalendarHelper::getComponentParameter('icon_framework', 'Joomla')));

JLoader::import('joomla.application.component.model');
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

$model = JModelLegacy::getInstance('Calendar', 'DPCalendarModel');
$model->getState();
$model->setState('filter.parentIds', $params->get('ids', array('root')));
$ids = array();
foreach ($model->getItems() as $calendar) {
	$ids[] = $calendar->id;
}

$startDate = trim($params->get('start_date', ''));
if ($startDate == 'start of day') {
	$startDate = DPCalendarHelper::getDate();
	$startDate->setTime(0, 0, 0);
} else {
	$startDate = DPCalendarHelper::getDate($startDate);
}

// Round to the last quater
$startDate->sub(new DateInterval("PT" . $startDate->format("s") . "S"));
$startDate->sub(new DateInterval("PT" . ($startDate->format("i") % 15) . "M"));

$endDate = trim($params->get('end_date', ''));
if ($endDate == 'same day') {
	$endDate = clone $startDate;
	$endDate->setTime(23, 59, 59);
} else if ($endDate) {
	$tmp = DPCalendarHelper::getDate($endDate);
	$tmp->sub(new DateInterval("PT" . $tmp->format("s") . "S"));
	$tmp->sub(new DateInterval("PT" . ($tmp->format("i") % 15) . "M"));
	$endDate = $tmp;
} else {
	$endDate = null;
}

$startDate = $startDate->format('U');

$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
$model->getState();
$model->setState('list.limit', $params->get('max_events', 5));
$model->setState('list.direction', $params->get('order', 'asc'));
$model->setState('category.id', $ids);
$model->setState('category.recursive', true);
$model->setState('filter.search', $params->get('filter', ''));
$model->setState('filter.ongoing', $params->get('ongoing', 0));
$model->setState('filter.expand', true);
$model->setState('filter.state', 1);
$model->setState('filter.language', JFactory::getLanguage());
$model->setState('filter.publish_date', true);
$model->setState('list.start-date', $startDate);
$model->setState('list.end-date', $endDate);
$model->setState('filter.my', $params->get('show_my_only', 0));
$model->setState('filter.featured', $params->get('filter_featured', 0));
$model->setState('filter.tags', $params->get('filter_tags', array()));
$model->setState('filter.locations', $params->get('filter_locations', array()));

$events = $model->getItems();

if (!$events && !$params->get('empty_text', 1)) {
	return;
}

// Sort the array by user date
usort($events, function ($e1, $e2) {
	$d1 = DPCalendarHelper::getDate($e1->start_date, $e1->all_day);
	$d2 = DPCalendarHelper::getDate($e2->start_date, $e2->all_day);

	return strcmp($d1->format('c', true), $d2->format('c', true));
});

JPluginHelper::importPlugin('content');
JPluginHelper::importPlugin('dpcalendar');

foreach ($events as $event) {
	$event->text = $event->description;
	JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_dpcalendar.event', &$event, &$event->params, 0));
	$event->description = $event->text;

	$event->realUrl = str_replace(
		array('?tmpl=component', 'tmpl=component'),
		'',
		DPCalendarHelperRoute::getEventRoute($event->id, $event->catid, false, true, $params->get('default_menu_item'))
	);
}

require JModuleHelper::getLayoutPath('mod_dpcalendar_upcoming', $params->get('layout', 'default'));
