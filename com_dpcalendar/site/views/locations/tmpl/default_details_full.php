<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-locations__details">
	<?php foreach ($this->locations as $location) { ?>
		<?php $description = '<a href="' . $this->router->getLocationRoute($location) . '">' . $location->title . '</a>'; ?>
		<div class="dp-location" id="<?php echo 'dp-location-' . $location->id; ?>">
			<h2 class="dp-heading">
				<span class="dp-heading__icon" style="color: #<?php echo $location->color; ?>">
					<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LOCATION]); ?>
				</span>
				<a href="<?php echo $this->router->getLocationRoute($location, JUri::getInstance()); ?>" class="dp-link dp-heading__link">
					<?php echo $location->title; ?>
				</a>
			</h2>
			<div class="dp-location__buttons dp-button-bar">
				<?php if ($location->params->get('access-edit')) { ?>
					<button type="button" class="dp-button dp-button-action dp-button-edit"
							data-href="<?php echo $this->router->getLocationFormRoute($location->id, JUri::getInstance()); ?>">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EDIT]); ?>
						<?php echo $this->translate('JACTION_EDIT'); ?>
					</button>
				<?php } ?>
				<?php if ($this->params->get('map_provider', 'openstreetmap') != 'none') { ?>
					<button type="button" class="dp-button dp-button-action dp-button-map-site" data-target="new"
							data-href="<?php echo \DPCalendar\Helper\Location::getMapLink($location); ?>">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::MAP]); ?>
						<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_MAP_SITE_LINK'); ?>
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EXTERNAL]); ?>
					</button>
					<button type="button" class="dp-button dp-button-action dp-button-map-directions" data-target="new"
							data-href="<?php echo \DPCalendar\Helper\Location::getDirectionsLink($location); ?>">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DIRECTIONS]); ?>
						<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_MAP_DIRECTIONS_LINK'); ?>
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EXTERNAL]); ?>
					</button>
				<?php } ?>
			</div>
			<div class="dp-location__details"
				 data-latitude="<?php echo $location->latitude; ?>"
				 data-longitude="<?php echo $location->longitude; ?>"
				 data-title="<?php echo $location->title; ?>"
				 data-description="<?php echo $this->escape($description); ?>"
				 data-color="<?php echo $location->color; ?>">
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
				<?php if ($location->city) { ?>
					<dl class="dp-description">
						<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL'); ?></dt>
						<dd class="dp-description__description dp-location__city">
							<?php if ($this->params->get('location_format', 'format_us') == 'format_us') { ?>
								<?php echo $location->city; ?>
							<?php } else { ?>
								<?php echo $location->zip . ' ' . $location->city; ?>
							<?php } ?>
						</dd>
					</dl>
				<?php } ?>
				<?php if ($location->province) { ?>
					<dl class="dp-description">
						<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL'); ?></dt>
						<dd class="dp-description__description dp-location__province">
							<?php if ($this->params->get('location_format', 'format_us') == 'format_us') { ?>
								<?php echo $location->province . ' ' . $location->zip; ?>
							<?php } else { ?>
								<?php echo $location->province; ?>
							<?php } ?>
						</dd>
					</dl>
				<?php } ?>
				<?php if ($location->country) { ?>
					<dl class="dp-description">
						<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'); ?></dt>
						<dd class="dp-description__description dp-location__country"><?php echo $location->country; ?></dd>
					</dl>
				<?php } ?>
				<?php if ($location->rooms) { ?>
					<dl class="dp-description">
						<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_ROOMS'); ?></dt>
						<dd class="dp-description__description dp-location__rooms">
							<?php foreach ($location->rooms as $room) { ?>
								<div class="dp-location__room"><?php echo $room->title; ?></div>
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
			<div class="dp-location__description">
				<?php echo trim(implode(
					"\n",
					$this->app->triggerEvent('onContentBeforeDisplay', ['com_dpcalendar.location', &$location, &$params, 0])
				)); ?>
				<?php echo JHTML::_('content.prepare', $location->description); ?>
			</div>
		</div>
	<?php } ?>
</div>
