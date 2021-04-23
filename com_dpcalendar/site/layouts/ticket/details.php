<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$ticket   = $displayData['ticket'];
$event    = $displayData['event'];
$params   = $displayData['params'];
$hasPrice = $ticket->price && $ticket->price != '0.00';

$imageUrl = $params->get('invoice_logo');
if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
	$imageUrl = trim(JUri::root(), '/') . '/' . trim($imageUrl, '/');
}

$fields = \DPCalendar\Helper\FieldsOrder::getTicketFields($ticket, $params, \Joomla\CMS\Factory::getApplication());
?>
<div class="dp-ticket-details">
	<?php if ($params->get('show_header', true)) { ?>
		<table style="width:100%">
			<tr>
				<td><?php echo nl2br($params->get('invoice_address')); ?></td>
				<td>
					<?php if ($imageUrl) { ?>
						<img src="<?php echo $imageUrl; ?>"/>
					<?php } ?>
				</td>
			</tr>
		</table>
	<?php } ?>
	<h3 style="border-bottom: 1px solid #eee"><?php echo $event->title; ?></h3>
	<table style="width:100%">
		<tr>
			<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_DATE'); ?></td>
			<td style="width:70%">
				<?php echo strip_tags($displayData['dateHelper']->getDateStringFromEvent(
					$event,
					$params->get('event_date_format', 'd.m.Y'),
					$params->get('event_time_format', 'H:i')
				)); ?>
			</td>
		</tr>
		<?php if ($event->locations) { ?>
			<tr>
				<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_LOCATION'); ?></td>
				<td style="width:70%">
					<?php $formattedLocations = []; ?>
					<?php foreach ($event->locations as $index => $location) { ?>
						<?php $formattedLocations[] = \DPCalendar\Helper\Location::format($location); ?>
					<?php } ?>
					<?php echo implode(', ', $formattedLocations); ?>
				</td>
			</tr>
		<?php } ?>
	</table>
	<h3 style="border-bottom: 1px solid #eee"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_TICKET_DETAILS'); ?></h3>
	<table style="width:100%">
		<tr>
			<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?></td>
			<td style="width:70%"><?php echo $ticket->uid; ?></td>
		</tr>
		<?php if ($event->price && key_exists($ticket->type, $event->price->label) && $event->price->label[$ticket->type]) { ?>
			<tr>
				<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_TICKET_FIELD_TYPE_LABEL'); ?></td>
				<td style="width:70%"><?php echo $event->price->label[$ticket->type] . ' ' . ($event->price->description[$ticket->type] ?: ''); ?></td>
			</tr>
		<?php } ?>
		<?php foreach ($fields as $field) { ?>
			<tr>
				<td style="width:30%"><?php echo $displayData['translator']->translate($field->dpDisplayLabel); ?></td>
				<td style="width:70%"><?php echo $displayData['translator']->translate($field->dpDisplayContent); ?></td>
			</tr>
		<?php } ?>
	</table>
</div>
