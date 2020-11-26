<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$params->get('show_map', 0)) {
	return;
}
?>
<div class="mod-dpcalendar-mini__map dp-map"
	 style="width: <?php echo $params->get('map_width', '100%'); ?>; height: <?php echo $params->get('map_height', '350px'); ?>"
	 data-zoom="<?php echo $params->get('map_zoom', 4); ?>"
	 data-latitude="<?php echo $params->get('map_lat', 47); ?>"
	 data-longitude="<?php echo $params->get('map_long', 4); ?>"
	 data-ask-consent="<?php echo $params->get('map_ask_consent'); ?>">
</div>
