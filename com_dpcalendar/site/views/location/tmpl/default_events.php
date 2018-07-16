<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('location_show_upcoming_events', 1)) {
	return;
}
?>
<div class="com-dpcalendar-location__events">
	<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_UPCOMING_EVENTS'); ?></h3>
	<div class="com-dpcalendar-location__event-list">
		<?php foreach ($this->events as $event) { ?>
			<?php $date = $this->dateHelper->getDateStringFromEvent($event, $this->params->get('date_format'), $this->params->get('time_format')); ?>
			<p class="dp-event" style="border-color: #<?php echo $event->color ?>">
				<span class="dp-event__date"><?php echo $date; ?></span>
				<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-link dp-event__link">
					<?php echo $event->title; ?>
				</a>
				<?php $this->displayData['event'] = $event; ?>
				<?php echo $this->layoutHelper->renderLayout('schema.event', $this->displayData); ?>
			</p>
		<?php } ?>
	</div>
</div>
