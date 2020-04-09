<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('location_show_map', 1) || $this->params->get('map_provider', 'openstreetmap') == 'none') {
	return;
}

$description = '<a href="' . $this->router->getLocationRoute($this->location) . '">' . $this->location->title . '</a>';
?>
<div class="com-dpcalendar-location__map dp-location">
	<div class="dp-map dp-location__details"
		 data-width="<?php echo $this->params->get('location_map_width', '100%'); ?>"
		 data-height="<?php echo $this->params->get('location_map_height', '250px'); ?>"
		 data-zoom="<?php echo $this->params->get('location_map_zoom', 10); ?>"
		 data-latitude="<?php echo $this->location->latitude; ?>"
		 data-longitude="<?php echo $this->location->longitude; ?>"
		 data-title="<?php echo $this->location->title; ?>"
		 data-description="<?php echo $this->escape($description); ?>"
		 data-color="<?php echo $this->location->color; ?>"
		 data-ask-consent="<?php echo $this->params->get('map_ask_consent'); ?>">
	</div>
</div>
