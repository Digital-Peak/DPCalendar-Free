<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->event->description && !$this->event->displayEvent->afterDisplayContent) {
	return;
}
?>
<div class="com-dpcalendar-event__description">
	<h<?php echo $this->heading + 2; ?> class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_DESCRIPTION'); ?>
	</h<?php echo $this->heading + 2; ?>>
	<div class="com-dpcalendar-event__description-content">
		<?php echo JHTML::_('content.prepare', $this->event->introText); ?>
		<?php echo JHTML::_('content.prepare', $this->event->description); ?>
	</div>
	<div class="com-dpcalendar-event__event-text">
		<?php echo $this->event->displayEvent->afterDisplayContent; ?>
	</div>
</div>
