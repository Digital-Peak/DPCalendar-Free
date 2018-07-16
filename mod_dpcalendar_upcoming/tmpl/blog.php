<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$events) {
	echo JText::_('MOD_DPCALENDAR_UPCOMING_NO_EVENT_TEXT');

	return;
}

require JModuleHelper::getLayoutPath('mod_dpcalendar_upcoming', '_scripts');

?>
<div class="mod-dpcalendar-upcoming mod-dpcalendar-upcoming-blog mod-dpcalendar-upcoming-<?php echo $module->id; ?>">
	<?php foreach ($groupedEvents as $groupHeading => $events) { ?>
		<?php $calendar = DPCalendarHelper::getCalendar($event->catid); ?>
		<?php if ($groupHeading) { ?>
			<div class="mod-dpcalendar-upcoming-blog__group">
			<p class="mod-dpcalendar-upcoming-blog__heading dp-group-heading"><?php echo $groupHeading; ?></p>
		<?php } ?>
		<?php foreach ($events as $index => $event) { ?>
			<?php $displayData['event'] = $event; ?>
			<div class="mod-dpcalendar-upcoming-blog__event">
				<h3 class="mod-dpcalendar-upcoming-blog__heading">
					<a href="<?php echo $event->realUrl; ?>" target="_parent" class="dp-event-url dp-link">
						<?php echo $event->title; ?>
					</a>
				</h3>
				<div class="dp-grid">
					<div class="mod-dpcalendar-upcoming-blog__information">
						<?php if (\DPCalendar\Helper\Booking::openForBooking($event)) { ?>
							<a href="<?php echo $router->getBookingFormRouteFromEvent($event, $return); ?>" class="dp-link">
								<?php echo $layoutHelper->renderLayout(
									'block.icon',
									[
										'icon'  => \DPCalendar\HTML\Block\Icon::PLUS,
										'title' => $translator->translate('MOD_DPCALENDAR_UPCOMING_BLOG_BOOK')
									]
								); ?>
							</a>
						<?php } ?>
						<?php if ($calendar->canEdit || ($calendar->canEditOwn && $event->created_by == $user->id)) { ?>
							<a href="<?php echo $router->getEventFormRoute($event->id, $return); ?>" class="dp-link">
								<?php echo $layoutHelper->renderLayout(
									'block.icon',
									['icon' => \DPCalendar\HTML\Block\Icon::EDIT, 'title' => $translator->translate('JACTION_EDIT')]
								); ?>
							</a>
						<?php } ?>
						<?php if ($calendar->canDelete || ($calendar->canEditOwn && $event->created_by == $user->id)) { ?>
							<a href="<?php echo $router->getEventDeleteRoute($event->id, $return); ?>" class="dp-link">
								<?php echo $layoutHelper->renderLayout(
									'block.icon',
									['icon' => \DPCalendar\HTML\Block\Icon::DELETE, 'title' => $translator->translate('JACTION_DELETE')]
								); ?>
							</a>
						<?php } ?>
						<div class="mod-dpcalendar-upcoming-blog__date">
							(<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_BLOG_DATE'); ?>:
							<?php echo $dateHelper->getDateStringFromEvent($event, $params->get('date_format'), $params->get('time_format')); ?>)
						</div>
						<div class="mod-dpcalendar-upcoming-blog__calendar">
							<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_BLOG_CALENDAR'); ?>:
							<?php echo $calendar != null ? $calendar->title : $event->catid; ?>
						</div>
						<?php if ($params->get('show_location') && !empty($event->locations)) { ?>
							<div class="mod-dpcalendar-upcoming-blog__location">
								<?php foreach ($event->locations as $location) { ?>
									<span class="dp-location">
										<span class="dp-location__details"
											  data-latitude="<?php echo $location->latitude; ?>"
											  data-longitude="<?php echo $location->longitude; ?>"
											  data-title="<?php echo $location->title; ?>"
											  data-color="<?php echo $event->color; ?>"></span>
										<a href="<?php echo $router->getLocationRoute($location); ?>" class="dp-location__url dp-link">
											<?php echo $location->title; ?>
										</a>
										<span class="dp-location__description">
											<?php echo $layoutHelper->renderLayout('event.tooltip', $displayData); ?>
										</span>
									</span>
								<?php } ?>
							</div>
						<?php } ?>
						<div class="mod-dpcalendar-upcoming-blog__capacity">
							<?php if ($event->capacity === null) { ?>
								<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_BLOG_CAPACITY'); ?>:
								<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_BLOG_CAPACITY_UNLIMITED'); ?>
							<?php } ?>
							<?php if ($event->capacity > 0) { ?>
								<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_BLOG_CAPACITY'); ?>:
								<?php echo ($event->capacity - $event->capacity_used) . '/' . (int)$event->capacity; ?>
							<?php } ?>
						</div>
						<div class="mod-dpcalendar-upcoming-blog__price">
							<?php echo $translator->translate($event->price ? 'MOD_DPCALENDAR_UPCOMING_BLOG_PAID_EVENT' : 'MOD_DPCALENDAR_UPCOMING_BLOG_FREE_EVENT'); ?>
						</div>
					</div>
					<?php if ($event->images->image_intro) { ?>
						<div class="mod-dpcalendar-upcoming-blog__image">
							<figure class="dp-figure">
								<img class="dp-image" src="<?php echo $event->images->image_intro; ?>"
									 alt="<?php echo $event->images->image_intro_alt; ?>">
								<?php if ($event->images->image_intro_caption) { ?>
									<figcaption class="dp-figure__caption"><?php echo $event->images->image_intro_caption; ?></figcaption>
								<?php } ?>
							</figure>
						</div>
					<?php } ?>
				</div>
				<div class="mod-dpcalendar-upcoming-blog__description">
					<?php echo $event->truncatedDescription; ?>
				</div>
				<?php echo $layoutHelper->renderLayout('schema.event', $displayData); ?>
			</div>
		<?php } ?>
		<?php if ($groupHeading) { ?>
			</div>
		<?php } ?>
	<?php } ?>
</div>
