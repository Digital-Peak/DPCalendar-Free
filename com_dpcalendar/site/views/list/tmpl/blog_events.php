<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\HTML\Block\Icon;

$this->translator->translateJS('COM_DPCALENDAR_CONFIRM_DELETE');
?>
<div class="com-dpcalendar-blog__events">
	<?php foreach ($this->events as $event) { ?>
		<?php $this->displayData['event'] = $event; ?>
		<?php $calendar = DPCalendarHelper::getCalendar($event->catid); ?>
		<div class="com-dpcalendar-blog__event dp-event dp-event_<?php echo $event->ongoing_start_date ? ($event->ongoing_end_date ? 'started' : 'finished') : 'future'; ?>"
			 data-calid="<?php echo $calendar->id; ?>">
			<?php echo $this->loadTemplate('events_title'); ?>
			<?php if ($this->params->get('list_show_display_events') && $event->displayEvent->afterDisplayTitle) { ?>
				<div class="dp-event__display-after-title"><?php echo $event->displayEvent->afterDisplayTitle; ?></div>
			<?php } ?>
			<div class="dp-event__details">
				<?php if ($calendar->canEdit || $calendar->canDelete || ($calendar->canEditOwn && $event->created_by == $this->user->id)) { ?>
					<div class="dp-event__actions">
						<?php if ($calendar->canEdit || ($calendar->canEditOwn && $event->created_by == $this->user->id)) { ?>
							<a href="<?php echo $this->router->getEventFormRoute($event->id, $this->returnPage); ?>" class="dp-link"
								aria-label="<?php echo $this->translate('JACTION_EDIT'); ?>">
								<?php echo $this->layoutHelper->renderLayout(
									'block.icon',
									['icon' => Icon::EDIT, 'title' => $this->translate('JACTION_EDIT')]
								); ?>
							</a>
						<?php } ?>
						<?php if ($calendar->canDelete || ($calendar->canEditOwn && $event->created_by == $this->user->id)) { ?>
							<a href="<?php echo $this->router->getEventDeleteRoute($event->id, $this->returnPage); ?>" class="dp-link dp-link_delete"
								aria-label="<?php echo $this->translate('JACTION_DELETE'); ?>">
								<?php echo $this->layoutHelper->renderLayout(
									'block.icon',
									['icon' => Icon::DELETE, 'title' => $this->translate('JACTION_DELETE')]
								); ?>
							</a>
						<?php } ?>
					</div>
				<?php } ?>
				<div class="dp-event__date">
					<?php echo $this->layoutHelper->renderLayout(
						'block.icon',
						['icon' => Icon::CLOCK, 'title' => $this->translate('COM_DPCALENDAR_DATE')]
					); ?>
					<?php echo $this->dateHelper->getDateStringFromEvent(
						$event,
						$this->params->get('event_date_format'),
						$this->params->get('event_time_format')
					); ?>
				</div>
				<?php if ($event->rrule) { ?>
					<div class="dp-event__rrule">
						<?php echo $this->layoutHelper->renderLayout(
							'block.icon',
							['icon'  => Icon::RECURRING, 'title' => $this->translate('COM_DPCALENDAR_BOOKING_FIELD_SERIES_LABEL')]
						); ?>
						<?php echo $this->dateHelper->transformRRuleToString($event->rrule, $event->start_date); ?>
					</div>
				<?php } ?>
				<div class="dp-event__calendar">
					<?php echo $this->layoutHelper->renderLayout(
						'block.icon',
						['icon' => Icon::CALENDAR, 'title' => $this->translate('COM_DPCALENDAR_CALENDAR')]
					); ?>
					<?php echo $calendar != null ? $calendar->title : $event->catid; ?>
				</div>
				<?php echo $this->loadTemplate('events_locations'); ?>
				<?php echo $this->loadTemplate('events_prices'); ?>
				<?php if ($this->params->get('list_show_hits', 1)) { ?>
					<div class="dp-event__hits">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::BULLSEYE,]); ?>
						<?php echo $event->hits . ' ' . $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_HITS'); ?>
					</div>
				<?php } ?>
			</div>
			<?php if ($this->params->get('list_show_booking', 1) && \DPCalendar\Helper\Booking::openForBooking($event)) { ?>
				<a href="<?php echo $this->router->getBookingFormRouteFromEvent($event, $this->returnPage); ?>" class="dp-link dp-link_cta dp-button">
					<?php echo $this->layoutHelper->renderLayout(
						'block.icon',
						['icon' => Icon::PLUS, 'title' => $this->translate('COM_DPCALENDAR_BOOK')]
					); ?>
					<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_TO_BOOK_TEXT'); ?>
				</a>
			<?php } ?>
			<?php echo $this->loadTemplate('events_image'); ?>
			<?php if ($this->params->get('list_show_display_events') && $event->displayEvent->beforeDisplayContent) { ?>
				<div class="dp-event__display-before-content"><?php echo $event->displayEvent->beforeDisplayContent; ?></div>
			<?php } ?>
			<div class="dp-event__description"><?php echo $event->truncatedDescription; ?></div>
			<?php if ($this->params->get('list_show_display_events') && $event->displayEvent->afterDisplayContent) { ?>
				<div class="dp-event__display-after-content"><?php echo $event->displayEvent->afterDisplayContent; ?></div>
			<?php } ?>
			<?php echo $this->layoutHelper->renderLayout('schema.event', $this->displayData); ?>
		</div>
	<?php } ?>
</div>