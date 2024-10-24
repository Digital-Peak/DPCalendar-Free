<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
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
			<span class="dp-information__content">
				<?php echo $this->totalBookings['year']['total']; ?>
				[<?php echo DPCalendarHelper::renderPrice($this->totalBookings['year']['price'] ?: ''); ?>]
			</span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_MONTH'); ?>: </span>
			<span class="dp-information__content">
				<?php echo $this->totalBookings['month']['total']; ?>
				[<?php echo DPCalendarHelper::renderPrice($this->totalBookings['month']['price'] ?: ''); ?>]
			</span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_WEEK'); ?>: </span>
			<span class="dp-information__content">
				<?php echo $this->totalBookings['week']['total']; ?>
				[<?php echo DPCalendarHelper::renderPrice($this->totalBookings['week']['price'] ?: ''); ?>]
			</span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_STATS_BOOKING_TOTAL_NOTACTIVE'); ?>: </span>
			<span class="dp-information__content">
				<?php echo $this->totalBookings['notactive']['total']; ?>
				[<?php echo DPCalendarHelper::renderPrice($this->totalBookings['notactive']['price'] ?: ''); ?>]
			</span>
		</div>
	</div>
</div>
