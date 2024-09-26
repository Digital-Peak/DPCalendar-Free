<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

if ($this->bookingId || !$this->needsPayment) {
	return;
}
?>
<div class="com-dpcalendar-bookingform__total dp-price-total">
	<span class="dp-price-total__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_TOTAL'); ?>: </span>
	<span class="dp-price-total__content" data-raw="0">
		<?php echo DPCalendarHelper::renderPrice($this->booking && $this->booking->id ? $this->booking->price : 0.00); ?>
	</span>
</div>
