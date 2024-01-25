<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

if ($this->event->original_id == '0') {
	return;
}
?>
<div class="com-dpcalendar-eventform__information">
	<?php if ($this->event->original_id == '-1') {
	?>
		<h4 class="com-dpcalendar-eventform__original-warning  dp-info-box">
			<?php 
	echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_ORIGINAL_WARNING');
	?>
		</h4>
	<?php 
} elseif (!empty($this->event->original_id)) {
	?>
		<h4 class="dp-info-box">
			<?php 
	echo sprintf(
 				$this->translate('COM_DPCALENDAR_VIEW_EVENT_GOTO_ORIGINAL'),
 				$this->router->getEventFormRoute($this->event->original_id, base64_decode($this->returnPage))
 			);
	?>
		</h4>
	<?php 
} ?>
</div>
