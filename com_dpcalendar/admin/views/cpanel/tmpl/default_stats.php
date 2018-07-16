<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-cpanel__stats">
	<h3 class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS'); ?>
	</h3>
	<div class="dp-information-container">
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_EVENTS_TOTAL'); ?>: </span>
			<span class="dp-information__content"><?php echo $this->totalEvents; ?></span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_YEAR'); ?>: </span>
			<span class="dp-information__content"><?php echo $this->totalBookings['year']['total']; ?></span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_INCOME'); ?>: </span>
			<span class="dp-information__content"><?php echo \DPCalendar\Helper\DPCalendarHelper::renderPrice($this->totalBookings['year']['price']); ?></span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_MONTH'); ?>: </span>
			<span class="dp-information__content"><?php echo $this->totalBookings['month']['total']; ?></span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_INCOME'); ?>: </span>
			<span class="dp-information__content"><?php echo \DPCalendar\Helper\DPCalendarHelper::renderPrice($this->totalBookings['month']['price']); ?></span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_WEEK'); ?>: </span>
			<span class="dp-information__content"><?php echo $this->totalBookings['week']['total']; ?></span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_INCOME'); ?>: </span>
			<span class="dp-information__content"><?php echo \DPCalendar\Helper\DPCalendarHelper::renderPrice($this->totalBookings['week']['price']); ?></span>
		</div>
	</div>
</div>
