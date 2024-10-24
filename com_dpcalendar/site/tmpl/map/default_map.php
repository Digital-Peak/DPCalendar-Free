<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();
?>
<div class="com-dpcalendar-map__map dp-map"
	style="width: <?php echo $this->params->get('map_view_width', '100%'); ?>; height: <?php echo $this->params->get('map_view_height', '600px'); ?>"
	data-zoom="<?php echo $this->params->get('map_view_zoom', 4); ?>"
	data-latitude="<?php echo $this->params->get('map_view_lat', 47); ?>"
	data-longitude="<?php echo $this->params->get('map_view_long', 4); ?>"
	data-ask-consent="<?php echo $this->params->get('map_ask_consent'); ?>">
</div>
