<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($this->booking->state != 8) {
	return;
}
?>
<div class="com-dpcalendar-booking__waiting dp-info-box">
	<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_WAITING_TEXT'); ?>
</div>
