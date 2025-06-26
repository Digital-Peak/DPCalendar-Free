<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2025 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

if ($this->bookingId || !$this->needsPayment) {
	return;
}
?>
<div class="com-dpcalendar-bookingform__total-user-group-discount dp-user-group-discount">
	<span class="dp-user-group-discount__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_USER_GROUP_DISCOUNT_LABEL'); ?>:</span>
	<span class="dp-user-group-discount__content"></span>
</div>
