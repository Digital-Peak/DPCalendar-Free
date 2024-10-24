<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

if ($this->bookingId || !$this->event || (is_countable($this->events) ? count($this->events) : 0) < 2) {
	return;
}
?>
<div class="com-dpcalendar-bookingform__series dp-booking-series">
	<?php $field = $this->form->getField('series'); ?>
	<span class="dp-booking-series__description"><?php echo $this->translate($field->__get('description')); ?></span>
	<span class="dp-booking-series__input"><?php echo $field->__get('input'); ?></span>
</div>
