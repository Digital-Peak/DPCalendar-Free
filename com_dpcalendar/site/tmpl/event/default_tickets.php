<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use Joomla\CMS\HTML\Helpers\StringHelper;

if (!$this->params->get('event_show_tickets', 0) || !isset($this->event->tickets) || !$this->event->tickets) {
	return;
}

// Permissions
if (!$this->user->authorise('core.admin', 'com_dpcalendar') && !in_array($this->event->access_content, $this->user->getAuthorisedViewLevels())) {
	return;
}

$format  = $this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i');
$limited = $this->params->get('event_show_tickets') == '2';

$hasPrice = false;
foreach ($this->event->tickets as $ticket) {
	if ($ticket->price) {
		$hasPrice = true;
		break;
	}
}
?>
<div class="com-dpcalendar-event__tickets com-dpcalendar-event_small">
	<h<?php echo $this->heading + 2; ?> class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_TICKETS_LABEL'); ?>
	</h<?php echo $this->heading + 2; ?>>
	<table class="dp-table">
		<thead>
		<tr>
			<?php if (!$limited) { ?>
				<th><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?></th>
				<th><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE'); ?></th>
			<?php } ?>
			<th><?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?></th>
			<?php if (!$limited) { ?>
				<th><?php echo $this->translate('COM_DPCALENDAR_CREATED_DATE'); ?></th>
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
						<?php echo StringHelper::abridge($ticket->uid, 15, 5); ?>
					</td>
					<td data-column="<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE'); ?>">
						<?php echo Booking::getStatusLabel($ticket); ?>
					</td>
				<?php } ?>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL'); ?>">
					<?php echo $ticket->name; ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?>">
					<?php echo $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo','Administrator')->format([$ticket]) ?: '&nbsp;'; ?>
				</td>
				<?php if (!$limited) { ?>
					<td data-column="<?php echo $this->translate('COM_DPCALENDAR_CREATED_DATE'); ?>">
						<?php echo $this->dateHelper->getDate($ticket->created)->format($format, true); ?>
					</td>
				<?php } ?>
				<?php if (!$limited && $hasPrice) { ?>
					<td data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?>">
						<?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($ticket->price); ?>
					</td>
				<?php } ?>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>
