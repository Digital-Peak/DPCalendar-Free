<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$params = $this->params;
if (!$params->get('locations_show_map', 1) || $params->get('map_provider', 'openstreetmap') == 'none') {
	return;
}
?>
<div class="com-dpcalendar-locations__map">
	<div class="dp-map"
		 style="width: <?php echo $params->get('locations_map_width', '100%'); ?>; height: <?php echo $params->get('locations_map_height', '250px'); ?>"
		 data-zoom="<?php echo $params->get('locations_map_zoom', 10); ?>"
		 data-latitude="<?php echo $params->get('locations_map_latitude', 47); ?>"
		 data-longitude="<?php echo $params->get('locations_map_longitude', 4); ?>"
		 data-zoom="<?php echo $params->get('locations_map_zoom', 4); ?>"
		 data-ask-consent="<?php echo $params->get('map_ask_consent'); ?>">
	</div>
</div>
