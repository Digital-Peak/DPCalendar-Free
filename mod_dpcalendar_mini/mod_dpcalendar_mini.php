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

// The display data
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
JFactory::getLanguage()->load('com_dpcalendar', JPATH_SITE . '/components/com_dpcalendar');

JLoader::import('joomla.application.component.model');
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

$model = JModelLegacy::getInstance('Calendar', 'DPCalendarModel');
$model->getState();
$model->setState('filter.parentIds', $params->get('ids', array('root')));
$ids = array();
foreach ($model->getItems() as $calendar) {
	$ids[] = $calendar->id;
}

$resources = [];
if ($params->get('calendar_filter_locations') && $params->get('calendar_resource_views') && !\DPCalendar\Helper\DPCalendarHelper::isFree()) {
	// Load the model
	JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
	$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', array('ignore_request' => true));
	$model->getState();
	$model->setState('list.limit', 10000);
	$model->setState('filter.search', 'ids:' . implode($params->get('calendar_filter_locations'), ','));

	// Add the locations
	foreach ($model->getItems() as $location) {
		$rooms = array();
		if ($location->rooms) {
			foreach ($location->rooms as $room) {
				$rooms[] = (object)array('id' => $location->id . '-' . $room->id, 'title' => $room->title);
			}
		}

		$resource = (object)array('id' => $location->id, 'title' => $location->title);

		if ($rooms) {
			$resource->children = $rooms;
		}
		$resources[] = $resource;
	}
}

require JModuleHelper::getLayoutPath('mod_dpcalendar_mini', $params->get('layout', 'default'));
