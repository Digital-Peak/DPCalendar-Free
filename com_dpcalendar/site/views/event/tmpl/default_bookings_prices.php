<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="dp-booking-info__prices">
	<?php foreach ($this->event->price->value as $key => $value) { ?>
		<?php $discounted = \DPCalendar\Helper\Booking::getPriceWithDiscount($value, $this->event); ?>
		<dl class="dp-description dp-booking-info__price">
			<dt class="dp-description__label">
				<?php echo $this->event->price->label[$key] ?: $this->translate('COM_DPCALENDAR_FIELD_PRICE_LABEL'); ?>
			</dt>
			<dd class="dp-description__description dp-event-price">
				<span class="dp-event-price__regular<?php echo $discounted != $value ? ' dp-event-price__regular_has-discount' : ''; ?>">
					<?php echo $value === '' ? '' : DPCalendarHelper::renderPrice($value); ?>
				</span>
				<?php if ($discounted != $value) { ?>
					<span class="dp-event-price__discount"><?php echo DPCalendarHelper::renderPrice($discounted); ?></span>
				<?php } ?>
				<span class="dp-event-price__description"><?php echo $this->event->price->description[$key]; ?></span>
			</dd>
		</dl>
	<?php } ?>
</div>
