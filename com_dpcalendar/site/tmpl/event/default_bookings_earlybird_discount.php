<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Language\Text;

if (empty($this->event->earlybird_discount)) {
	return;
}

$discounts = array_filter(
	(array)$this->event->earlybird_discount,
	fn($d, $k): bool => Booking::getPriceWithDiscount(1000, $this->event, $k, '') != 1000,
	ARRAY_FILTER_USE_BOTH
);
if ($discounts === []) {
	return;
}
?>
<div class="dp-booking-info__discount-earlybird_discount dp-info-box">
	<?php foreach ($discounts as $discount) { ?>
		<div class="dp-earlybird_discount">
			<span class="dp-earlybird_discount__label">
				<?php echo $discount->label ?: $this->translate('COM_DPCALENDAR_FIELD_EARLYBIRD_LABEL'); ?>
			</span>
			<span class="dp-earlybird_discount__content">
				<?php
				$value = ($discount->type == 'value' ? DPCalendarHelper::renderPrice($discount->value) : $discount->value . '%');
				$limit = $discount->date;
				$date  = $this->dateHelper->getDate($this->event->start_date);
				if (str_starts_with((string) $limit, '-') || str_starts_with((string) $limit, '+')) {
					// Relative date
					$date->modify(str_replace('+', '-', (string) $limit));
				} else {
					// Absolute date
					$date = $this->dateHelper->getDate($limit);
					if ($date->format('H:i') === '00:00') {
						$date->setTime(23, 59, 59);
					}
				}
				$dateFormatted = $date->format($this->params->get('event_date_format', 'd.m.Y'), true); ?>
				<?php echo Text::sprintf('COM_DPCALENDAR_VIEW_EVENT_EARLYBIRD_DISCOUNT_TEXT', $value, $dateFormatted); ?>
			</span>
			<span class="dp-earlybird_discount__description"><?php echo $discount->description; ?></span>
		</div>
	<?php } ?>
</div>
