<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$params = $this->params;
if (!$params->get('location_show_map', 1) || $params->get('map_provider', 'openstreetmap') == 'none') {
	return;
}

$description = '<a href="' . $this->router->getLocationRoute($this->location) . '">' . $this->location->title . '</a>';
?>
<div class="com-dpcalendar-location__map dp-location">
	<div class="dp-map dp-location__details"
		 style="width: <?php echo $params->get('location_map_width', '100%'); ?>; height: <?php echo $params->get('location_map_height', '250px'); ?>"
		 data-zoom="<?php echo $params->get('location_map_zoom', 10); ?>"
		 data-latitude="<?php echo $this->location->latitude; ?>"
		 data-longitude="<?php echo $this->location->longitude; ?>"
		 data-title="<?php echo $this->location->title; ?>"
		 data-description="<?php echo $this->escape($description); ?>"
		 data-color="<?php echo $this->location->color; ?>"
		 data-ask-consent="<?php echo $params->get('map_ask_consent'); ?>">
	</div>
</div>
