<?php
use Joomla\CMS\HTML\Helpers\StringHelper;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Location;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

if (!$this->tickets) {
	return;
}

$format = $this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i');

$hasPrice = array_filter(
	$this->tickets,
	static fn($ticket): bool => $ticket->price > 0
);
?>
<div class="com-dpcalendar-tickets__table">
	<table class="dp-table">
		<thead>
		<tr>
			<th><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_ACTION'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_EVENT'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_CREATED_DATE'); ?></th>
			<?php if ($hasPrice !== []) { ?>
				<th><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></th>
			<?php } ?>
			<?php foreach ($this->tickets[0]->jcfields as $field) { ?>
				<th><?php echo $field->label; ?></th>
			<?php } ?>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->tickets as $ticket) { ?>
			<tr class="dp-ticket">
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?>">
					<a href="<?php echo $this->router->getTicketRoute($ticket); ?>" class="dp-link dp-ticket__link">
						<?php echo $ticket->price_label ?: StringHelper::abridge($ticket->uid, 15, 5); ?>
					</a>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_ACTION'); ?>">
					<?php $this->ticket = $ticket; ?>
					<?php echo $this->loadTemplate('content_action'); ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_EVENT'); ?>">
					<a href="<?php echo $this->router->getEventRoute($ticket->event_id, $ticket->event_calid); ?>"
					   class="dp-link dp-ticket__event-link">
						<?php echo $ticket->event_title; ?>
						<?php if (!$this->eventInstances[$ticket->event_id]) { ?>
							<?php echo $this->dateHelper->getDateStringFromEvent($ticket); ?>
						<?php } ?>
						<?php if ($ticket->event_rrule) { ?>
							<div class="dp-ticket__event-rrule">
								<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::RECURRING]); ?>
								<?php echo $this->dateHelper->transformRRuleToString($ticket->event_rrule, $ticket->start_date); ?>
							</div>
						<?php } ?>
					</a>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE'); ?>" class="dp-ticket__state">
					<?php echo Booking::getStatusLabel($ticket); ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL'); ?>" class="dp-ticket__name">
					<?php echo $ticket->name; ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?>" class="dp-ticket__location">
					<?php echo $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo','Administrator')->format([$ticket]) ?: '&nbsp;'; ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_CREATED_DATE'); ?>" class="dp-ticket__created-date">
					<?php echo $this->dateHelper->getDate($ticket->created)->format($format); ?>
				</td>
				<?php if ($hasPrice !== []) { ?>
					<td class="dp-cell-price dp-ticket__price"
						data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?>">
						<?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($ticket->price, $this->params->get('currency_symbol', '$')); ?>
					</td>
				<?php } ?>
				<?php foreach ($ticket->jcfields as $field) { ?>
					<td data-column="<?php echo $field->label; ?>" class="dp-ticket__field"><?php echo $field->value; ?></td>
				<?php } ?>
			</tr>
			<tr class="dp-ticket-instance">
				<td colspan="<?php echo 7 + ($hasPrice !== [] ? 1 :0) + (is_countable($this->tickets[0]->jcfields) ? count($this->tickets[0]->jcfields) : 0); ?>">
					<?php if ($this->eventInstances[$ticket->event_id]) { ?>
						<div class="dp-toggle">
							<div class="dp-toggle__up dp-toggle_hidden" data-direction="up">
								<span class="dp-toggle__text">
									<?php echo $this->translate('COM_DPCALENDAR_VIEW_TICKETS_HIDE_SERIES_LABEL'); ?>
								</span>
								<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::UP]); ?>
							</div>
							<div class="dp-toggle__down" data-direction="down">
								<span class="dp-toggle__text">
									<?php echo $this->translate('COM_DPCALENDAR_VIEW_TICKETS_SHOW_SERIES_LABEL'); ?>
								</span>
								<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DOWN]); ?>
							</div>
						</div>
						<div class="dp-ticket__event-instances">
							<?php foreach ($this->eventInstances[$ticket->event_id] as $event) { ?>
								<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>"
								   class="dp-link dp-ticket__event-link dp-ticket__event-instance">
									<?php echo $this->dateHelper->getDateStringFromEvent($event); ?>
								</a>
							<?php } ?>
						</div>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>
