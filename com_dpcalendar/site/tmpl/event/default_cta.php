<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\Uri\Uri;

// Booking is disabled when the message is an empty string
if ($this->noBookingMessage === '') {
	return;
}

$waiting = $this->event->capacity !== null && $this->event->capacity_used >= $this->event->capacity && $this->event->booking_waiting_list;
if ($this->originalEvent && ($this->originalEvent->booking_series == 2 || $this->originalEvent->capacity_used < $this->originalEvent->capacity || !$this->event->booking_waiting_list)) {
	$waiting = false;
}
// Reset for waiting list
if ((int)$this->event->waiting_list_count > 0) {
	$waiting = true;
}
?>
<?php if ($this->noBookingMessage) { ?>
	<div class="com-dpcalendar-event__cta com-dpcalendar-event__cta_disabled dp-print-hide">
		<?php echo $this->noBookingMessage; ?>
	</div>
	<?php return; ?>
<?php } ?>
<?php if ($this->params->get('event_show_booking_form') != 2) { ?>
	<div class="com-dpcalendar-event__cta dp-event-cta dp-print-hide">
		<a href="<?php echo $this->router->getBookingFormRouteFromEvent($this->event, Uri::getInstance()->toString()); ?>"
			class="dp-link dp-link_cta">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::BOOK]); ?>
			<span class="dp-link__text">
				<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_TO_BOOK_TEXT' . ($waiting ? '_WAITING' : '')); ?>
			</span>
		</a>
		<div class="dp-event-cta__end-date">
		<?php $endDate = Booking::getRegistrationEndDate($this->originalEvent && $this->originalEvent->booking_series == 1 ? $this->originalEvent : $this->event); ?>
			<?php echo sprintf(
				$this->translate('COM_DPCALENDAR_VIEW_EVENT_REGISTRATION_END_TEXT'),
				$endDate->format($this->params->get('event_date_format', 'd.m.Y'), true),
				$endDate->format('H:i') !== '00:00' ? $endDate->format($this->params->get('event_time_format', 'h:i a'), true) : ''
			); ?>
		</div>
	</div>
<?php } ?>
