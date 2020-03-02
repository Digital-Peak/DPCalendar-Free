<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
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

$fields   = [];
$fields[] = (object)['id' => 'name', 'name' => 'name', 'label' => 'COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL'];
$fields[] = (object)['id' => 'email', 'name' => 'email'];

if ($params->get('ticket_show_seat', 1)) {
	$fields[] = (object)['id' => 'seat', 'name' => 'seat', 'label' => 'COM_DPCALENDAR_TICKET_FIELD_SEAT_LABEL'];
}
$fields[] = (object)[
	'id'    => 'country',
	'name'  => 'country' . (!empty($ticket->country_code_value) ? '_code_value' : ''),
	'label' => 'COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'
];
$fields[] = (object)['id' => 'province', 'name' => 'province', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL'];
$fields[] = (object)['id' => 'city', 'name' => 'city', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL'];
$fields[] = (object)['id' => 'zip', 'name' => 'zip', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_ZIP_LABEL'];
$fields[] = (object)['id' => 'street', 'name' => 'street', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL'];
$fields[] = (object)['id' => 'number', 'name' => 'number', 'label' => 'COM_DPCALENDAR_LOCATION_FIELD_NUMBER_LABEL'];
$fields[] = (object)['id' => 'telephone', 'name' => 'telephone'];

// The fields are not fetched, load them
if (!isset($ticket->jcfields)) {
	JPluginHelper::importPlugin('content');
	$ticket->text = '';
	JFactory::getApplication()->triggerEvent('onContentPrepare', ['com_dpcalendar.ticket', &$ticket, &$params, 0]);
}

$fields = array_merge($fields, $ticket->jcfields);

\DPCalendar\Helper\DPCalendarHelper::sortFields($fields, $params->get('ticket_fields_order', new stdClass()));
foreach ($fields as $key => $field) {
	if (!$params->get('ticket_show_' . $field->name, 1)) {
		unset($fields[$key]);
	}

	$label = 'COM_DPCALENDAR_BOOKING_FIELD_' . strtoupper($field->name) . '_LABEL';
	if (isset($field->label)) {
		$label = $field->label;
	}

	$content = '';
	if (property_exists($ticket, $field->name)) {
		$content = $ticket->{$field->name};
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
					$params->get('event_date_format', 'm.d.Y'),
					$params->get('event_time_format', 'g:i a')
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
