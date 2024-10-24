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
<div class="com-dpcalendar-bookingform__info-text-discount-tickets dp-info-box">
	<?php foreach ($this->event->tickets_discount as $discount) { ?>
		<div class="dp-discount">
			<span class="dp-discount__label">
				<?php echo $discount->label ?: sprintf($this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_TICKETS_DISCOUNT_AMOUNT'), $discount->amount); ?>
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
