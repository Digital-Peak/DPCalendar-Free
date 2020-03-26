<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$events) {
	echo JText::_('MOD_DPCALENDAR_UPCOMING_NO_EVENT_TEXT');

	return;
}

require JModuleHelper::getLayoutPath('mod_dpcalendar_upcoming', '_scripts');
?>
<div class="mod-dpcalendar-upcoming mod-dpcalendar-upcoming-panel mod-dpcalendar-upcoming-<?php echo $module->id; ?> dp-locations">
	<div class="mod-dpcalendar-upcoming-panel__events">
		<?php foreach ($groupedEvents as $groupHeading => $events) { ?>
			<?php if ($groupHeading) { ?>
				<div class="mod-dpcalendar-upcoming-panel__group">
				<p class="mod-dpcalendar-upcoming-panel__heading dp-group-heading"><?php echo $groupHeading; ?></p>
			<?php } ?>
			<?php foreach ($events as $index => $event) { ?>
				<?php $displayData['event'] = $event; ?>
				<?php $startDate = $dateHelper->getDate($event->start_date, $event->all_day); ?>
				<div class="mod-dpcalendar-upcoming-panel__event dp-event dp-event_<?php echo $event->ongoing_start_date ? 'started' : 'future'; ?>">
					<?php if ($event->images->image_intro) { ?>
						<div class="mod-dpcalendar-upcoming-panel__image">
							<figure class="dp-figure">
								<img class="dp-image" src="<?php echo $event->images->image_intro; ?>"
									 alt="<?php echo $event->images->image_intro_alt; ?>">
								<?php if ($event->images->image_intro_caption) { ?>
									<figcaption class="dp-figure__caption"><?php echo $event->images->image_intro_caption; ?></figcaption>
								<?php } ?>
							</figure>
						</div>
					<?php } ?>
					<div class="mod-dpcalendar-upcoming-panel__information">
						<a href="<?php echo $event->realUrl; ?>" class="dp-event-url dp-link"><?php echo $event->title; ?></a>
						<?php if ($params->get('show_display_events') && $event->displayEvent->afterDisplayTitle) { ?>
							<div class="dp-event-display-after-title"><?php echo $event->displayEvent->afterDisplayTitle; ?></div>
						<?php } ?>
						<?php if (($params->get('show_location') || $params->get('show_map')) && isset($event->locations) && $event->locations) { ?>
							<?php foreach ($event->locations as $location) { ?>
								<span class="mod-dpcalendar-upcoming-panel__location dp-location">
								<span class="dp-location__details"
									  data-latitude="<?php echo $location->latitude; ?>"
									  data-longitude="<?php echo $location->longitude; ?>"
									  data-title="<?php echo $location->title; ?>"
									  data-color="<?php echo $event->color; ?>"></span>
								<?php if ($params->get('show_location')) { ?>
									<a href="<?php echo $router->getLocationRoute($location); ?>"
									   class="mod-dpcalendar-upcoming-panel__url dp-location__url dp-link">
										<?php echo $location->title; ?>
									</a>
								<?php } ?>
								<span class="dp-location__description">
									<?php echo $layoutHelper->renderLayout('event.tooltip', $displayData); ?>
								</span>
							</span>
							<?php } ?>
						<?php } ?>
						<div class="mod-dpcalendar-upcoming-panel__date">
							<?php echo $layoutHelper->renderLayout(
								'block.icon',
								['icon' => \DPCalendar\HTML\Block\Icon::CLOCK, 'title' => $translator->translate('MOD_DPCALENDAR_UPCOMING_DATE')]
							); ?>
							<?php echo $dateHelper->getDateStringFromEvent($event, $params->get('date_format'), $params->get('time_format')); ?>
						</div>
						<?php if ($event->rrule) { ?>
							<div class="mod-dpcalendar-upcoming-panel__rrule">
								<?php echo $layoutHelper->renderLayout(
									'block.icon',
									[
										'icon'  => \DPCalendar\HTML\Block\Icon::RECURRING,
										'title' => $translator->translate('MOD_DPCALENDAR_UPCOMING_SERIES')
									]
								); ?>
								<?php echo $dateHelper->transformRRuleToString($event->rrule, $event->start_date); ?>
							</div>
						<?php } ?>
						<?php if ($params->get('show_price') && $event->price) { ?>
							<?php foreach ($event->price->value as $key => $value) { ?>
								<?php $discounted = \DPCalendar\Helper\Booking::getPriceWithDiscount($value, $event); ?>
								<div class="mod-dpcalendar-upcoming-panel__price dp-event-price">
									<?php echo $layoutHelper->renderLayout(
										'block.icon',
										[
											'icon'  => \DPCalendar\HTML\Block\Icon::MONEY,
											'title' => $translator->translate('MOD_DPCALENDAR_UPCOMING_PRICES')
										]
									); ?>
									<span class="dp-event-price__label">
									<?php echo $event->price->label[$key] ?: $translator->translate('MOD_DPCALENDAR_UPCOMING_PRICES'); ?>
								</span>
									<span class="dp-event-price__regular<?php echo $discounted != $value ? ' dp-event-price__regular_has-discount' : ''; ?>">
									<?php echo $value === '' ? '' : DPCalendarHelper::renderPrice($value); ?>
								</span>
									<?php if ($discounted != $value) { ?>
										<span class="dp-event-price__discount"><?php echo DPCalendarHelper::renderPrice($discounted); ?></span>
									<?php } ?>
									<span class="dp-event-price__description">
									<?php echo $event->price->description[$key]; ?>
								</span>
								</div>
							<?php } ?>
						<?php } ?>
						<?php if ($params->get('show_booking', 1) && \DPCalendar\Helper\Booking::openForBooking($event)) { ?>
							<a href="<?php echo $router->getBookingFormRouteFromEvent($event, $return); ?>" class="dp-link dp-link_cta dp-button">
								<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PLUS]); ?>
								<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_BOOK'); ?>
							</a>
						<?php } ?>
						<?php if ($params->get('show_display_events') && $event->displayEvent->beforeDisplayContent) { ?>
							<div class="dp-event-display-before-content"><?php echo $event->displayEvent->beforeDisplayContent; ?></div>
						<?php } ?>
						<div class="mod-dpcalendar-upcoming-panel__description">
							<?php echo $event->truncatedDescription; ?>
						</div>
						<?php if ($params->get('show_display_events') && $event->displayEvent->afterDisplayContent) { ?>
							<div class="dp-event-display-after-content"><?php echo $event->displayEvent->afterDisplayContent; ?></div>
						<?php } ?>
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
	<?php if ($params->get('show_map')) { ?>
		<div class="mod-dpcalendar-upcoming-panel__map dp-map"
			 data-width="<?php echo $params->get('map_width', '100%'); ?>"
			 data-height="<?php echo $params->get('map_height', '350px'); ?>"
			 data-zoom="<?php echo $params->get('map_zoom', 4); ?>"
			 data-latitude="<?php echo $params->get('map_lat', 47); ?>"
			 data-longitude="<?php echo $params->get('map_long', 4); ?>">
		</div>
	<?php } ?>
</div>
