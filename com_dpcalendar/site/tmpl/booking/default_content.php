<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
?>
<div class="com-dpcalendar-booking__content">
	<?php if ($this->booking->price) { ?>
		<div class="com-dpcalendar-booking__invoice-details">
			<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_INVOICE_INVOICE_DETAILS'); ?></h3>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_INVOICE_NUMBER'); ?></dt>
				<dd class="dp-description__description"><?php echo $this->booking->uid; ?></dd>
			</dl>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_INVOICE_DATE'); ?></dt>
				<dd class="dp-description__description">
					<?php echo $this->dateHelper->getDate($this->booking->book_date)
						->format($this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i')); ?>
				</dd>
			</dl>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></dt>
				<dd class="dp-description__description">
					<?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($this->booking->price, $this->params->get('currency_symbol', '$')); ?>
					<?php if ($this->booking->tax && $this->booking->tax != '0.00') { ?>
						(<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_TAX_LABEL'); ?>
						<?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($this->booking->tax, $this->params->get('currency_symbol', '$')); ?>)
					<?php } ?>
				</dd>
			</dl>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PAYMENT_PROVIDER_LABEL'); ?></dt>
				<dd class="dp-description__description">
					<?php if ($this->paymentProvider) { ?>
						<?php echo $this->translate('PLG_DPCALENDARPAY_' . $this->paymentProvider->plugin_name . '_TITLE'); ?>
					<?php } else { ?>
						<?php echo $this->booking->processor; ?>
					<?php } ?>
				</dd>
			</dl>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('JSTATUS'); ?></dt>
				<dd class="dp-description__description"><?php echo Booking::getStatusLabel($this->booking); ?></dd>
			</dl>
		</div>
	<?php } ?>
	<div class="com-dpcalendar-booking__booking-details">
		<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_INVOICE_BOOKING_DETAILS'); ?></h3>
		<?php if (!$this->booking->price) { ?>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?></dt>
				<dd class="dp-description__description"><?php echo $this->booking->uid; ?></dd>
			</dl>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_TICKETS_LABEL'); ?></dt>
				<dd class="dp-description__description"><?php echo $this->booking->amount_tickets; ?></dd>
			</dl>
		<?php } ?>
		<?php foreach ($this->bookingFields as $field) { ?>
			<dl class="dp-description dp-field-<?php echo $field->id; ?>">
				<dt class="dp-description__label"><?php echo $this->translate($field->dpDisplayLabel); ?></dt>
				<dd class="dp-description__description"><?php echo $field->dpDisplayContent; ?></dd>
			</dl>
		<?php } ?>
	</div>
</div>
