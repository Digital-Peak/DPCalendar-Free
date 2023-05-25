<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->event->user_discount) {
	return;
}
?>
<div class="dp-booking-info__discount-user">
	<?php foreach ($this->event->user_discount->value as $index => $value) { ?>
		<?php if (\DPCalendar\Helper\Booking::getPriceWithDiscount(1000, $this->event, -2, $index) == 1000) { ?>
			<?php continue; ?>
		<?php } ?>
		<div class="dp-userdiscount dp-info-box">
			<span class="dp-userdiscount__label">
				<?php echo $this->event->user_discount->label[$index] ?: $this->translate('COM_DPCALENDAR_FIELD_USER_DISCOUNT_LABEL'); ?>
			</span>
			<span class="dp-userdiscount__content">
				<?php echo $this->event->user_discount->type[$index] == 'value' ? DPCalendarHelper::renderPrice($value) : $value . '%'; ?>
			</span>
			<span class="dp-userdiscount__description">
				<?php echo $this->event->user_discount->description[$index]; ?>
			</span>
		</div>
	<?php } ?>
</div>
