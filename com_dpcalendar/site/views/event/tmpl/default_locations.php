<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Location;
use DPCalendar\HTML\Block\Icon;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

if (!$this->event->locations || !$this->params->get('event_show_location', '2')) {
	return;
}
?>
<div class="com-dpcalendar-event__locations com-dpcalendar-event_small">
	<?php if ($this->params->get('event_show_map', '1') == '1'
		&& $this->params->get('event_show_location', '2') == '1' && $this->params->get('map_provider', 'openstreetmap') != 'none') { ?>
		<div class="dp-location">
			<div class="dp-map" data-zoom="<?php echo $this->params->get('event_map_zoom', 4); ?>"
				 data-ask-consent="<?php echo $this->params->get('map_ask_consent'); ?>"></div>
			<?php foreach ($this->event->locations as $location) { ?>
				<div class="dp-location__details"
					 data-latitude="<?php echo $location->latitude; ?>"
					 data-longitude="<?php echo $location->longitude; ?>"
					 data-title="<?php echo $location->title; ?>"
					 data-description="&lt;a href='<?php echo DPCalendarHelperRoute::getLocationRoute($location); ?>'&gt;<?php echo $location->title; ?>&lt;/a&gt;"
					 data-color="<?php echo $location->color; ?>">
				</div>
			<?php } ?>
		</div>
	<?php } ?>
	<?php if ($this->params->get('event_show_location', '2') == '2') { ?>
		<h<?php echo $this->heading + 2; ?> class="dp-heading">
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_LOCATION_INFORMATION'); ?>
		</h<?php echo $this->heading + 2; ?>>
		<?php foreach ($this->event->locations as $location) { ?>
			<div class="dp-location">
				<h<?php echo $this->heading + 3; ?> class="dp-heading dp-heading_small">
					<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::LOCATION]); ?>
					<?php if ($location->params->get('access-edit')) { ?>
						<a href="<?php echo $this->router->getLocationFormRoute($location->id, Uri::getInstance()); ?>"
						   class="dp-link dp-location__edit-link">
							<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EDIT]); ?>
						</a>
					<?php } ?>
					<a href="<?php echo $this->router->getLocationRoute($location); ?>"
					   class="dp-link dp-location__detail-link" name="<?php echo 'dp-location-' . $location->id; ?>">
						<?php echo $location->title; ?>
					</a>
				</h<?php echo $this->heading + 3; ?>>
				<?php if ($this->params->get('map_provider', 'openstreetmap') != 'none') { ?>
					<div class="dp-button-bar">
						<button type="button" class="dp-button dp-button-action dp-button-map-site" data-target="new"
								data-href="<?php echo Location::getMapLink($location); ?>">
							<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::MAP]); ?>
							<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_MAP_SITE_LINK'); ?>
							<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EXTERNAL]); ?>
						</button>
						<button type="button" class="dp-button dp-button-action dp-button-map-directions" data-target="new"
								data-href="<?php echo Location::getDirectionsLink($location); ?>">
							<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DIRECTIONS]); ?>
							<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_MAP_DIRECTIONS_LINK'); ?>
							<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EXTERNAL]); ?>
						</button>
					</div>
				<?php } ?>
				<?php if ($this->params->get('event_show_map', '1') == '1' && $this->params->get('map_provider', 'openstreetmap') != 'none') { ?>
					<div class="dp-map" data-zoom="<?php echo $this->params->get('event_map_zoom', 4); ?>"
						 data-ask-consent="<?php echo $this->params->get('map_ask_consent'); ?>"></div>
				<?php } ?>
				<div class="dp-location__details"
					 data-latitude="<?php echo $location->latitude; ?>"
					 data-longitude="<?php echo $location->longitude; ?>"
					 data-title="<?php echo $location->title; ?>"
					 data-description="&lt;a href='<?php echo DPCalendarHelperRoute::getLocationRoute($location); ?>'&gt;<?php echo $location->title; ?>&lt;/a&gt;"
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
					<?php if (!empty($location->country_code_value)) { ?>
						<dl class="dp-description">
							<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'); ?></dt>
							<dd class="dp-description__description dp-location__country"><?php echo $location->country_code_value; ?></dd>
						</dl>
					<?php } ?>
					<?php if ($location->url) { ?>
						<?php $u = Uri::getInstance($location->url); ?>
						<dl class="dp-description">
							<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_URL_LABEL'); ?></dt>
							<dd class="dp-description__description dp-location__url">
								<a href="<?php echo $location->url; ?>" class="dp-link"
								   target="<?php echo $u->getHost() && Uri::getInstance()->getHost() != $u->getHost() ? '_blank' : ''; ?>">
									<?php echo $location->url; ?>
								</a>
							</dd>
						</dl>
					<?php } ?>
				</div>
				<div class="dp-location__description">
					<?php echo trim(implode(
						"\n",
						$this->app->triggerEvent('onContentBeforeDisplay', ['com_dpcalendar.location', &$location, &$params, 0])
					)); ?>
					<?php echo HTMLHelper::_('content.prepare', $location->description ?: ''); ?>
				</div>
			</div>
		<?php } ?>
	<?php } ?>
</div>
