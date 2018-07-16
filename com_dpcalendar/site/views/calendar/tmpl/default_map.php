<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('show_map', 1)) {
	return;
}

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
?>
<div class="com-dpcalendar-calendar__map dp-map"
	 data-width="<?php echo $this->params->get('map_width', '100%'); ?>"
	 data-height="<?php echo $this->params->get('map_height', '350px'); ?>"
	 data-zoom="<?php echo $this->params->get('map_zoom', 6); ?>"
	 data-latitude="<?php echo $this->params->get('map_lat', 47); ?>"
	 data-longitude="<?php echo $this->params->get('map_long', 4); ?>">
</div>
