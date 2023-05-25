<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->seriesEvents || !$this->params->get('event_show_series', 1) || empty($this->event->series_min_start_date)) {
	return;
}
?>
<div class="com-dpcalendar-event__series com-dpcalendar-event_small">
	<h<?php echo $this->heading + 2; ?> class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_SERIES_LIST'); ?>
	</h<?php echo $this->heading + 2; ?>>
	<ul class="dp-event-list dp-list">
		<?php foreach ($this->seriesEvents as $event) { ?>
			<li class="dp-events-list__event dp-event-<?php echo $event->id; ?>">
				<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-link">
					<?php echo $this->dateHelper->getDateStringFromEvent(
						$event,
						$this->params->get('event_date_format', 'd.m.Y'),
						$this->params->get('event_time_format', 'H:i')
					); ?>
				</a>
			</li>
		<?php } ?>
	</ul>
	<p class="com-dpcalendar-event__series-info">
		<?php echo JText::sprintf(
			'COM_DPCALENDAR_VIEW_EVENT_SERIES_INFO',
			$this->dateHelper->getDate($this->event->series_min_start_date)->format($this->params->get('event_date_format', 'd.m.Y'), true),
			$this->dateHelper->getDate($this->event->series_max_end_date)->format($this->params->get('event_date_format', 'd.m.Y'), true)
		); ?>
	</p>
</div>
