<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;

$format = $this->params->get('event_date_format', 'd.m.Y') . ' ' . $this->params->get('event_time_format', 'H:i');
$fields = array_column((array)$this->params->get('bookings_fields', []), 'field') ;
?>
<div class="com-dpcalendar-bookings__table">
	<table class="dp-table">
		<thead>
		<tr>
			<?php foreach ($this->headers as $index => $header) { ?>
				<th class="dp-table__cell<?php echo $fields[$index] === 'price' ? ' dp-table__cell_center' : ''; ?>">
					<?php echo $header; ?>
				</th>
			<?php } ?>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->bookings as $booking) { ?>
			<tr class="dp-booking">
				<?php foreach ($booking->preparedData as $index => $value) { ?>
					<td data-column="<?php echo $this->headers[$index]; ?>" class="dp-booking__<?php echo $fields[$index]; ?> dp-table__cell
						<?php echo $fields[$index] === 'tickets_count' ? ' dp-table__cell_center' : ''; ?>
						<?php echo $fields[$index] === 'price' ? ' dp-table__cell_right' : ''; ?>
						">
						<?php if ($fields[$index] === 'uid') { ?>
							<a href="<?php echo $this->router->getBookingRoute($booking); ?>" class="dp-link dp-booking__link">
								<?php echo StringHelper::abridge($value, 15, 5); ?>
							</a>
						<?php } elseif ($fields[$index] === 'tickets_count') { ?>
							<a href="<?php echo RouteHelper::getTicketsRoute($booking->id); ?>" class="dp-link dp-booking__tickets-link">
								<?php echo $value; ?>
							</a>
						<?php } elseif ($fields[$index] === 'book_date') { ?>
							<?php echo $this->dateHelper->getDate($value)->format($format, true); ?>
						<?php } else { ?>
							<?php echo $value; ?>
						<?php } ?>
					</td>
				<?php } ?>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>
