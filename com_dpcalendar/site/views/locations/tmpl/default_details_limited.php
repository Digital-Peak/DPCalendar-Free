<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-locations__details com-dpcalendar-locations-limited__details">
	<?php foreach ($this->locationGroups as $id => $locations) { ?>
		<div class="dp-location-group dp-location-group-<?php echo $id; ?>">
			<?php if ($id) { ?>
				<h2 class="dp-location-group__label">
					<?php echo $this->params->get('locations_output_grouping', 0) != 'country' ? $id : $locations[0]->country_code_value; ?>
				</h2>
			<?php } ?>
			<div class="dp-location-group__locations">
				<?php foreach ($locations as $index => $location) { ?>
					<?php $description = '<a href="' . $this->router->getLocationRoute($location) . '">' . $location->title . '</a>'; ?>
					<div class=dp-location" id="<?php echo 'dp-location-' . $location->id; ?>">
						<h<?php echo $id ? 3 : 2; ?> class="dp-heading">
							<span class="dp-heading__icon" style="color: #<?php echo $location->color; ?>">
								<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LOCATION]); ?>
							</span>
							<a href="<?php echo $this->router->getLocationRoute($location, JUri::getInstance()); ?>" class="dp-link">
								<?php echo $location->title; ?>
							</a>
						</h<?php echo $id ? 3 : 2; ?>>
						<div class="dp-location__details"
							 data-latitude="<?php echo $location->latitude; ?>"
							 data-longitude="<?php echo $location->longitude; ?>"
							 data-title="<?php echo $location->title; ?>"
							 data-description="<?php echo $this->escape($description); ?>"
							 data-color="<?php echo $location->color; ?>">
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
</div>
