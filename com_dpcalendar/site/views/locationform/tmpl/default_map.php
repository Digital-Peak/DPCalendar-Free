<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('location_form_show_map', 1) || $this->params->get('map_provider', 'openstreetmap') == 'none') {
	return;
}

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
?>
<div class="com-dpcalendar-locationform__map dp-map"
	 data-width="<?php echo $this->params->get('location_form_map_width', '100%'); ?>"
	 data-height="<?php echo $this->params->get('location_form_map_height', '250px'); ?>"
	 data-zoom="<?php echo $this->params->get('location_form_map_zoom', 10); ?>"
	 data-latitude="<?php echo $this->params->get('location_form_map_latitude', 47); ?>"
	 data-longitude="<?php echo $this->params->get('location_form_map_longitude', 4); ?>">
</div>
