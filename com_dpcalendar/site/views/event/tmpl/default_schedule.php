<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->event->schedule || !$this->params->get('event_show_schedule', 1)) {
	return;
}
?>
<div class="com-dpcalendar-event__schedule com-dpcalendar-event_small">
	<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_FIELD_SCHEDULE_LABEL'); ?></h3>
	<div class="dp-schedule-list">
		<?php foreach ($this->event->schedule as $index => $schedule) { ?>
			<div class="dp-schedule-list__item dp-schedule dp-schedule-<?php echo $index; ?>">
				<div class="dp-schedule__duration"><?php echo $schedule->duration . ' ' . $this->translate('COM_DPCALENDAR_VIEW_EVENT_MINUTES'); ?></div>
				<div class="dp-schedule__title"><?php echo $schedule->title; ?></div>
				<div class="dp-schedule__description"><?php echo $schedule->description; ?></div>
			</div>
		<?php } ?>
	</div>
</div>
