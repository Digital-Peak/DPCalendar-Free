<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('event_form_show_map', 1) || $this->params->get('map_provider', 'openstreetmap') == 'none') {
	return;
}
?>
<div class="com-dpcalendar-eventform__map">
	<div class="dp-map"
		 style="width: <?php echo $this->params->get('event_form_map_width', '100%'); ?>; height: <?php echo $this->params->get('event_form_map_height', '250px'); ?>"
		 data-zoom="<?php echo $this->params->get('event_form_map_zoom', 6); ?>"
		 data-latitude="<?php echo $this->params->get('event_form_map_latitude', 47); ?>"
		 data-longitude="<?php echo $this->params->get('event_form_map_longitude', 4); ?>"
		 data-ask-consent="<?php echo $this->params->get('map_ask_consent'); ?>">
	</div>
</div>
