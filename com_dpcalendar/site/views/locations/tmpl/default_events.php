<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('locations_show_upcoming_events', 1)) {
	return;
}
?>
<div class="com-dpcalendar-locations__events">
	<h2 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_UPCOMING_EVENTS'); ?></h2>
	<div class="com-dpcalendar-locations__event-list">
		<?php foreach ($this->events as $event) { ?>
			<?php $date = $this->dateHelper->getDateStringFromEvent($event, $this->params->get('date_format'), $this->params->get('time_format')); ?>
			<div class="dp-event" style="border-color: #<?php echo $event->color ?>">
				<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-link dp-event__link">
					<?php echo $event->title; ?>
				</a>
				<div class="dp-event__locations">
					<?php foreach ($event->locations as $location) { ?>
						<div class="dp-event__location">
							<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LOCATION]); ?>
							<a href="<?php echo '#dp-location-' . $location->id; ?>" class="dp-link">
								<?php echo $location->title; ?>
							</a>
						</div>
					<?php } ?>
				</div>
				<div class="dp-event__date">
					<?php echo $this->layoutHelper->renderLayout(
						'block.icon',
						['icon' => \DPCalendar\HTML\Block\Icon::CLOCK, 'title' => $this->translate('COM_DPCALENDAR_DATE')]
					); ?>
					<?php echo $date; ?>
				</div>
				<?php if ($event->rrule) { ?>
					<div class="dp-event__rrule">
						<?php echo $this->layoutHelper->renderLayout(
							'block.icon',
							[
								'icon'  => \DPCalendar\HTML\Block\Icon::RECURRING,
								'title' => $this->translate('COM_DPCALENDAR_BOOKING_FIELD_SERIES_LABEL')
							]
						); ?>
						<?php echo $this->dateHelper->transformRRuleToString($event->rrule, $event->start_date); ?>
					</div>
				<?php } ?>
				<?php $this->displayData['event'] = $event; ?>
				<?php echo $this->layoutHelper->renderLayout('schema.event', $this->displayData); ?>
			</div>
		<?php } ?>
	</div>
</div>
