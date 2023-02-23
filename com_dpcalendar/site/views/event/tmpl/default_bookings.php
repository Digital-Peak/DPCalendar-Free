<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('event_show_bookings', '1')) {
	return;
}

$event = $this->event;
if (($event->capacity !== null && (int)$event->capacity === 0) || DPCalendarHelper::isFree()) {
	return;
}

$tickets = [];
foreach ($event->tickets as $t) {
	if ($this->user->id > 0 && $this->user->id == $t->user_id) {
		$tickets[] = $t;
	}
}

if ($tickets) {
	$this->app->enqueueMessage(
		JText::plural('COM_DPCALENDAR_VIEW_EVENT_BOOKED_TEXT', count($tickets), DPCalendarHelperRoute::getTicketsRoute(null, $event->id, true))
	);
}
?>
<div class="com-dpcalendar-event__booking com-dpcalendar-event_small dp-booking-info">
	<h<?php echo $this->heading + 2; ?> class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_BOOKING_INFORMATION'); ?>
	</h<?php echo $this->heading + 2; ?>>
	<?php if ($this->params->get('event_show_price', '1') && $event->price) { ?>
		<div class="dp-booking-info__discount">
			<?php echo $this->loadtemplate('bookings_earlybird'); ?>
			<?php echo $this->loadtemplate('bookings_user'); ?>
		</div>
		<?php echo $this->loadtemplate('bookings_prices'); ?>
	<?php } ?>
	<?php echo $this->loadtemplate('bookings_options'); ?>
	<?php if ($this->params->get('event_show_capacity', '1') && ($event->capacity === null || $event->capacity > 0)) { ?>
		<dl class="dp-description dp-booking-info__capacity">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_CAPACITY_LABEL'); ?></dt>
			<dd class="dp-description__description dp-event-capacity">
				<?php echo $event->capacity === null ? $this->translate('COM_DPCALENDAR_FIELD_CAPACITY_UNLIMITED') : (int)$event->capacity; ?>
			</dd>
		</dl>
	<?php } ?>
	<?php if ($this->params->get('event_show_capacity_used', '1') && ($event->capacity === null || $event->capacity > 0)) { ?>
		<dl class="dp-description dp-booking-info__capacity-used">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_CAPACITY_USED_LABEL'); ?></dt>
			<dd class="dp-description__description dp-event-capacity-used"><?php echo min($event->capacity, $event->capacity_used); ?></dd>
		</dl>
	<?php } ?>
	<?php if ($this->params->get('event_show_capacity_used', '1') && $event->capacity !== null && $event->booking_waiting_list
		&& ((int)$event->waiting_list_count > 0 || $event->capacity_used >= $event->capacity)) { ?>
		<dl class="dp-description dp-booking-info__waiting">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_BOOKING_WAITING_LIST'); ?></dt>
			<dd class="dp-description__description dp-event-waiting">
				<?php echo $event->waiting_list_count ?: count(array_filter($event->tickets, fn ($t) => $t->state == 8)); ?>
			</dd>
		</dl>
	<?php } ?>
	<?php if ($event->booking_information) { ?>
		<div class="dp-booking-info__information"><?php echo $event->booking_information; ?></div>
	<?php } ?>
</div>
