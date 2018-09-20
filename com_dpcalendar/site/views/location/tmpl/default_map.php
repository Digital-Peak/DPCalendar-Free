<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('location_show_map', 1)) {
	return;
}
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);

$description = '<a href="' . $this->router->getLocationRoute($this->location) . '">' . $this->location->title . '</a>';
?>
<div class="com-dpcalendar-location__map dp-location">
	<div class="dp-map dp-location__details"
	     data-width="<?php echo $this->params->get('location_map_width', '100%'); ?>"
	     data-height="<?php echo $this->params->get('location_map_height', '250px'); ?>"
	     data-zoom="<?php echo $this->params->get('location_map_zoom', 4); ?>"
	     data-latitude="<?php echo $this->location->latitude; ?>"
	     data-longitude="<?php echo $this->location->longitude; ?>"
	     data-title="<?php echo $this->location->title; ?>"
	     data-description="<?php echo $this->escape($description); ?>"
	     data-color="<?php echo \DPCalendar\Helper\Location::getColor($this->location); ?>">
	</div>
</div>
