<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (empty($this->event->booking_options)) {
	return;
}
?>
<dl class="dp-description dp-booking-info__options">
	<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_OPTIONS'); ?></dt>
	<dd class="dp-description__description">
		<?php foreach ($this->event->booking_options as $option) { ?>
			<div class="dp-booking-option">
				<span class="dp-booking-option__price"><?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($option->price); ?></span>
				<span class="dp-booking-option__label"><?php echo $option->label; ?></span>
				<span class="dp-booking-option__description"><?php echo $option->description; ?></span>
			</div>
		<?php } ?>
	</dd>
</dl>
