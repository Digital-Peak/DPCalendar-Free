<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-locations__details">
	<?php foreach ($this->locations as $location) { ?>
		<?php $description = '<a href="' . $this->router->getLocationRoute($location) . '">' . $location->title . '</a>'; ?>
		<div class=dp-location" id="<?php echo 'dp-location-' . $location->id; ?>">
			<div class="dp-location__details"
				 data-latitude="<?php echo $location->latitude; ?>"
				 data-longitude="<?php echo $location->longitude; ?>"
				 data-title="<?php echo $location->title; ?>"
				 data-description="<?php echo $this->escape($description); ?>"
				 data-color="<?php echo $location->color; ?>">
			</div>
		</div>
	<?php } ?>
</div>
