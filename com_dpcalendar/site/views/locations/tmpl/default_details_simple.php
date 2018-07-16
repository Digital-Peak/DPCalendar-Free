<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-locations__details">
	<?php foreach ($this->locations as $location) { ?>
		<?php $description = '<a href="' . $this->router->getLocationRoute($location) . '">' . $location->title . '</a>'; ?>
		<div class=dp-location">
			<div class="dp-location__details"
			     data-latitude="<?php echo $location->latitude; ?>"
			     data-longitude="<?php echo $location->longitude; ?>"
			     data-title="<?php echo $location->title; ?>"
			     data-description="<?php echo $this->escape($description); ?>"
			     data-color="<?php echo \DPCalendar\Helper\Location::getColor($location); ?>">
			</div>
		</div>
	<?php } ?>
</div>
