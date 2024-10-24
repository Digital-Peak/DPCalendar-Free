<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\HTML\Helpers\StringHelper;

$format = $this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i');
$fields = array_column((array)$this->params->get('tickets_fields', []), 'field') ;
?>
<div class="com-dpcalendar-tickets__table">
	<table class="dp-table">
		<thead>
		<tr>
			<?php foreach ($this->headers as $index => $header) { ?>
				<th class="dp-table__cell<?php echo in_array($fields[$index], ['action', 'price']) ? ' dp-table__cell_center' : ''; ?>">
					<?php echo $header; ?>
				</th>
			<?php } ?>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->tickets as $ticket) { ?>
			<tr class="dp-ticket">
				<?php foreach ($ticket->preparedData as $index => $value) { ?>
					<td data-column="<?php echo $this->headers[$index]; ?>" class="dp-ticket__<?php echo $fields[$index]; ?> dp-table__cell
						<?php echo $fields[$index] === 'action' ? ' dp-table__cell_center' : ''; ?>
						<?php echo $fields[$index] === 'price' ? ' dp-table__cell_right' : ''; ?>
						">
						<?php if ($fields[$index] === 'uid') { ?>
							<a href="<?php echo $this->router->getTicketRoute($ticket); ?>" class="dp-link dp-ticket__link">
								<?php echo $ticket->price_label ?: StringHelper::abridge($value, 15, 5); ?>
							</a>
						<?php } elseif ($fields[$index] === 'event_id') { ?>
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
						<?php } elseif ($fields[$index] === 'created') { ?>
							<?php echo $this->dateHelper->getDate($ticket->created)->format($format); ?>
						<?php } elseif ($fields[$index] === 'location') { ?>
							<?php echo $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo','Administrator')->format([$ticket]); ?>
						<?php } elseif ($fields[$index] === 'action') { ?>
							<?php $this->ticket = $ticket; ?>
							<?php echo $this->loadTemplate('content_action'); ?>
						<?php } else { ?>
							<?php echo $value ?: '&nbsp;'; ?>
						<?php } ?>
					</td>
				<?php } ?>
			</tr>
			<tr class="dp-ticket-instance">
				<td colspan="<?php echo count($fields); ?>">
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
