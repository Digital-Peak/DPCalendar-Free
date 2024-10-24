<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;

if ($this->bookingId) {
	return;
}

$bookings = [];
foreach ($this->events as $event) {
	foreach ($event->tickets as $ticket) {
		if (in_array($ticket->state, [0, 2, 3]) && $ticket->user_id && $ticket->user_id == $this->user->id) {
			$booking = $this->getModel()->getItem($ticket->booking_id);
			if (!$booking) {
				continue;
			}

			$booking->event_title   = $ticket->event_title;
			$bookings[$booking->id] = $booking;
		}
	}
}

if ($bookings === []) {
	return;
}
?>
<div class="com-dpcalendar-bookingform__existing-booking">
	<?php foreach ($bookings as $booking) { ?>
		<?php echo Text::Sprintf(
			'COM_DPCALENDAR_VIEW_BOOKINGFORM_AVAILABLE_BOOKING',
			$booking->event_title,
			RouteHelper::getBookingRoute($booking)
		); ?>
	<?php } ?>
</div>
