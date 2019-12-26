<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->seriesEvents || !$this->params->get('event_show_series', 1)) {
	return;
}
?>
<div class="com-dpcalendar-event__series com-dpcalendar-event_small">
	<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_SERIES_LIST'); ?></h3>
	<ul class="dp-event-list">
		<?php foreach ($this->seriesEvents as $event) { ?>
			<li class="dp-events-list__event dp-event-<?php echo $event->id; ?>">
				<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-link">
					<?php echo $this->dateHelper->getDateStringFromEvent(
						$event,
						$this->params->get('event_date_format', 'm.d.Y'),
						$this->params->get('event_time_format', 'g:i a')
					); ?>
				</a>
			</li>
		<?php } ?>
	</ul>
	<p class="com-dpcalendar-event__series-info">
		<?php echo JText::sprintf(
			'COM_DPCALENDAR_VIEW_EVENT_SERIES_INFO',
			$this->dateHelper->getDate($this->event->series_min_start_date)->format($this->params->get('event_date_format', 'm.d.Y'), true),
			$this->dateHelper->getDate($this->event->series_max_end_date)->format($this->params->get('event_date_format', 'm.d.Y'), true)
		); ?>
	</p>
</div>
