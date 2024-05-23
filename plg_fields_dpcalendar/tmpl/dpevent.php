<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;

/** @var \stdClass $field */

$value = $field->value;
if ($value == '') {
	return;
}

if (!is_array($value)) {
	$value = [$value];
}

$texts = [];
foreach ($value as $eventId) {
	if (!$eventId) {
		continue;
	}

	// Getting the event
	$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator', ['ignore_request' => true]);
	$event = $model->getItem($eventId);
	if (!is_object($event)) {
		continue;
	}

	$texts[] = '<a href="' . RouteHelper::getEventRoute($event->id, $event->catid) . '">' . htmlentities((string) $event->title, ENT_COMPAT, 'UTF-8') . '</a>';
}
echo implode(', ', $texts);
