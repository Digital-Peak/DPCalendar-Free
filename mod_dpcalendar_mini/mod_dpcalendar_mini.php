<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

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

$app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
$app->getLanguage()->load('com_dpcalendar', JPATH_SITE . '/components/com_dpcalendar');

JLoader::import('joomla.application.component.model');
BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

$model = BaseDatabaseModel::getInstance('Calendar', 'DPCalendarModel');
$model->getState();
$model->setState('filter.parentIds', $params->get('ids', ['root']));
$ids = [];
foreach ($model->getItems() as $calendar) {
	$ids[] = $calendar->id;
}

if (!$ids) {
	return;
}

$resources = [];
if ($params->get('calendar_filter_locations') && $params->get('calendar_resource_views') && !DPCalendarHelper::isFree()) {
	// Load the model
	BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
	$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
	$model->getState();
	$model->setState('list.limit', 10000);
	$model->setState('filter.search', 'ids:' . implode(',', $params->get('calendar_filter_locations')));

	// Add the locations
	foreach ($model->getItems() as $location) {
		$rooms = [];
		if ($location->rooms) {
			foreach ($location->rooms as $room) {
				$rooms[] = (object)['id' => $location->id . '-' . $room->id, 'title' => $room->title];
			}
		}

		$resource = (object)['id' => $location->id, 'title' => $location->title];

		if ($rooms) {
			$resource->children = $rooms;
		}
		$resources[] = $resource;
	}
}

require ModuleHelper::getLayoutPath('mod_dpcalendar_mini', $params->get('layout', 'default'));
