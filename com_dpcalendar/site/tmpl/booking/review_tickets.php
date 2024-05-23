<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$format = $this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i');

$tickets = [];
foreach ($this->tickets as $sortedTickets) {
	$tickets = array_merge($tickets, $sortedTickets);
}
?>
<div class="com-dpcalendar-booking__tickets dp-print-hide">
	<?php foreach ($tickets as $ticket) { ?>
		<h4 class="com-dpcalendar-booking__ticket-heading dp-heading">
			<?php if ($ticket->price_label) { ?>
				<?php echo $ticket->price_label; ?>
			<?php } ?>
			<?php if ($ticket->price) { ?>
				[<?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($ticket->price); ?>]
			<?php } ?>
		</h4>
		<div class="com-dpcalendar-booking__event-info dp-control">
			<div class="dp-control__label">
				<?php echo $this->translate('COM_DPCALENDAR_EVENT'); ?>
			</div>
			<div class="dp-control__input">
				<a href="<?php echo $this->router->getEventRoute($ticket->event_id, $ticket->event_calid); ?>" class="dp-link">
					<?php echo $ticket->event_title; ?> [
					<?php echo $this->dateHelper->getDateStringFromEvent(
						$ticket,
						$this->params->get('event_date_format', 'd.m.Y'),
						$this->params->get('event_time_format', 'H:i')
					); ?>
					]
				</a>
			</div>
		</div>
		<div class="com-dpcalendar-booking__fields">
			<?php foreach ($this->ticketFormFields[$ticket->id] as $field) { ?>
				<?php echo $field->renderField(['class' => \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::getFieldName($field, true)]); ?>
			<?php } ?>
		</div>
	<?php } ?>
	<?php if ($this->captchaField) { ?>
		<?php echo $this->captchaField->renderField(['class' => \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::getFieldName($this->captchaField, true)]); ?>
	<?php } ?>
</div>
