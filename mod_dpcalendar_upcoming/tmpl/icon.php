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
<div class="mod-dpcalendar-upcoming mod-dpcalendar-upcoming-icon mod-dpcalendar-upcoming-<?php echo $module->id; ?>">
	<?php foreach ($groupedEvents as $groupHeading => $events) { ?>
		<?php if ($groupHeading) { ?>
			<div class="mod-dpcalendar-upcoming-icon__group">
			<p class="mod-dpcalendar-upcoming-icon__heading dp-group-heading"><?php echo $groupHeading; ?></p>
		<?php } ?>
		<?php foreach ($events as $index => $event) { ?>
			<?php $startDate = $dateHelper->getDate($event->start_date, $event->all_day); ?>
			<div class="mod-dpcalendar-upcoming-icon__event">
				<div class="mod-dpcalendar-upcoming-icon__information">
					<?php echo $layoutHelper->renderLayout(
						'block.icon',
						[
							'icon'  => \DPCalendar\HTML\Block\Icon::CALENDAR,
							'title' => $translator->translate('MOD_DPCALENDAR_UPCOMING_BLOG_CALENDAR')
						]
					); ?>
					<a href="<?php echo $event->realUrl; ?>" class="dp-event-url dp-link"><?php echo $event->title; ?></a>
					<?php if ($params->get('show_location') && isset($event->locations) && $event->locations) { ?>
						<?php foreach ($event->locations as $location) { ?>
							<span class="mod-dpcalendar-upcoming-icon__location"
								  data-latitude="<?php echo $location->latitude; ?>"
								  data-longitude="<?php echo $location->longitude; ?>"
								  data-title="<?php echo $location->title; ?>">
								<a href="<?php echo $router->getLocationRoute($location); ?>" class="dp-link">
									<?php echo $location->title; ?>
								</a>
							</span>
						<?php } ?>
					<?php } ?>
					<span class="mod-dpcalendar-upcoming-icon__date">
						<?php echo $dateHelper->getDateStringFromEvent($event, $params->get('date_format'), $params->get('time_format')); ?>
					</span>
				</div>
				<?php if ($event->images->image_intro) { ?>
					<div class="mod-dpcalendar-upcoming-icon__image">
						<figure class="dp-figure">
							<img class="dp-image" src="<?php echo $event->images->image_intro; ?>"
								 alt="<?php echo $event->images->image_intro_alt; ?>">
							<?php if ($event->images->image_intro_caption) { ?>
								<figcaption class="dp-figure__caption"><?php echo $event->images->image_intro_caption; ?></figcaption>
							<?php } ?>
						</figure>
					</div>
				<?php } ?>
				<?php if ($params->get('show_booking', 1) && \DPCalendar\Helper\Booking::openForBooking($event)) { ?>
					<a href="<?php echo $router->getBookingFormRouteFromEvent($event, $return); ?>" class="dp-link dp-link_cta dp-button">
						<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PLUS]); ?>
						<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_BOOK'); ?>
					</a>
				<?php } ?>
				<div class="mod-dpcalendar-upcoming-icon__description">
					<?php echo $event->truncatedDescription; ?>
				</div>
				<?php $displayData['event'] = $event; ?>
				<?php echo $layoutHelper->renderLayout('schema.event', $displayData); ?>
			</div>
		<?php } ?>
		<?php if ($groupHeading) { ?>
			</div>
		<?php } ?>
	<?php } ?>
</div>
