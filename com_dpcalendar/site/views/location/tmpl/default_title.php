<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<h3 class="com-dpcalendar-location__title dp-heading">
	<a href="<?php echo \DPCalendar\Helper\Location::getMapLink($this->location); ?>" class="dp-link" target="_blank">
		<?php echo $this->location->title; ?>
	</a>
</h3>
