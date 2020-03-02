<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$params->get('show_map', 1)) {
	return;
}

$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
?>
<div class="mod-dpcalendar-mini__map dp-map"
	 data-width="<?php echo $params->get('map_width', '100%'); ?>"
	 data-height="<?php echo $params->get('map_height', '350px'); ?>"
	 data-zoom="<?php echo $params->get('map_zoom', 4); ?>"
	 data-latitude="<?php echo $params->get('map_lat', 47); ?>"
	 data-longitude="<?php echo $params->get('map_long', 4); ?>">
</div>
