<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
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
<div class="com-dpcalendar-event__booking dp-booking-info">
	<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_BOOKING_INFORMATION'); ?></h3>
	<?php if ($this->params->get('event_show_price', '1') && $event->price) { ?>
		<div class="dp-booking-info__discount">
			<?php if ($event->earlybird) { ?>
				<?php foreach ($event->earlybird->value as $index => $value) { ?>
					<?php if (\DPCalendar\Helper\Booking::getPriceWithDiscount(1000, $event, $index, -2) == 1000) { ?>
						<?php continue; ?>
					<?php } ?>
					<div class="dp-earlybird dp-info-box">
						<span class="dp-earlybird__label">
							<?php echo $event->earlybird->label[$index] ?: $this->translate('COM_DPCALENDAR_FIELD_EARLYBIRD_LABEL'); ?>
						</span>
						<span class="dp-earlybird__content">
							<?php
							$value = ($event->earlybird->type[$index] == 'value' ? DPCalendarHelper::renderPrice($value) : $value . ' %');
							$limit = $event->earlybird->date[$index];
							$date  = DPCalendarHelper::getDate($event->start_date);
							if (strpos($limit, '-') === 0 || strpos($limit, '+') === 0) {
								// Relative date
								$date->modify(str_replace('+', '-', $limit));
							} else {
								// Absolute date
								$date = DPCalendarHelper::getDate($limit);
								if ($date->format('H:i') == '00:00') {
									$date->setTime(23, 59, 59);
								}
							}
							$dateFormated = $date->format($this->params->get('event_date_format', 'm.d.Y'), true); ?>
							<?php echo JText::sprintf('COM_DPCALENDAR_VIEW_EVENT_EARLYBIRD_DISCOUNT_TEXT', $value, $dateFormated); ?>
						</span>
						<span class="dp-earlybird__description">
							<?php echo $event->earlybird->description[$index]; ?>
						</span>
					</div>
				<?php } ?>
			<?php } ?>
			<?php if ($event->user_discount) { ?>
				<?php foreach ($event->user_discount->value as $index => $value) { ?>
					<?php if (\DPCalendar\Helper\Booking::getPriceWithDiscount(1000, $event, -2, $index) == 1000) { ?>
						<?php continue; ?>
					<?php } ?>
					<div class="dp-userdiscount dp-info-box">
						<span class="dp-userdiscount__label">
							<?php echo $event->user_discount->label[$index] ?: $this->translate('COM_DPCALENDAR_FIELD_USER_DISCOUNT_LABEL'); ?>
						</span>
						<span class="dp-userdiscount__content">
							<?php echo $event->user_discount->type[$index] == 'value' ? DPCalendarHelper::renderPrice($value) : $value . ' %'; ?>
						</span>
						<span class="dp-userdiscount__description">
							<?php echo $event->user_discount->description[$index]; ?>
						</span>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
		<?php foreach ($event->price->value as $key => $value) { ?>
			<?php $discounted = \DPCalendar\Helper\Booking::getPriceWithDiscount($value, $event); ?>
			<dl class="dp-description dp-booking-info__price">
				<dt class="dp-description__label">
					<?php echo $event->price->label[$key] ?: $this->translate('COM_DPCALENDAR_FIELD_PRICE_LABEL'); ?>
				</dt>
				<dd class="dp-description__description dp-event-price">
					<span class="dp-event-price__regular<?php echo $discounted != $value ? ' dp-event-price__regular_has-discount' : ''; ?>">
						<?php echo DPCalendarHelper::renderPrice($value); ?>
					</span>
					<?php if ($discounted != $value) { ?>
						<span class="dp-event-price__discount"><?php echo DPCalendarHelper::renderPrice($discounted); ?></span>
					<?php } ?>
					<span class="dp-event-price__description">
						<?php echo $event->price->description[$key]; ?>
					</span>
				</dd>
			</dl>
		<?php } ?>
	<?php } ?>
	<?php if (!empty($event->booking_options)) { ?>
		<dl class="dp-description dp-booking-info__options">
			<dt class="dp-description__label">
				<?php echo $this->translate('COM_DPCALENDAR_OPTIONS'); ?>
			</dt>
			<dd class="dp-description__description">
				<?php foreach ($event->booking_options as $option) { ?>
					<div class="dp-booking-option">
						<span class="dp-booking-option__price"><?php echo DPCalendarHelper::renderPrice($option->price); ?></span>
						<span class="dp-booking-option__label"><?php echo $option->label; ?></span>
						<span class="dp-booking-option__description"><?php echo $option->description; ?></span>
					</div>
				<?php } ?>
			</dd>
		</dl>
	<?php } ?>
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
			<dd class="dp-description__description dp-event-capacity">
				<?php echo $event->capacity_used; ?>
			</dd>
		</dl>
	<?php } ?>
	<?php if ($event->booking_information) { ?>
		<div class="dp-booking-info__information">
			<?php echo $event->booking_information; ?>
		</div>
	<?php } ?>
</div>
