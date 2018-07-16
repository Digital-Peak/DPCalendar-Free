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

$document     = new \DPCalendar\HTML\Document\HtmlDocument();
$dateHelper   = new \DPCalendar\Helper\DateHelper();
$layoutHelper = new \DPCalendar\Helper\LayoutHelper();
$userHelper   = new \DPCalendar\Helper\UserHelper();
$router       = new \DPCalendar\Router\Router();
$translator   = new \DPCalendar\Translator\Translator();
$input        = $app->input;

// The display data with some common helpers for the JLayouts
$displayData = [
	'document'     => $document,
	'layoutHelper' => $layoutHelper,
	'userHelper'   => $userHelper,
	'dateHelper'   => $dateHelper,
	'translator'   => $translator,
	'router'       => $router,
	'params'       => $params
];

JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

JLoader::import('joomla.application.component.model');
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

$model = JModelLegacy::getInstance('Calendar', 'DPCalendarModel');
$model->getState();
$model->setState('filter.parentIds', $params->get('ids', array('root')));
$ids = array();
foreach ($model->getItems() as $calendar) {
	$ids[] = $calendar->id;
}

$startDate = DPCalendarHelper::getDate();

// Round to the last quater
$startDate->sub(new DateInterval("PT" . $startDate->format("s") . "S"));
$startDate->sub(new DateInterval("PT" . ($startDate->format("i") % 15) . "M"));

$endDate = clone $startDate;
$endDate->modify('+1 year');

$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
$model->getState();
$model->setState('list.limit', 1);
$model->setState('list.direction', $params->get('order', 'asc'));
$model->setState('category.id', $ids);
$model->setState('category.recursive', true);
$model->setState('filter.search', $params->get('filter', ''));
$model->setState('filter.expand', true);
$model->setState('filter.state', 1);
$model->setState('filter.language', JFactory::getLanguage());
$model->setState('filter.publish_date', true);
$model->setState('list.start-date', $startDate);
$model->setState('list.end-date', $endDate);
$model->setState('filter.tags', $params->get('filter_tags', array()));
$model->setState('filter.locations', $params->get('filter_locations', array()));
$model->setState('filter.my', $params->get('show_my_only', 0));

$event = $model->getItems();
if (empty($event)) {
	$event = null;
} else {
	$event = reset($event);
}

$truncatedDescription = '';
if ($event && ($params->get('description_length') > 0 || $params->get('description_length') === null)) {
	$truncatedDescription = JHtml::_('string.truncate', $event->description, $params->get('description_length'));
	$truncatedDescription = JHTML::_('content.prepare', $truncatedDescription);
	$truncatedDescription = \DPCalendar\Helper\DPCalendarHelper::fixImageLinks($truncatedDescription);
}

require JModuleHelper::getLayoutPath('mod_dpcalendar_counter', $params->get('layout', 'default'));
