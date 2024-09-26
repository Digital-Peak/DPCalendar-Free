<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

$event = $this->event->original_id > 0 && $this->originalEvent !== null ? $this->originalEvent : $this->event;
if (!$event->events_discount || $event->booking_series != 2) {
    return;
}
?>
<div class="com-dpcalendar-bookingform__info-text-discount-events dp-info-box">
	<?php foreach ($event->events_discount as $discount) { ?>
		<div class="dp-discount">
			<span class="dp-discount__label">
				<?php echo $discount->label ?: sprintf($this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_EVENTS_DISCOUNT_AMOUNT'), $discount->amount); ?>
			</span>
			<span class="dp-discount__content">
				<?php echo $discount->type === 'value' ? DPCalendarHelper::renderPrice($discount->value) : $discount->value . '%'; ?>
			</span>
			<span class="dp-discount__description">
				<?php echo $discount->description; ?>
			</span>
		</div>
	<?php } ?>
</div>
