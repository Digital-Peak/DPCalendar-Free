<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;

if (!$authoredEvents && !$hostEvents) {
	return;
}

$document->loadStyleFile('events.css', 'plg_content_dpcalendar');
?>
<div class="plg-content-dpcalendar-user-events">
	<?php if ($authoredEvents) { ?>
		<div class="plg-content-dpcalendar-user-events__header">
            <?php echo $translator->translate('PLG_CONTENT_DPCALENDAR_USER_EVENTS_TITLE_AUTHORED'); ?>
        </div>
		<ul class="plg-content-dpcalendar-user-events__authors dp-events dp-list dp-list_unordered">
			<?php foreach ($authoredEvents as $event) { ?>
				<li class="dp-event">
					<a href="<?php echo $router->getEventRoute($event->id, $event->catid); ?>" class="dp-event__link dp-link">
						<?php echo $event->title; ?>
					</a>
					<span class="dp-event__date">
						<?php echo $dateHelper->getDateStringFromEvent($event); ?>
					</span>
					<span class="dp-event__calendar">
						<?php $calendar = DPCalendarHelper::getCalendar($event->catid); ?>
						<?php echo $calendar != null ? $calendar->title : $event->catid; ?>
					</span>
			    </li>
			<?php } ?>
		</ul>
	<?php } ?>
	<?php if ($hostEvents) { ?>
		<div class="plg-content-dpcalendar-user-events__header">
            <?php echo $translator->translate('PLG_CONTENT_DPCALENDAR_USER_EVENTS_TITLE_HOSTS'); ?>
        </div>
		<ul class="plg-content-dpcalendar-user-events__hosts dp-events dp-list dp-list_unordered">
			<?php foreach ($hostEvents as $event) { ?>
				<li class="dp-event">
					<a href="<?php echo $router->getEventRoute($event->id, $event->catid); ?>" class="dp-event__link dp-link">
						<?php echo $event->title; ?>
					</a>
					<span class="dp-event__date">
						<?php echo $dateHelper->getDateStringFromEvent($event); ?>
					</span>
					<span class="dp-event__calendar">
						<?php $calendar = DPCalendarHelper::getCalendar($event->catid); ?>
						<?php echo $calendar != null ? $calendar->title : $event->catid; ?>
					</span>
				</li>
			<?php } ?>
		</ul>
	<?php } ?>
</div>
