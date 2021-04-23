<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use DPCalendar\Helper\FieldsOrder;

$booking = $displayData['booking'];
$tickets = $displayData['tickets'];
$params  = $displayData['params'];
$user    = JFactory::getUser($booking->user_id);

$plugin = null;
if ($processor = $booking->processor ? substr($booking->processor, 0, strpos($booking->processor, '-')) : '') {
	$plugin = JPluginHelper::getPlugin('dpcalendarpay', $processor);
	JFactory::getLanguage()->load('plg_dpcalendarpay_' . $processor, JPATH_PLUGINS . '/dpcalendarpay/' . $processor);
}
$hasPrice                = $booking->price && $booking->price != '0.00';
$booking->amount_tickets = 0;
$eventOptions            = [];
$orderedOptions          = $booking->options ? explode(',', $booking->options) : [];
foreach ($tickets as $ticket) {
	if ($ticket->booking_id == $booking->id) {
		$booking->amount_tickets++;
	}

	if (!$ticket->event_options) {
		continue;
	}

	$ticketEventOptions = $ticket->event_options;
	if (is_string($ticketEventOptions)) {
		$ticketEventOptions = json_decode($ticketEventOptions);
	}

	// Prepare the options
	foreach ($ticketEventOptions as $key => $option) {
		$key = preg_replace('/\D/', '', $key);

		foreach ($orderedOptions as $o) {
			list($eventId, $type, $amount) = explode('-', $o);

			if ($eventId != $ticket->event_id || $type != $key || !empty($eventOptions[$eventId][$type])) {
				continue;
			}

			if (!array_key_exists($eventId, $eventOptions)) {
				$eventOptions[$eventId] = [];
			}
			$eventOptions[$eventId][$type] = ['price' => $option->price, 'label' => $option->label, 'amount' => $amount];
		}
	}
}

$imageUrl = $params->get('invoice_logo');
if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
	$imageUrl = trim(JUri::root(), '/') . '/' . trim($imageUrl, '/');
}

$fields = FieldsOrder::getBookingFields($booking, $params, \Joomla\CMS\Factory::getApplication());
?>
<div class="dp-booking-invoice">
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
	<?php if ($content = $params->get('invoice_content_top')) { ?>
		<p><?php echo $displayData['translator']->translate(nl2br($content)); ?></p>
	<?php } ?>
	<?php if ($hasPrice) { ?>
		<h3 style="border-bottom: 1px solid #eee"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_INVOICE_DETAILS'); ?></h3>
		<table style="width:100%">
			<tr>
				<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_NUMBER'); ?></td>
				<td style="width:70%"><?php echo $booking->uid; ?></td>
			</tr>
			<tr>
				<td><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_DATE'); ?></td>
				<?php $format = $params->get('event_date_format', 'd.m.Y') . ' ' . $params->get('event_time_format', 'H:i'); ?>
				<td><?php echo $displayData['dateHelper']->getDate($booking->book_date)->format($format, true); ?></td>
			</tr>
			<tr>
				<td><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></td>
				<td>
					<?php echo DPCalendarHelper::renderPrice($booking->price, $params->get('currency_symbol', '$')); ?>
					<?php if ($booking->tax && $booking->tax != '0.00') { ?>
						(<?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_TAX_LABEL'); ?>
						<?php echo DPCalendarHelper::renderPrice($booking->tax, $params->get('currency_symbol', '$')); ?>)
					<?php } ?>
				</td>
			</tr>
			<tr>
				<td><?php echo $displayData['translator']->translate('JSTATUS'); ?></td>
				<td><?php echo \DPCalendar\Helper\Booking::getStatusLabel($booking); ?></td>
			</tr>
			<?php if ($plugin) { ?>
				<tr>
					<td><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_PAYMENT_PROVIDER_LABEL'); ?></td>
					<td><?php echo $displayData['translator']->translate('PLG_DPCALENDARPAY_' . $plugin->name . '_TITLE'); ?></td>
				</tr>
			<?php } ?>
		</table>
	<?php } ?>
	<h3 style="border-bottom: 1px solid #eee"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_BOOKING_DETAILS'); ?></h3>
	<table style="width:100%">
		<?php foreach ($fields as $field) { ?>
			<tr>
				<td style="width:30%"><?php echo $displayData['translator']->translate($field->dpDisplayLabel); ?></td>
				<td style="width:70%"><?php echo $displayData['translator']->translate($field->dpDisplayContent); ?></td>
			</tr>
		<?php } ?>
	</table>
	<?php if ($params->get('invoice_include_tickets', 1)) { ?>
		<h3 style="border-bottom: 1px solid #eee"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_TICKET_DETAILS'); ?></h3>
		<?php foreach ($tickets as $ticket) { ?>
			<table style="width:100%; border-collapse:collapse">
				<tr>
					<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL'); ?></td>
					<td style="width:70%"><?php echo $ticket->uid; ?></td>
				</tr>
				<tr>
					<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_EVENT'); ?></td>
					<td style="width:70%">
						<?php echo $ticket->event_title; ?> [
						<?php echo $displayData['dateHelper']->getDateStringFromEvent(
							$ticket,
							$params->get('event_date_format'),
							$params->get('event_time_format')
						); ?>
						]
					</td>
				</tr>
				<?php if (!empty($ticket->event_prices)) { ?>
					<?php $prices = !is_string($ticket->event_prices) ? $ticket->event_prices : json_decode($ticket->event_prices); ?>
					<?php if (!empty($prices->label[$ticket->type])) { ?>
						<tr>
							<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_TICKET_FIELD_TYPE_LABEL'); ?></td>
							<td style="width:70%"><?php echo $prices->label[$ticket->type]; ?></td>
						</tr>
					<?php } ?>
				<?php } ?>
				<?php if ($hasPrice && $ticket->price && $ticket->price != '0.00') { ?>
					<tr>
						<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></td>
						<td style="width:70%"><?php echo DPCalendarHelper::renderPrice($ticket->price, $params->get('currency_symbol', '$')); ?></td>
					</tr>
				<?php } ?>
				<?php foreach (FieldsOrder::getTicketFields($ticket, $params, \Joomla\CMS\Factory::getApplication()) as $field) { ?>
					<tr>
						<td style="width:30%"><?php echo $displayData['translator']->translate($field->dpDisplayLabel); ?></td>
						<td style="width:70%"><?php echo $field->dpDisplayContent; ?></td>
					</tr>
				<?php } ?>
				<tr>
					<td></td>
					<td></td>
				</tr>
			</table>
			<?php if (!empty($eventOptions)) { ?>
				<h3 style="border-bottom: 1px solid #eee"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_OPTIONS'); ?></h3>
				<table style="width:100%; border-collapse:collapse">
					<?php foreach ($eventOptions as $eventId => $options) { ?>
						<?php foreach ($options as $option) { ?>
							<tr>
								<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_OPTION'); ?></td>
								<td style="width:70%"><?php echo $option['label']; ?></td>
							</tr>
							<tr>
								<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></td>
								<td style="width:70%"><?php echo DPCalendarHelper::renderPrice($option['price']); ?></td>
							</tr>
							<tr>
								<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_AMOUNT_LABEL'); ?></td>
								<td style="width:70%"><?php echo $option['amount']; ?></td>
							</tr>
						<?php } ?>
					<?php } ?>
				</table>
			<?php } ?>
		<?php } ?>
	<?php } ?>
	<?php if ($content = $params->get('invoice_content_bottom')) { ?>
		<p><?php echo $displayData['translator']->translate(nl2br($content)); ?></p>
	<?php } ?>
</div>
