<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-locations__details">
	<?php foreach ($this->locations as $location) { ?>
		<?php $description = '<a href="' . $this->router->getLocationRoute($location) . '">' . $location->title . '</a>'; ?>
		<div class=dp-location">
			<h3 class="dp-heading">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LOCATION]); ?>
				<a href="<?php echo $this->router->getLocationRoute($location, JUri::getInstance()); ?>" class="dp-link">
					<?php echo $location->title; ?>
				</a>
			</h3>
			<div class="dp-location__details"
			     data-latitude="<?php echo $location->latitude; ?>"
			     data-longitude="<?php echo $location->longitude; ?>"
			     data-title="<?php echo $location->title; ?>"
			     data-description="<?php echo $this->escape($description); ?>"
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
			<?php echo trim(implode("\n",
				$this->app->triggerEvent('onContentBeforeDisplay', ['com_dpcalendar.location', &$location, &$params, 0]))); ?>
			<?php echo JHTML::_('content.prepare', $location->description); ?>
		</div>
	<?php } ?>
</div>
