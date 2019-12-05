<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$booking = $displayData['booking'];
$tickets = $displayData['tickets'];
$params  = $displayData['params'];
$user    = JFactory::getUser($booking->user_id);
$plugin  = $booking->processor ? JPluginHelper::getPlugin('dpcalendarpay', $booking->processor) : null;
if ($plugin) {
	JFactory::getLanguage()->load('plg_dpcalendarpay_' . $booking->processor, JPATH_PLUGINS . '/dpcalendarpay/' . $booking->processor);
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

	// Prepare the options
	foreach (json_decode($ticket->event_options) as $key => $option) {
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

$fields   = array();
$fields[] = (object)array('id' => 'name', 'name' => 'name');
$fields[] = (object)array('id' => 'email', 'name' => 'email');
$fields[] = (object)array('id' => 'telephone', 'name' => 'telephone');
$fields[] = (object)array('id' => 'country', 'name' => 'country', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL');
$fields[] = (object)array('id' => 'province', 'name' => 'province', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL');
$fields[] = (object)array('id' => 'city', 'name' => 'city', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL');
$fields[] = (object)array('id' => 'zip', 'name' => 'zip', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL');
$fields[] = (object)array('id' => 'street', 'name' => 'street', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL');
$fields[] = (object)array('id' => 'number', 'name' => 'number', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL');

// The fields are not fetched, load them
if (!isset($booking->jcfields)) {
	JPluginHelper::importPlugin('content');
	$booking->text = '';
	JFactory::getApplication()->triggerEvent('onContentPrepare', array('com_dpcalendar.booking', &$booking, &$params, 0));
}

$fields = array_merge($fields, $booking->jcfields);

\DPCalendar\Helper\DPCalendarHelper::sortFields($fields, $params->get('booking_fields_order', new stdClass()));
foreach ($fields as $key => $field) {
	if (!$params->get('booking_show_' . $field->name, 1)) {
		unset($fields[$key]);
	}

	$label = 'COM_DPCALENDAR_BOOKING_FIELD_' . strtoupper($field->name) . '_LABEL';
	if (isset($field->label)) {
		$label = $field->label;
	}

	$content = '';
	if (property_exists($booking, $field->name)) {
		$content = $booking->{$field->name};
	}
	if (property_exists($field, 'value')) {
		$content = $field->value;
	}

	if (!$content) {
		unset($fields[$key]);
		continue;
	}

	$field->dpDisplayLabel   = $label;
	$field->dpDisplayContent = $content;
}
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
	<?php if ($hasPrice) { ?>
		<h3 style="border-bottom: 1px solid #eee"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_INVOICE_DETAILS'); ?></h3>
		<table style="width:100%">
			<tr>
				<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_NUMBER'); ?></td>
				<td style="width:70%"><?php echo $booking->uid; ?></td>
			</tr>
			<tr>
				<td><?php echo $displayData['translator']->translate('COM_DPCALENDAR_INVOICE_DATE'); ?></td>
				<?php $format = $params->get('event_date_format', 'm.d.Y') . ' ' . $params->get('event_time_format', 'g:i a'); ?>
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
				<td><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_TICKETS_LABEL'); ?></td>
				<td><?php echo $booking->amount_tickets; ?></td>
			</tr>
			<tr>
				<td><?php echo $displayData['translator']->translate('JSTATUS'); ?></td>
				<td><?php echo \DPCalendar\Helper\Booking::getStatusLabel($booking); ?></td>
			</tr>
			<?php if ($plugin) { ?>
				<tr>
					<td><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_PROCESSOR_LABEL'); ?></td>
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
					<?php echo $ticket->event_title . ' [' . $displayData['dateHelper']->getDateStringFromEvent($ticket,
							$params->get('event_date_format'),
							$params->get('event_time_format')) . ']'; ?>
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
			<tr>
				<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL'); ?></td>
				<td style="width:70%"><?php echo $ticket->name; ?></td>
			</tr>
			<?php if ($ticket->price && $ticket->price != '0.00') { ?>
				<tr>
					<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?></td>
					<td style="width:70%"><?php echo DPCalendarHelper::renderPrice($ticket->price, $params->get('currency_symbol', '$')); ?></td>
				</tr>
			<?php } ?>
			<?php if ($params->get('ticket_show_seat', 1)) { ?>
				<tr>
					<td style="width:30%"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL'); ?></td>
					<td style="width:70%"><?php echo $ticket->seat; ?></td>
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
</div>
