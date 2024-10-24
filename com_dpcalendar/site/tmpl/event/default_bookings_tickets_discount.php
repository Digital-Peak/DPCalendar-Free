<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

if (!$this->event->tickets_discount) {
	return;
}
?>
<div class="dp-booking-info__discount-tickets dp-info-box">
	<?php foreach ($this->event->tickets_discount as $discount) { ?>
		<div class="dp-tickets-discount">
			<span class="dp-tickets-discount__label">
				<?php echo $discount->label ?: sprintf($this->translate('COM_DPCALENDAR_VIEW_EVENT_MULTIPLE_TICKETS_DISCOUNT_TEXT'), $discount->amount); ?>
			</span>
			<span class="dp-tickets-discount__content">
				<?php echo $discount->type == 'value' ? DPCalendarHelper::renderPrice($discount->value) : $discount->value . '%'; ?>
			</span>
			<span class="dp-tickets-discount__description">
				<?php echo $discount->description; ?>
			</span>
		</div>
	<?php } ?>
</div>
