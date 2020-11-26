<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
$model->setState('filter.parentIds', $params->get('ids', ['root']));
$ids = [];
foreach ($model->getItems() as $calendar) {
	$ids[] = $calendar->id;
}

if (!$ids) {
	return;
}

$startDate = trim($params->get('start_date', ''));
if ($startDate == 'start of day') {
	$startDate = $dateHelper->getDate(null, true, 'UTC');
	$startDate->setTime(0, 0, 0);
} else {
	$startDate = $dateHelper->getDate($startDate);
}

// Round to the last quater
$startDate->sub(new DateInterval("PT" . $startDate->format("s") . "S"));
$startDate->sub(new DateInterval("PT" . ($startDate->format("i") % 15) . "M"));

$endDate = clone $startDate;
$endDate->modify('+1 year');

$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
$model->getState();
$model->setState('list.limit', $params->get('max_events', 1));
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
$model->setState('filter.tags', $params->get('filter_tags', []));
$model->setState('filter.locations', $params->get('filter_locations', []));
$model->setState('filter.my', $params->get('show_my_only', 0));

$events = $model->getItems();
foreach ($events as $event) {
	$event->truncatedDescription = '';
	if ($params->get('description_length') > 0 || $params->get('description_length') === null) {
		$event->truncatedDescription = JHtml::_('string.truncate', $event->description, $params->get('description_length'));
		$event->truncatedDescription = JHTML::_('content.prepare', $event->truncatedDescription);
		$event->truncatedDescription = \DPCalendar\Helper\DPCalendarHelper::fixImageLinks($event->truncatedDescription);
	}
}

require JModuleHelper::getLayoutPath('mod_dpcalendar_counter', $params->get('layout', 'default'));
