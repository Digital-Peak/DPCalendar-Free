<?php

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

$ticket = $this->ticket;
$event  = $this->event;
?>
<div class="com-dpcalendar-ticket__content dp-ticket">
	<h3 class="dp-heading"><?php echo $event->title; ?></h3>
	<dl class="dp-description">
		<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_DATE'); ?></dt>
		<dd class="dp-description__description">
			<?php echo $this->dateHelper->getDateStringFromEvent(
				$event,
				$this->params->get('event_date_format', 'd.m.Y'),
				$this->params->get('event_time_format', 'H:i')
			); ?>
		</dd>
	</dl>
	<?php if (isset($event->locations) && $event->locations) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?></dt>
			<dd class="dp-description__description">
				<?php foreach ($event->locations as $location) { ?>
					<div class="dp-location">
						<a href="<?php echo $this->router->getLocationRoute($location); ?>" class="dp-link">
							<?php echo $location->title; ?>
						</a>
					</div>
				<?php } ?>
			</dd>
		</dl>
	<?php } ?>
	<h3 class="dp-heading"><?php echo $this->translate('JDETAILS'); ?></h3>
	<dl class="dp-description">
		<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?></dt>
		<dd class="dp-description__description"><?php echo $ticket->uid; ?></dd>
	</dl>
	<?php if ($ticket->price_label) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_TYPE_LABEL'); ?></dt>
			<dd class="dp-description__description">
				<span class="dp-ticket__type-label"><?php echo $ticket->price_label; ?></span>
				<span class="dp-ticket__type-description"><?php echo $ticket->price_description; ?></span>
			</dd>
		</dl>
	<?php } ?>
	<?php if ($ticket->price) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></dt>
			<dd class="dp-description__description">
				<?php echo DPCalendarHelper::renderPrice($ticket->price); ?>
			</dd>
		</dl>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('JSTATUS'); ?></dt>
			<dd class="dp-description__description">
				<?php echo Booking::getStatusLabel($ticket); ?>
			</dd>
		</dl>
	<?php } ?>
	<?php foreach ($this->ticketFields as $field) { ?>
		<dl class="dp-description dp-field-<?php echo $field->id; ?>">
			<dt class="dp-description__label"><?php echo $this->translate($field->dpDisplayLabel); ?></dt>
			<dd class="dp-description__description"><?php echo $field->dpDisplayContent; ?></dd>
		</dl>
	<?php } ?>
</div>
