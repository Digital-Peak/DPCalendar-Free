<?php
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\HTML\Helpers\StringHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\FieldsOrder;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$format = $this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i');
?>
<a class="com-dpcalendar-booking__tickets-header" href="#">
	<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CONFIRM_TICKET_EXPAND_TEXT'); ?>
</a>
<div class="com-dpcalendar-booking__tickets">
	<?php foreach ($this->tickets as $eventId => $tickets) { ?>
		<h3 class="dp-heading">
			<span class="dp-heading__event-label"><?php echo $this->translate('COM_DPCALENDAR_EVENT'); ?>: </span>
			<span class="dp-heading__event-title"><?php echo $tickets[0]->event_title; ?></span>
			<span class="dp-heading__event-date"><?php echo $this->dateHelper->getDateStringFromEvent($tickets[0]); ?></span>
			<?php if ($tickets[0]->event_original_id == -1 && $tickets[0]->event_rrule) { ?>
				<span class="dp-heading__event-rrule">
					<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::RECURRING]); ?>
					<?php echo $this->dateHelper->transformRRuleToString($tickets[0]->event_rrule, $tickets[0]->start_date); ?>
				</span>
			<?php } ?>
		</h3>
		<?php foreach ($tickets as $ticket) { ?>
			<div class="dp-ticket">
				<dl class="dp-description">
					<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_TICKET'); ?></dt>
					<dd class="dp-description__description">
						<a href="<?php echo $this->router->getTicketRoute($ticket); ?>" title="<?php echo $ticket->uid; ?>" class="dp-link">
							<?php if ($ticket->price_label) { ?>
								<?php echo $ticket->price_label; ?>
							<?php } else { ?>
								<?php echo StringHelper::abridge($ticket->uid, 15, 5); ?>
							<?php } ?>
						</a>
					</dd>
				</dl>
				<dl class="dp-description">
					<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE'); ?></dt>
					<dd class="dp-description__description"><?php echo Booking::getStatusLabel($ticket); ?></dd>
				</dl>
				<?php if ($ticket->price) { ?>
					<dl class="dp-description">
						<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></dt>
						<dd class="dp-description__description"><?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($ticket->price); ?></dd>
					</dl>
				<?php } ?>
				<?php foreach ($this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('FieldsOrder','Administrator')->getTicketFields($ticket, $this->params, $this->app) as $field) { ?>
					<dl class="dp-description dp-field-<?php echo $field->id; ?>">
						<dt class="dp-description__label"><?php echo $this->translate($field->dpDisplayLabel); ?></dt>
						<dd class="dp-description__description"><?php echo $field->dpDisplayContent; ?></dd>
					</dl>
				<?php } ?>
			</div>
		<?php } ?>
		<?php if (!empty($this->eventOptions[$eventId])) { ?>
			<?php foreach ($this->eventOptions[$eventId] as $option) { ?>
				<div class="dp-option">
					<dl class="dp-description">
						<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_OPTION'); ?></dt>
						<dd class="dp-description__description">
							<?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($option['price']); ?>
							<?php echo $option['amount']; ?>
							<?php echo $option['label']; ?>
						</dd>
					</dl>
				</div>
			<?php } ?>
		<?php } ?>
	<?php } ?>
</div>
