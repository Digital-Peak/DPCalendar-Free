<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

// Booking is disabled when the message is an empty string
if ($this->noBookingMessage === '') {
	return;
}
?>
<?php if ($this->noBookingMessage) { ?>
	<div class="com-dpcalendar-event__cta com-dpcalendar-event__cta_disabled dp-print-hide">
		<?php echo $this->noBookingMessage; ?>
	</div>
	<?php return; ?>
<?php } ?>
<div class="com-dpcalendar-event__cta dp-event-cta dp-print-hide">
	<a href="<?php echo $this->router->getBookingFormRouteFromEvent($this->event, JUri::getInstance()->toString()); ?>"
	   class="dp-button dp-button_cta">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PLUS]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_TO_BOOK_TEXT'); ?>
	</a>
	<div class="dp-event-cta__end-date">
		<?php $endDate = \DPCalendar\Helper\Booking::getRegistrationEndDate($this->event); ?>
		<?php echo JText::sprintf(
			'COM_DPCALENDAR_VIEW_EVENT_REGISTRATION_END_TEXT',
			$endDate->format($this->params->get('event_date_format', 'd.m.Y'), true),
			$endDate->format('H:i') != '00:00' ? $endDate->format($this->params->get('event_time_format', 'h:i a'), true) : ''
		); ?>
	</div>
</div>

