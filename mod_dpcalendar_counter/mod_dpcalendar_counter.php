<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
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

$app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

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

$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
$model->getState();
$model->setState('list.limit', $params->get('max_events', 1));
$model->setState('list.direction', $params->get('order', 'asc'));
$model->setState('category.id', $ids);
$model->setState('category.recursive', true);
$model->setState('filter.search', $params->get('filter', ''));
$model->setState('filter.expand', true);
$model->setState('filter.state', 1);
$model->setState('filter.language', $app->getLanguage()->getTag());
$model->setState('filter.publish_date', true);
$model->setState('list.start-date', $startDate);
$model->setState('list.end-date', $endDate);
$model->setState('filter.tags', $params->get('filter_tags', []));
$model->setState('filter.locations', $params->get('filter_locations', []));
$model->setState('filter.author', $params->get('filter_author', 0));

$events = $model->getItems();
foreach ($events as $event) {
	$event->truncatedDescription = $event->introText ?: '';
	if (!$event->introText && ($params->get('description_length') > 0 || $params->get('description_length') === null)) {
		$event->truncatedDescription = HTMLHelper::_('string.truncate', $event->description ?: '', $params->get('description_length'));
		$event->truncatedDescription = HTMLHelper::_('content.prepare', $event->truncatedDescription);
		$event->truncatedDescription = DPCalendarHelper::fixImageLinks($event->truncatedDescription);
	}
}

require ModuleHelper::getLayoutPath('mod_dpcalendar_counter', $params->get('layout', 'default'));
