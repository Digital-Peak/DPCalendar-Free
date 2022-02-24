<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use DPCalendar\HTML\Block\Icon;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

// Booking is disabled when the message is an empty string
if ($this->noBookingMessage === '') {
	return;
}

$waiting = $this->event->capacity !== null && $this->event->capacity_used >= $this->event->capacity && $this->event->booking_waiting_list;
if ($this->originalEvent && $this->originalEvent->booking_series == 2) {
	$waiting = false;
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
		   class="dp-button dp-button_cta">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PLUS]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_TO_BOOK_TEXT' . ($waiting ? '_WAITING' : '')); ?>
		</a>
		<div class="dp-event-cta__end-date">
			<?php $endDate = Booking::getRegistrationEndDate($this->event); ?>
			<?php echo Text::sprintf(
				'COM_DPCALENDAR_VIEW_EVENT_REGISTRATION_END_TEXT',
				$endDate->format($this->params->get('event_date_format', 'd.m.Y'), true),
				$endDate->format('H:i') != '00:00' ? $endDate->format($this->params->get('event_time_format', 'h:i a'), true) : ''
			); ?>
		</div>
	</div>
<?php } ?>
