<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

if ($this->bookingId) {
	return;
}

$this->translator->translateJS('COM_DPCALENDAR_VIEW_BOOKINGFORM_DISCOUNT');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_BOOKINGFORM_TICKETS_OVERBOOKED_MESSAGE');
?>
<div class="com-dpcalendar-bookingform__events">
	<?php foreach ($this->events as $instance) { ?>
		<div class="dp-event dp-event_<?php echo $instance->id == $this->event->id ? 'original' : 'instance'; ?>"
			 data-event-id="<?php echo $instance->id; ?>" data-ticket-count="<?php echo $instance->ticket_count; ?>">
			<h2 class="dp-heading">
				<span class="dp-event__title"><?php echo $instance->title; ?></span>
				<span class="dp-event__date">
						<?php $dateData = $instance->booking_series != 1 || !$instance->series_min_start_date ? $instance :
							(object)[
								'all_day'       => true,
								'show_end_time' => false,
								'start_date'    => $instance->series_min_start_date,
								'end_date'      => $instance->series_max_end_date
							]; ?>
						<?php echo $this->dateHelper->getDateStringFromEvent(
							$dateData,
							$this->params->get('event_date_format', 'd.m.Y'),
							$this->params->get('event_time_format', 'H:i')
						); ?>
				</span>
			</h2>
			<?php if ($instance->ticket_count) { ?>
				<div class="dp-event__ticket-count">
					<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_TICKETS_AVAILABLE_MESSAGE') . ': ' . $instance->ticket_count; ?>
				</div>
			<?php } ?>
			<?php if ($instance->booking_series == 1) { ?>
				<div class="com-dpcalendar-bookingform__whole-series">
					<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_BOOK_WHOLE_SERIES_MESSAGE'); ?>
				</div>
			<?php } ?>
			<table class="dp-event__tickets dp-table">
				<thead class="dp-table__thead">
				<tr class="dp-ticket">
					<th class="dp-ticket__title" <?php echo $this->needsPayment ? '' : 'colspan="2"'; ?>>
						<?php echo $this->translate('COM_DPCALENDAR_TICKET'); ?>
					</th>
					<?php if ($this->needsPayment) { ?>
						<th class="dp-ticket__price"><?php echo $this->translate('COM_DPCALENDAR_FIELD_PRICE_PRICE_LABEL'); ?></th>
					<?php } ?>
					<th class="dp-ticket__amount"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_AMOUNT_LABEL'); ?></th>
					<th class="dp-ticket__calculated-price">
						<?php if ($this->needsPayment) { ?>
							<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_TOTAL'); ?>
						<?php } ?>
					</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($instance->price->value as $key => $value) { ?>
					<tr class="dp-ticket" data-ticket-price="<?php echo $key; ?>">
						<td class="dp-ticket__title" <?php echo $this->needsPayment ? '' : 'colspan="2"'; ?>
							data-column="<?php echo $this->translate('COM_DPCALENDAR_TICKET'); ?>">
							<?php echo $instance->price->label[$key]?: '&nbsp;'; ?>
						</td>
						<?php if ($this->needsPayment) { ?>
							<td class="dp-ticket__price"
								data-column="<?php echo $this->translate('COM_DPCALENDAR_FIELD_PRICE_PRICE_LABEL'); ?>">
								<?php echo DPCalendarHelper::renderPrice($value); ?>
							</td>
						<?php } ?>
						<td class="dp-ticket__amount" data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_AMOUNT_LABEL'); ?>">
							<?php $name = $this->form->getFormControl() . '[event_id][' . $instance->id . '][tickets][' . $key . ']'; ?>
							<?php if ($instance->ticket_count == 0) { ?>
								<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_CHOOSE_TICKET_LIMIT_REACHED'); ?>
							<?php } else { ?>
								<select name="<?php echo $name; ?>" class="dp-select dp-select_plain">
									<?php for ($i = 0; $i <= $instance->ticket_count; $i++) { ?>
										<?php $selected = (is_countable($instance->price->value) ? count($instance->price->value) : 0) == 1 && $i == 1 && $instance->id == $this->event->id ? 'selected="selected"' : ''; ?>
										<?php $selected = !empty($this->selection[$instance->id]) && !empty($this->selection[$instance->id]['tickets']) &&$this->selection[$instance->id]['tickets'][$key] && $this->selection[$instance->id]['tickets'][$key] == $i ? 'selected="selected"' : $selected; ?>
										<option value="<?php echo $i; ?>"<?php echo $selected; ?>><?php echo $i; ?></option>
									<?php } ?>
								</select>
							<?php } ?>
						</td>
						<td class="dp-ticket__calculated-price dp-price"
							data-column="<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_TOTAL'); ?>">
							<?php if ($this->needsPayment) { ?>
								<div class="dp-price__info dp-price_hidden">
									<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::INFO]); ?>
								</div>
								<div class="dp-price__live"><?php echo DPCalendarHelper::renderPrice('0.00'); ?></div>
								<div class="dp-price__original dp-price_hidden"><?php echo DPCalendarHelper::renderPrice('0.00'); ?></div>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
				</tbody>
				<?php if ($instance->booking_options) { ?>
					<tbody class="dp-table__thead">
					<tr class="dp-ticket">
						<th class="dp-ticket__title"><?php echo $this->translate('COM_DPCALENDAR_OPTION'); ?></th>
						<th class="dp-ticket__price"><?php echo $this->translate('COM_DPCALENDAR_FIELD_PRICE_PRICE_LABEL'); ?></th>
						<th class="dp-ticket__amount"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_AMOUNT_LABEL'); ?></th>
						<th class="dp-ticket__calculated-price"><?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_TOTAL'); ?></th>
					</tr>
					</tbody>
					<tbody>
					<?php foreach ($instance->booking_options as $key => $option) { ?>
						<?php $key = preg_replace('/\D/', '', (string) $key); ?>
						<tr class="dp-option" data-option-price="<?php echo $key; ?>">
							<td class="dp-option__title" data-column="<?php echo $this->translate('COM_DPCALENDAR_OPTION'); ?>">
								<?php echo $option->label; ?>
							</td>
							<td class="dp-option__price" data-column="<?php echo $this->translate('COM_DPCALENDAR_FIELD_PRICE_PRICE_LABEL'); ?>">
								<?php echo DPCalendarHelper::renderPrice($option->price); ?>
							</td>
							<td class="dp-option__amount" data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_AMOUNT_LABEL'); ?>">
								<?php $name = $this->form->getFormControl() . '[event_id][' . $instance->id . '][options][' . $key . ']'; ?>
								<select name="<?php echo $name; ?>" class="dp-select dp-select_plain">
									<?php for ($i = $option->min_amount; $i <= $option->amount; $i++) { ?>
										<?php $selected = !empty($this->selection[$instance->id]) && !empty($this->selection[$instance->id]['tickets']) &&$this->selection[$instance->id]['options'][$key] && $this->selection[$instance->id]['options'][$key] == $i ? 'selected="selected"' : ''; ?>
										<option value="<?php echo $i; ?>"<?php echo $selected; ?>><?php echo $i; ?></option>
									<?php } ?>
								</select>
							</td>
							<td class="dp-option__calculated-price dp-price"
								data-column="<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_TOTAL'); ?>">
								<div class="dp-price__live"><?php echo DPCalendarHelper::renderPrice('0.00'); ?></div>
								<div class="dp-price__original dp-price_hidden"></div>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				<?php } ?>
			</table>
		</div>
	<?php } ?>
</div>
