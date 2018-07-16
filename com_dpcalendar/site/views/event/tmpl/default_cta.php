<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!\DPCalendar\Helper\Booking::openForBooking($this->event)) {
	return;
}
?>
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
			$endDate->format($this->params->get('event_date_format', 'm.d.Y'), true),
			$endDate->format('H:i') != '00:00' ? $endDate->format($this->params->get('event_time_format', 'h:i a'), true) : ''
		); ?>
	</div>
</div>

