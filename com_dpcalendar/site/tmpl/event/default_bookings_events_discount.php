<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

if ($this->originalEvent === null || !$this->originalEvent->events_discount) {
	return;
}
?>
<div class="dp-booking-info__discount-events dp-info-box">
	<?php foreach ($this->originalEvent->events_discount as $discount) { ?>
		<div class="dp-events-discount">
			<span class="dp-events-discount__label">
				<?php echo $discount->label ?: sprintf($this->translate('COM_DPCALENDAR_VIEW_EVENT_MULTIPLE_EVENTS_DISCOUNT_TEXT'), $discount->amount); ?>
			</span>
			<span class="dp-events-discount__content">
				<?php echo $discount->type == 'value' ? DPCalendarHelper::renderPrice($discount->value) : $discount->value . '%'; ?>
			</span>
			<span class="dp-events-discount__description">
				<?php echo $discount->description; ?>
			</span>
		</div>
	<?php } ?>
</div>
