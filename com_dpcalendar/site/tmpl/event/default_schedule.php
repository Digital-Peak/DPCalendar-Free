<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->event->schedule || !$this->params->get('event_show_schedule', 1)) {
	return;
}
?>
<div class="com-dpcalendar-event__schedule com-dpcalendar-event_small">
	<h<?php echo $this->heading + 2; ?> class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_FIELD_SCHEDULE_LABEL'); ?>
	</h<?php echo $this->heading + 2; ?>>
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
