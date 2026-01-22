<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2025 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
?>
<div class="com-dpcalendar-booking__content">
	<h2 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_INVOICE_INVOICE_DETAILS'); ?></h2>
	<dl class="dp-description">
		<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_INVOICE_NUMBER'); ?></dt>
		<dd class="dp-description__description"><?php echo $this->booking->uid; ?></dd>
	</dl>
	<dl class="dp-description">
		<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></dt>
		<dd class="dp-description__description">
			<?php echo DPCalendarHelper::renderPrice($this->booking->price); ?>
			<?php if ($this->booking->tax && $this->booking->tax != '0.00') { ?>
				<?php echo sprintf(
					$this->translator->translate('COM_DPCALENDAR_LAYOUT_RECEIPT_TAX_TEXT'),
					round($this->booking->tax_rate ?? 0, 2),
					DPCalendarHelper::renderPrice($this->booking->tax)
				); ?>
			<?php } ?>
		</dd>
	</dl>
</div>
