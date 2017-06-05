<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
if (!class_exists('DPCalendarHelper')) {
	return;
}
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

$startDate = DPCalendarHelper::getDate(trim($params->get('start_date', '')));

// Round to the last quater
$startDate->sub(new DateInterval("PT" . $startDate->format("s") . "S"));
$startDate->sub(new DateInterval("PT" . ($startDate->format("i") % 15) . "M"));

$startDate = $startDate->format('U');

$endDate = trim($params->get('end_date', ''));
if ($endDate) {
	$tmp = DPCalendarHelper::getDate($endDate);
	$tmp->sub(new DateInterval("PT" . $tmp->format("s") . "S"));
	$tmp->sub(new DateInterval("PT" . ($tmp->format("i") % 15) . "M"));
	$endDate = $tmp->format('U');
} else {
	$endDate = null;
}

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

$events = $model->getItems();

JPluginHelper::importPlugin('content');
JPluginHelper::importPlugin('dpcalendar');

foreach ($events as $event) {
	$event->text = $event->description;
	JEventDispatcher::getInstance()->trigger('onContentPrepare', array('com_dpcalendar.event', &$event, &$event->params, 0));
	$event->description = $event->text;
}

require JModuleHelper::getLayoutPath('mod_dpcalendar_upcoming', $params->get('layout', 'default'));
