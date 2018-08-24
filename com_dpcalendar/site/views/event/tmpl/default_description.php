<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->event->description && !$this->event->displayEvent->afterDisplayContent) {
	return;
}
?>
<div class="com-dpcalendar-event__description">
	<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_DESCRIPTION'); ?></h3>
	<div class="com-dpcalendar-event__description-content">
		<?php echo JHTML::_('content.prepare', $this->event->description); ?>
	</div>
	<div class="com-dpcalendar-event__event-text">
		<?php echo $this->event->displayEvent->afterDisplayContent; ?>
	</div>
</div>
