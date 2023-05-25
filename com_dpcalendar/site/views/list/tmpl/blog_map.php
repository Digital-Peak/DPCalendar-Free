<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('list_show_map', 1) || $this->params->get('map_provider', 'openstreetmap') == 'none') {
	return;
}
?>
<div class="com-dpcalendar-blog__map dp-map"
	 style="width: <?php echo $this->params->get('list_map_width', '100%'); ?>; height: <?php echo $this->params->get('list_map_height', '350px'); ?>"
	 data-zoom="<?php echo $this->params->get('list_map_zoom', 4); ?>"
	 data-latitude="<?php echo $this->params->get('list_map_lat', 47); ?>"
	 data-longitude="<?php echo $this->params->get('list_map_long', 4); ?>"
	 data-ask-consent="<?php echo $this->params->get('map_ask_consent'); ?>">
</div>
