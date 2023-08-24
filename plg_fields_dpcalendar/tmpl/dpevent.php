<?php
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$value = $field->value;
if ($value == '') {
	return;
}

if (!is_array($value)) {
	$value = [$value];
}

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

$texts = [];
foreach ($value as $eventId) {
	if (!$eventId) {
		continue;
	}

	// Getting the event
	$model = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel', ['ignore_request' => true]);
	$event = $model->getItem($eventId);
	if (!$event) {
		continue;
	}

	$texts[] = '<a href="' . DPCalendarHelperRoute::getEventRoute($event->id, $event->catid) . '">' . htmlentities($event->title, ENT_COMPAT, 'UTF-8') . '</a>';
}
echo implode(', ', $texts);
