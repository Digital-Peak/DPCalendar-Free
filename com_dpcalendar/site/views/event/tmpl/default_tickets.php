<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('event_show_tickets', 0) || !isset($this->event->tickets) || !$this->event->tickets) {
	return;
}

$format  = $this->params->get('event_date_format', 'm.d.Y') . ' ' . $this->params->get('event_time_format', 'g:i a');
$limited = $this->params->get('event_show_tickets') == '2';

$hasPrice = false;
foreach ($this->event->tickets as $ticket) {
	if ($ticket->price && $ticket->price != '0.00') {
		$hasPrice = true;
		break;
	}
}
?>
<div class="com-dpcalendar-event__tickets">
	<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_TICKETS_LABEL'); ?></h3>
	<table class="dp-table">
		<thead>
		<tr>
			<?php if (!$limited) { ?>
				<th><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?></th>
				<th><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE'); ?></th>
			<?php } ?>
			<th><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?></th>
			<?php if (!$limited) { ?>
				<th><?php echo $this->translate('COM_DPCALENDAR_CREATED_DATE'); ?></th>
				<?php if ($this->params->get('ticket_show_seat', 1)) { ?>
					<th><?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL'); ?></th>
				<?php } ?>
			<?php } ?>
			<?php if (!$limited && $hasPrice) { ?>
				<th><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></th>
			<?php } ?>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->event->tickets as $ticket) { ?>
			<tr>
				<?php if (!$limited) { ?>
					<td data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?>">
						<?php echo JHtmlString::abridge($ticket->uid, 15, 5); ?>
					</td>
					<td data-column="<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE'); ?>">
						<?php echo \DPCalendar\Helper\Booking::getStatusLabel($ticket); ?>
					</td>
				<?php } ?>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL'); ?>">
					<?php echo $ticket->name; ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?>">
					<?php echo \DPCalendar\Helper\Location::format([$ticket]) ?: '&nbsp;'; ?>
				</td>
				<?php if (!$limited) { ?>
					<td data-column="<?php echo $this->translate('COM_DPCALENDAR_CREATED_DATE'); ?>">
						<?php echo $this->dateHelper->getDate($ticket->created)->format($format, true); ?>
					</td>
					<?php if ($this->params->get('ticket_show_seat', 1)) { ?>
						<td data-column="<?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL'); ?>">
							<?php echo $ticket->seat; ?>
						</td>
					<?php } ?>
				<?php } ?>
				<?php if (!$limited && $hasPrice) { ?>
					<td data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?>">
						<?php echo DPCalendarHelper::renderPrice($ticket->price); ?>
					</td>
				<?php } ?>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>
