<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

if ($this->bookingId || (is_countable($this->events) ? count($this->events) : 0) != 1 || !reset($this->events)->booking_information) {
	return;
}
?>
<div class="com-dpcalendar-bookingform__info-text">
	<?php echo reset($this->events)->booking_information; ?>
</div>
