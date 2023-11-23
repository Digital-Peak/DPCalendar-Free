<?php
use DPCalendar\HTML\Block\Icon;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('location_show_upcoming_events', 1)) {
	return;
}
?>
<div class="com-dpcalendar-location__events">
	<h<?php echo $this->heading + 2; ?> class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_UPCOMING_EVENTS'); ?>
	</h<?php echo $this->heading + 2; ?>>
	<div class="com-dpcalendar-location__event-list">
		<?php foreach ($this->events as $event) { ?>
			<?php $date = $this->dateHelper->getDateStringFromEvent($event, $this->params->get('date_format'), $this->params->get('time_format')); ?>
			<div class="dp-event" style="border-color: #<?php echo $event->color ?>">
				<?php if ($event->state == 3) { ?>
					<span class="dp-event__title_canceled">[<?php echo $this->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED'); ?>]</span>
				<?php } ?>
				<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-link dp-event__link">
					<?php echo $event->title; ?>
				</a>
				<div class="dp-event__date">
					<?php echo $this->layoutHelper->renderLayout(
						'block.icon',
						['icon' => Icon::CLOCK, 'title' => $this->translate('COM_DPCALENDAR_DATE')]
					); ?>
					<?php echo $date; ?>
				</div>
				<?php if ($event->rrule) { ?>
					<div class="dp-event__rrule">
						<?php echo $this->layoutHelper->renderLayout(
							'block.icon',
							[
								'icon'  => Icon::RECURRING,
								'title' => $this->translate('COM_DPCALENDAR_BOOKING_FIELD_SERIES_LABEL')
							]
						); ?>
						<?php echo nl2br($this->dateHelper->transformRRuleToString($event->rrule, $event->start_date, $event->exdates)); ?>
					</div>
				<?php } ?>
				<?php $this->displayData['event'] = $event; ?>
				<?php echo $this->layoutHelper->renderLayout('schema.event', $this->displayData); ?>
			</div>
		<?php } ?>
	</div>
</div>
