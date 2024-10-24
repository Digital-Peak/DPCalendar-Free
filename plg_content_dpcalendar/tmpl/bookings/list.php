<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

if (!$bookings) {
	return;
}

$document->loadStyleFile('bookings.css', 'plg_content_dpcalendar');
?>
<div class="plg-content-dpcalendar-bookings">
    <div class="plg-content-dpcalendar-bookings__header">
        <?php echo $translator->translate('PLG_CONTENT_DPCALENDAR_BOOKINGS_TITLE'); ?>
    </div>
    <ul class="plg-content-dpcalendar-contact-events__authors dp-bookings dp-list dp-list_unordered">
        <?php foreach ($bookings as $booking) { ?>
            <li class="dp-booking">
                <a href="<?php echo $router->getBookingRoute($booking); ?>" class="dp-booking__link dp-link">
                    <?php echo $booking->uid; ?>
                </a>
                <?php if ($booking->price) { ?>
                    <span class="dp-booking__price">
                        <?php echo $translator->translate('PLG_CONTENT_DPCALENDAR_BOOKINGS_PRICE') . ': ' . DPCalendarHelper::renderPrice($booking->price); ?>
                    </span>
                <?php } ?>
                <span class="dp-booking__state">
                    <?php echo $translator->translate('PLG_CONTENT_DPCALENDAR_BOOKINGS_STATE') . ': ' . Booking::getStatusLabel($booking); ?>
                </span>
                <span class="dp-booking__tickets">
                    <?php foreach ($booking->tickets as $ticket) { ?>
                        <span class="dp-booking__ticket">
                            <?php echo $ticket->event_title; ?> [
                            <?php echo $dateHelper->getDateStringFromEvent(
                                $ticket,
                                $params->get('event_date_format', 'd.m.Y') ,
                                $params->get('event_time_format', 'H:i')
                            ); ?>]
                        </span>
                    <?php } ?>
                </span>
            </li>
        <?php } ?>
    </ul>
</div>
