<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
	return;
}

// Helpers
$document     = new \DPCalendar\HTML\Document\HtmlDocument();
$dateHelper   = new \DPCalendar\Helper\DateHelper();
$layoutHelper = new \DPCalendar\Helper\LayoutHelper();
$userHelper   = new \DPCalendar\Helper\UserHelper();
$router       = new \DPCalendar\Router\Router();
$translator   = new \DPCalendar\Translator\Translator();

$cParams      = clone JComponentHelper::getParams('com_dpcalendar');
$moduleParams = $cParams->merge($params);

// The display data
$displayData = [
	'document'     => $document,
	'layoutHelper' => $layoutHelper,
	'userHelper'   => $userHelper,
	'dateHelper'   => $dateHelper,
	'translator'   => $translator,
	'router'       => $router,
	'input'        => $app->input,
	'params'       => $moduleParams
];

JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
JLoader::import('joomla.application.component.model');
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

$model = JModelLegacy::getInstance('Calendar', 'DPCalendarModel');
$model->getState();
$model->setState('filter.parentIds', $moduleParams->get('ids', ['root']));
$ids = [];
foreach ($model->getItems() as $calendar) {
	$ids[] = $calendar->id;
}

$startDate = trim($moduleParams->get('start_date', ''));
if ($startDate == 'start of day') {
	$startDate = $dateHelper->getDate(null, true, 'UTC');
	$startDate->setTime(0, 0, 0);
} else {
	$startDate = $dateHelper->getDate($startDate);
}

// Round to the last quater
$startDate->sub(new DateInterval("PT" . $startDate->format("s") . "S"));
$startDate->sub(new DateInterval("PT" . ($startDate->format("i") % 15) . "M"));

$endDate = trim($moduleParams->get('end_date', ''));
if ($endDate == 'same day') {
	$endDate = clone $startDate;
	$endDate->setTime(23, 59, 59);
} else if ($endDate) {
	$tmp = $dateHelper->getDate($endDate);
	$tmp->sub(new DateInterval("PT" . $tmp->format("s") . "S"));
	$tmp->sub(new DateInterval("PT" . ($tmp->format("i") % 15) . "M"));
	$endDate = $tmp;
} else {
	$endDate = null;
}

$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
$model->getState();
$model->setState('list.limit', $moduleParams->get('max_events', 5));
$model->setState('list.direction', $moduleParams->get('order', 'asc'));
$model->setState('list.ordering', 'a.' . $moduleParams->get('sort', 'start_date'));
$model->setState('category.id', $ids);
$model->setState('category.recursive', true);
$model->setState('filter.search', $moduleParams->get('filter', ''));
$model->setState('filter.ongoing', $moduleParams->get('ongoing', 0));
$model->setState('filter.expand', $moduleParams->get('expand', 1));
$model->setState('filter.state', [1, 3]);
$model->setState('filter.language', JFactory::getLanguage());
$model->setState('filter.publish_date', true);
$model->setState('list.start-date', $startDate);
$model->setState('list.end-date', $endDate);
$model->setState('filter.my', $moduleParams->get('show_my_only', 0));
$model->setState('filter.featured', $moduleParams->get('filter_featured', 0));
$model->setState('filter.tags', $moduleParams->get('filter_tags', []));
$model->setState('filter.locations', $moduleParams->get('filter_locations', []));

$events = $model->getItems();

if (!$events && !$moduleParams->get('empty_text', 1)) {
	return;
}

if ($moduleParams->get('sort', 'start_date') == 'start_date') {
	// Sort the array by user date
	usort($events, function ($e1, $e2) use ($dateHelper, $moduleParams) {
		$d1 = $dateHelper->getDate($e1->start_date, $e1->all_day);
		$d2 = $dateHelper->getDate($e2->start_date, $e2->all_day);

		if ($moduleParams->get('order', 'asc') !== 'asc') {
			$tmp = $d1;
			$d1  = $d2;
			$d2  = $tmp;
		}

		return strcmp($d1->format('c', true), $d2->format('c', true));
	});
}

JPluginHelper::importPlugin('content');
JPluginHelper::importPlugin('dpcalendar');
$now = $dateHelper->getDate();

// The grouping option
$grouping = $moduleParams->get('output_grouping', '');

// The last computed heading
$lastHeading = '';

// The grouped events
$groupedEvents = [];
foreach ($events as $event) {
	$startDate    = $dateHelper->getDate($event->start_date, $event->all_day);
	$groupHeading = $grouping ? $startDate->format($grouping, true) : false;

	if ($groupHeading && $groupHeading != $lastHeading) {
		$lastHeading = $groupHeading;
	}

	if (!array_key_exists($lastHeading, $groupedEvents)) {
		$groupedEvents[$lastHeading] = [];
	}

	$groupedEvents[$lastHeading][] = $event;

	$event->text = $event->description;
	$app->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$event->params, 0]);
	$event->description = $event->text;

	$event->realUrl = str_replace(
		['?tmpl=component', 'tmpl=component'],
		'',
		$router->getEventRoute($event->id, $event->catid, false, true, $moduleParams->get('default_menu_item'))
	);

	$desc = $params->get('description_length') === '0' ? '' : JHTML::_('content.prepare', $event->description);
	if ($desc && $params->get('description_length') > 0) {
		$descTruncated = JHtmlString::truncateComplex($desc, $params->get('description_length', null));

		// Move the dots inside the last tag
		if (\DPCalendar\Helper\DPCalendarHelper::endsWith($descTruncated, '...') && $pos = strrpos($descTruncated, '</')) {
			$descTruncated = trim(substr_replace($descTruncated, '...</', $pos, 2), '.');
		}

		if ($desc != $descTruncated) {
			$event->alternative_readmore = JText::_('MOD_DPCALENDAR_UPCOMING_READ_MORE');

			$desc = $layoutHelper->renderLayout(
				'joomla.content.readmore',
				[
					'item'   => $event,
					'params' => new \Joomla\Registry\Registry(['access-view' => true]),
					'link'   => $router->getEventRoute($event->id, $event->catid)
				]
			);

			$desc = $descTruncated . $desc;
		}
	}
	$event->truncatedDescription = $desc;

	// Determine if the event is running
	$date = $dateHelper->getDate($event->start_date);
	if (!empty($event->series_min_start_date) && !$moduleParams->get('expand', 1)) {
		$date = $dateHelper->getDate($event->series_min_start_date);
	}
	$event->ongoing_start_date = $date < $now ? $date : null;

	if ($moduleParams->get('show_display_events')) {
		$event->displayEvent                    = new stdClass();
		$results                                = $app->triggerEvent(
			'onContentAfterTitle',
			['com_dpcalendar.event', &$event, &$event->params, 0]
		);
		$event->displayEvent->afterDisplayTitle = trim(implode("\n", $results));

		$results                                   = $app->triggerEvent(
			'onContentBeforeDisplay',
			['com_dpcalendar.event', &$event, &$event->params, 0]
		);
		$event->displayEvent->beforeDisplayContent = trim(implode("\n", $results));

		$results                                  = $app->triggerEvent(
			'onContentAfterDisplay',
			['com_dpcalendar.event', &$event, &$event->params, 0]
		);
		$event->displayEvent->afterDisplayContent = trim(implode("\n", $results));
	}
}

$return = $app->input->getInt('Itemid', null);
if (!empty($return)) {
	$return = $router->route('index.php?Itemid=' . $return);
}

require JModuleHelper::getLayoutPath('mod_dpcalendar_upcoming', $moduleParams->get('layout', 'default'));
