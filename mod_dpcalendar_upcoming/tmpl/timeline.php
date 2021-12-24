<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\HTML\Block\Icon;
use Joomla\CMS\Helper\ModuleHelper;

if (!$events) {
	echo $translator->translate('MOD_DPCALENDAR_UPCOMING_NO_EVENT_TEXT');

	return;
}

require ModuleHelper::getLayoutPath('mod_dpcalendar_upcoming', '_scripts');
?>
<div class="mod-dpcalendar-upcoming mod-dpcalendar-upcoming-timeline mod-dpcalendar-upcoming-<?php echo $module->id; ?> dp-locations"
	 data-popup="<?php echo $params->get('show_as_popup', 0); ?>">
	<div class="mod-dpcalendar-upcoming-timeline__events">
		<?php foreach ($groupedEvents as $groupHeading => $events) { ?>
			<?php if ($groupHeading) { ?>
				<p class="mod-dpcalendar-upcoming-timeline__heading dp-group-heading"><?php echo $groupHeading; ?></p>
			<?php } ?>
			<?php foreach ($events as $index => $event) { ?>
				<?php $displayData['event'] = $event; ?>
				<?php $startDate = $dateHelper->getDate($event->start_date, $event->all_day); ?>
				<div class="mod-dpcalendar-upcoming-timeline__event dp-event dp-event_<?php echo $event->ongoing_start_date ? ($event->ongoing_end_date ? 'started' : 'finished') : 'future'; ?>">
					<div class="mod-dpcalendar-upcoming-timeline__dot"></div>
					<div class="mod-dpcalendar-upcoming-timeline__information">
						<h3 class="mod-dpcalendar-upcoming-timeline__title" style="background-color: #<?php echo $event->color; ?>">
							<?php if ($event->state == 3) { ?>
								<span class="dp-event_canceled">[<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_CANCELED'); ?>]</span>
							<?php } ?>
							<a href="<?php echo $event->realUrl; ?>" class="dp-event-url dp-link"
							   style="color: #<?php echo DPCalendarHelper::getOppositeBWColor($event->color); ?>">
								<?php echo $event->title; ?>
							</a>
							<?php if ($params->get('show_display_events') && $event->displayEvent->afterDisplayTitle) { ?>
								<div class="dp-event-display-after-title"><?php echo $event->displayEvent->afterDisplayTitle; ?></div>
							<?php } ?>
						</h3>
						<?php if (($params->get('show_location') || $params->get('show_map')) && isset($event->locations) && $event->locations) { ?>
							<div class="mod-dpcalendar-upcoming-timeline__location">
								<?php if ($params->get('show_location')) { ?>
									<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => Icon::LOCATION]); ?>
								<?php } ?>
								<?php foreach ($event->locations as $location) { ?>
									<div class="dp-location<?php echo !$params->get('show_location') ? ' dp-location_hidden' : ''; ?>">
										<div class="dp-location__details"
											 data-latitude="<?php echo $location->latitude; ?>"
											 data-longitude="<?php echo $location->longitude; ?>"
											 data-title="<?php echo $location->title; ?>"
											 data-color="<?php echo $event->color; ?>"></div>
										<?php if ($params->get('show_location')) { ?>
											<a href="<?php echo $router->getLocationRoute($location); ?>" class="dp-location__url dp-link">
												<?php echo $location->title; ?>
											</a>
										<?php } ?>
										<div class="dp-location__description">
											<?php echo $layoutHelper->renderLayout('event.tooltip', $displayData); ?>
										</div>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
						<div class="mod-dpcalendar-upcoming-timeline__date">
							<?php echo $layoutHelper->renderLayout(
								'block.icon',
								['icon' => Icon::CLOCK, 'title' => $translator->translate('MOD_DPCALENDAR_UPCOMING_DATE')]
							); ?>
							<?php echo $dateHelper->getDateStringFromEvent($event, $params->get('date_format'), $params->get('time_format')); ?>
						</div>
						<?php if ($event->rrule) { ?>
							<div class="mod-dpcalendar-upcoming-timeline__rrule">
								<?php echo $layoutHelper->renderLayout(
									'block.icon',
									['icon' => Icon::RECURRING, 'title' => $translator->translate('MOD_DPCALENDAR_UPCOMING_SERIES')]
								); ?>
								<?php echo $dateHelper->transformRRuleToString($event->rrule, $event->start_date); ?>
							</div>
						<?php } ?>
						<?php if ($params->get('show_price') && $event->price) { ?>
							<?php foreach ($event->price->value as $key => $value) { ?>
								<?php $discounted = Booking::getPriceWithDiscount($value, $event); ?>
								<div class="mod-dpcalendar-upcoming-timeline__price dp-event-price">
									<?php echo $layoutHelper->renderLayout(
										'block.icon',
										['icon' => Icon::MONEY, 'title' => $translator->translate('MOD_DPCALENDAR_UPCOMING_PRICES')]
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
						<?php if ($event->images->image_intro) { ?>
							<div class="mod-dpcalendar-upcoming-timeline__image">
								<figure class="dp-figure">
									<a href="<?php echo $event->realUrl; ?>" class="dp-event-url dp-link">
										<img class="dp-image" src="<?php echo $event->images->image_intro; ?>"
											aria-label="<?php echo $event->images->image_intro_alt; ?>"
											alt="<?php echo $event->images->image_intro_alt; ?>"
											loading="lazy" <?php echo $event->images->image_intro_dimensions; ?>>
									</a>
									<?php if ($event->images->image_intro_caption) { ?>
										<figcaption class="dp-figure__caption"><?php echo $event->images->image_intro_caption; ?></figcaption>
									<?php } ?>
								</figure>
							</div>
						<?php } ?>
						<?php if ($params->get('show_booking', 1) && Booking::openForBooking($event)) { ?>
							<a href="<?php echo $router->getBookingFormRouteFromEvent($event, $return); ?>" class="dp-link dp-link_cta dp-button">
								<?php echo $layoutHelper->renderLayout('block.icon', ['icon' => Icon::PLUS]); ?>
								<?php echo $translator->translate('MOD_DPCALENDAR_UPCOMING_BOOK'); ?>
							</a>
						<?php } ?>
						<?php if ($params->get('show_display_events') && $event->displayEvent->beforeDisplayContent) { ?>
							<div class="dp-event-display-before-content"><?php echo $event->displayEvent->beforeDisplayContent; ?></div>
						<?php } ?>
						<div class="mod-dpcalendar-upcoming-timeline__description">
							<?php echo $event->truncatedDescription; ?>
						</div>
						<?php if ($params->get('show_display_events') && $event->displayEvent->afterDisplayContent) { ?>
							<div class="dp-event-display-after-content"><?php echo $event->displayEvent->afterDisplayContent; ?></div>
						<?php } ?>
						<?php $displayData['event'] = $event; ?>
						<?php echo $layoutHelper->renderLayout('schema.event', $displayData); ?>
					</div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
	<?php if ($params->get('show_map')) { ?>
		<div class="mod-dpcalendar-upcoming-timeline__map dp-map"
			 style="width: <?php echo $params->get('map_width', '100%'); ?>; height: <?php echo $params->get('map_height', '350px'); ?>"
			 data-zoom="<?php echo $params->get('map_zoom', 4); ?>"
			 data-latitude="<?php echo $params->get('map_lat', 47); ?>"
			 data-longitude="<?php echo $params->get('map_long', 4); ?>"
			 data-ask-consent="<?php echo $params->get('map_ask_consent'); ?>">
		</div>
	<?php } ?>
</div>
