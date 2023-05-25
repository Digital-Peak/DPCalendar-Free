<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!class_exists('DPCalendarControllerBookingForm') || !$this->params->get('event_show_booking_form')) {
	return;
}
?>
<div class="com-dpcalendar-event__booking-form<?php echo $this->params->get('event_show_booking_form') == 1 ? ' dp-toggle_hidden' : ''; ?>">
	<?php (new DPCalendarControllerBookingForm())->display(); ?>
</div>
