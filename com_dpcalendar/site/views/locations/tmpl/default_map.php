<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('locations_show_map', 1)) {
	return;
}
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
?>
<div class="com-dpcalendar-locations__map">
	<div class="dp-map"
	     data-width="<?php echo $this->params->get('locations_map_width', '100%'); ?>"
	     data-height="<?php echo $this->params->get('locations_map_height', '250px'); ?>"
	     data-latitude="<?php echo $this->params->get('locations_map_latitude', 47); ?>"
	     data-longitude="<?php echo $this->params->get('locations_map_longitude', 4); ?>"
	     data-zoom="<?php echo $this->params->get('locations_map_zoom', 4); ?>">
	</div>
</div>
