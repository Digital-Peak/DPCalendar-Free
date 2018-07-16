<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($this->event->original_id == '0') {
	return;
}
?>
<div class="com-dpcalendar-eventform__information">
	<?php if ($this->event->original_id == '-1') { ?>
		<h4 class="dp-info-box"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_ORIGINAL_WARNING'); ?></h4>
	<?php } else if (!empty($this->event->original_id)) { ?>
		<h4 class="dp-info-box">
			<?php echo JText::sprintf(
				'COM_DPCALENDAR_VIEW_EVENT_GOTO_ORIGINAL',
				$this->router->getEventFormRoute($this->event->original_id, base64_decode($this->returnPage))
			); ?>
		</h4>
	<?php } ?>
</div>
