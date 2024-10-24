<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
?>
<div class="dp-booking-info__prices">
	<?php foreach ($this->event->prices as $price) { ?>
		<?php $discounted = Booking::getPriceWithDiscount($price->value, $this->event); ?>
		<dl class="dp-description dp-booking-info__price">
			<dt class="dp-description__label">
				<?php echo $price->label ?: $this->translate('COM_DPCALENDAR_FIELD_PRICES_LABEL'); ?>
			</dt>
			<dd class="dp-description__description dp-event-price">
				<span class="dp-event-price__regular<?php echo $discounted != $price->value ? ' dp-event-price__regular_has-discount' : ''; ?>">
					<?php echo $price->value === '' ? '' : DPCalendarHelper::renderPrice($price->value); ?>
				</span>
				<?php if ($discounted != $price->value) { ?>
					<span class="dp-event-price__discount"><?php echo DPCalendarHelper::renderPrice($discounted); ?></span>
				<?php } ?>
				<span class="dp-event-price__description"><?php echo $price->description; ?></span>
			</dd>
		</dl>
	<?php } ?>
</div>
