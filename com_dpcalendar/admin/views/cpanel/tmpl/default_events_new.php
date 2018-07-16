<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-cpanel__new-events">
	<h3 class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_NEW_EVENTS'); ?>
	</h3>
	<div class="dp-information-container">
		<?php foreach ($this->newEvents as $event) { ?>
			<div class="com-dpcalendar-cpanel__event">
				<?php $date = $this->dateHelper->getDate($event->created)->format(
					$this->params->get('event_date_format', 'm.d.Y') . ' ' . $this->params->get('event_time_format', 'g:i a'), true
				); ?>
				<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-link">
					<?php echo $event->title; ?>
				</a>
				<div class="dp-information dp-grid">
					<span class="dp-information__label"><?php echo $this->translate('JGLOBAL_FIELD_CREATED_LABEL'); ?>: </span>
					<span class="dp-information__content"><?php echo $date; ?></span>
				</div>
				<div class="dp-information dp-grid">
					<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_AUTHOR'); ?>: </span>
					<span class="dp-information__content"><?php echo JFactory::getUser($event->created_by)->name; ?></span>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
