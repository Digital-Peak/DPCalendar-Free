<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

if (!$this->hasCoupons) {
	return;
}
?>
<div class="com-dpcalendar-bookingform__total-coupon dp-coupon">
	<div class="dp-coupon__code">
		<?php echo $this->form->getField('coupon_id')->renderField(['class' => 'dp-field-coupon']); ?>
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::RECURRING]); ?>
	</div>
	<div class="dp-coupon__description">
		<span class="dp-coupon__label">
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_COUPON_DISCOUNT'); ?>:
		</span>
		<span class="dp-coupon__label dp-coupon__label_tickets">
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_COUPON_DISCOUNT_TICKETS'); ?>:
		</span>
		<span class="dp-coupon__label dp-coupon__label_options">
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_COUPON_DISCOUNT_OPTIONS'); ?>:
		</span>
		<span class="dp-coupon__content"></span>
	</div>
</div>
