<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->event->locations) {
	return;
}

if ($this->params->get('event_show_map', '1')) {
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
}
?>
<div class="com-dpcalendar-event__locations">
	<?php if ($this->params->get('event_show_map', '1') == '1' && $this->params->get('event_show_location', '2') == '1') { ?>
		<div class="dp-location">
			<div class="dp-map" data-zoom="<?php echo $this->params->get('event_map_zoom', 4); ?>"></div>
			<?php foreach ($this->event->locations as $location) { ?>
				<div class="dp-location__details"
					 data-latitude="<?php echo $location->latitude; ?>"
					 data-longitude="<?php echo $location->longitude; ?>"
					 data-title="<?php echo $location->title; ?>"
					 data-description="&lt;a href='<?php echo DPCalendarHelperRoute::getLocationRoute($location); ?>'&gt;<?php echo $location->title; ?>&lt;/a&gt;"
					 data-color="<?php echo \DPCalendar\Helper\Location::getColor($location); ?>">
				</div>
			<?php } ?>
		</div>
	<?php } ?>
	<?php if ($this->params->get('event_show_location', '2') == '2') { ?>
		<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_LOCATION_INFORMATION'); ?></h3>
		<?php foreach ($this->event->locations as $location) { ?>
			<div class="dp-location">
				<h4 class="dp-heading dp-heading_small">
					<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LOCATION]); ?>
					<?php if ($this->user->authorise('core.edit', 'com_dpcalendar')) { ?>
						<a href="<?php echo $this->router->getLocationFormRoute($location->id, JUri::getInstance()); ?>"
						   class="dp-link dp-location__edit-link">
							<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EDIT]); ?>
						</a>
					<?php } ?>
					<a href="<?php echo $this->router->getLocationRoute($location); ?>"
					   class="dp-link dp-location__detail-link" name="<?php echo 'dp-location-' . $location->id; ?>">
						<?php echo $location->title; ?>
					</a>
				</h4>
				<?php if ($this->params->get('event_show_map', '1') == '1') { ?>
					<div class="dp-map" data-zoom="<?php echo $this->params->get('event_map_zoom', 4); ?>"></div>
				<?php } ?>
				<div class="dp-location__details"
					 data-latitude="<?php echo $location->latitude; ?>"
					 data-longitude="<?php echo $location->longitude; ?>"
					 data-title="<?php echo $location->title; ?>"
					 data-description="&lt;a href='<?php echo DPCalendarHelperRoute::getLocationRoute($location); ?>'&gt;<?php echo $location->title; ?>&lt;/a&gt;"
					 data-color="<?php echo \DPCalendar\Helper\Location::getColor($location); ?>">
					<?php if ($location->country) { ?>
						<dl class="dp-description">
							<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'); ?></dt>
							<dd class="dp-description__description dp-location__country"><?php echo $location->country; ?></dd>
						</dl>
					<?php } ?>
					<?php if ($location->province) { ?>
						<dl class="dp-description">
							<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL'); ?></dt>
							<dd class="dp-description__description dp-location__province"><?php echo $location->province; ?></dd>
						</dl>
					<?php } ?>
					<?php if ($location->city) { ?>
						<dl class="dp-description">
							<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL'); ?></dt>
							<dd class="dp-description__description dp-location__city">
								<?php if ($this->params->get('location_format', 'format_us') == 'format_us') { ?>
									<?php echo $location->city . ' ' . $location->zip; ?>
								<?php } else { ?>
									<?php echo $location->zip . ' ' . $location->city; ?>
								<?php } ?>
							</dd>
						</dl>
					<?php } ?>
					<?php if ($location->street) { ?>
						<dl class="dp-description">
							<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL'); ?></dt>
							<dd class="dp-description__description dp-location__street">
								<?php if ($this->params->get('location_format', 'format_us') == 'format_us') { ?>
									<?php echo $location->number . ' ' . $location->street; ?>
								<?php } else { ?>
									<?php echo $location->street . ' ' . $location->number; ?>
								<?php } ?>
							</dd>
						</dl>
					<?php } ?>
					<?php if ($location->url) { ?>
						<dl class="dp-description">
							<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_URL_LABEL'); ?></dt>
							<dd class="dp-description__description dp-location__url">
								<a href="<?php echo $location->url; ?>" class="dp-link"><?php echo $location->url; ?></a>
							</dd>
						</dl>
					<?php } ?>
				</div>
				<?php echo trim(implode("\n",
					$this->app->triggerEvent('onContentBeforeDisplay', ['com_dpcalendar.location', &$location, &$params, 0]))); ?>
				<?php echo JHTML::_('content.prepare', $location->description); ?>
			</div>
		<?php } ?>
	<?php } ?>
</div>
