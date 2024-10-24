<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

if (empty($this->event->user_discount)) {
	return;
}

$discounts = array_filter(
	(array)$this->event->user_discount,
	fn($d, $k): float => Booking::getPriceWithDiscount(1000, $this->event, '', $k) != 1000,
	ARRAY_FILTER_USE_BOTH
);
if ($discounts === []) {
	return;
}
?>
<div class="dp-booking-info__discount-user dp-info-box">
	<?php foreach ($this->event->user_discount as $discount) { ?>
		<div class="dp-user-discount">
			<span class="dp-user-discount__label">
				<?php echo $discount->label ?: $this->translate('COM_DPCALENDAR_FIELD_USER_DISCOUNT_LABEL'); ?>
			</span>
			<span class="dp-user-discount__content">
				<?php echo $discount->type == 'value' ? DPCalendarHelper::renderPrice($discount->value) : $value . '%'; ?>
			</span>
			<span class="dp-user-discount__description">
				<?php echo $discount->description; ?>
			</span>
		</div>
	<?php } ?>
</div>
