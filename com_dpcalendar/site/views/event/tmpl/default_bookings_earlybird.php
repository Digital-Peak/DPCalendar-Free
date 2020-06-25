<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->event->earlybird) {
	return;
}
?>
<div class="dp-booking-info__discount-earlybird">
	<?php foreach ($this->event->earlybird->value as $index => $value) { ?>
		<?php if (\DPCalendar\Helper\Booking::getPriceWithDiscount(1000, $this->event, $index, -2) == 1000) { ?>
			<?php continue; ?>
		<?php } ?>
		<div class="dp-earlybird dp-info-box">
			<span class="dp-earlybird__label">
				<?php echo $this->event->earlybird->label[$index] ?: $this->translate('COM_DPCALENDAR_FIELD_EARLYBIRD_LABEL'); ?>
			</span>
			<span class="dp-earlybird__content">
				<?php
				$value = ($this->event->earlybird->type[$index] == 'value' ? DPCalendarHelper::renderPrice($value) : $value . '%');
				$limit = $this->event->earlybird->date[$index];
				$date  = DPCalendarHelper::getDate($this->event->start_date);
				if (strpos($limit, '-') === 0 || strpos($limit, '+') === 0) {
					// Relative date
					$date->modify(str_replace('+', '-', $limit));
				} else {
					// Absolute date
					$date = DPCalendarHelper::getDate($limit);
					if ($date->format('H:i') == '00:00') {
						$date->setTime(23, 59, 59);
					}
				}
				$dateFormated = $date->format($this->params->get('event_date_format', 'm.d.Y'), true); ?>
				<?php echo JText::sprintf('COM_DPCALENDAR_VIEW_EVENT_EARLYBIRD_DISCOUNT_TEXT', $value, $dateFormated); ?>
			</span>
			<span class="dp-earlybird__description"><?php echo $this->event->earlybird->description[$index]; ?></span>
		</div>
	<?php } ?>
</div>
