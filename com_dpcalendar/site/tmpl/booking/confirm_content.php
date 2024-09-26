<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-booking__content">
	<dl class="dp-description">
		<dt class="dp-description__label">
			<?php echo $this->translate($this->booking->price ? 'COM_DPCALENDAR_INVOICE_NUMBER' : 'JGRID_HEADING_ID'); ?>
		</dt>
		<dd class="dp-description__description"><?php echo $this->booking->uid; ?></dd>
	</dl>
	<dl class="dp-description">
		<dt class="dp-description__label">
			<?php echo $this->translate($this->booking->price ? 'COM_DPCALENDAR_INVOICE_DATE' : 'COM_DPCALENDAR_CREATED_DATE'); ?>
		</dt>
		<dd class="dp-description__description">
			<?php echo $this->dateHelper->getDate($this->booking->book_date)
				->format($this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i')); ?>
		</dd>
	</dl>
	<?php if ($this->booking->price) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></dt>
			<dd class="dp-description__description">
				<?php echo DPCalendarHelper::renderPrice($this->booking->price); ?>
				<?php if ($this->booking->tax && $this->booking->tax != '0.00') { ?>
					(<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_TAX_LABEL'); ?>
					<?php echo DPCalendarHelper::renderPrice($this->booking->tax); ?>)
				<?php } ?>
			</dd>
		</dl>
	<?php } ?>
	<?php foreach ($this->bookingFields as $field) { ?>
		<dl class="dp-description dp-field-<?php echo $field->id; ?>">
			<dt class="dp-description__label"><?php echo $this->translate($field->dpDisplayLabel); ?></dt>
			<dd class="dp-description__description"><?php echo $field->dpDisplayContent; ?></dd>
		</dl>
	<?php } ?>
</div>
