<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Booking;
use Joomla\CMS\HTML\HTMLHelper;
?>
<form class="com-dpcalendar-event-mailtickets__form dp-form form-validate" method="post" name="adminForm"
	id="adminForm" action="<?php echo $this->router->getEventRoute($this->event->id, $this->event->catid); ?>">
	<div class="com-dpcalendar-event-mailtickets__fields">
		<?php foreach ($this->mailTicketsForm->getFieldSet() as $field) { ?>
			<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
		<?php } ?>
	</div>
	<legend><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_TICKETHOLDERS_LEGEND'); ?></legend>
	<?php foreach ($this->event->tickets as $ticket) { ?>
		<div class="dp-control">
			<div class="dp-control__label"><?php echo $ticket->price_label; ?></div>
			<div class="dp-control__input">
				<input type="checkbox" class="dp-input dp-input-checkbox dp-form-input"
					value="<?php echo $ticket->id; ?>" name="jform[tickets][]" id="ticket-<?php echo $ticket->id; ?>" checked>
				<label for="ticket-<?php echo $ticket->id; ?>"><?php echo $ticket->name . ' [' . Booking::getStatusLabel($ticket) . ']'; ?></label>
			</div>
		</div>
	<?php } ?>
	<input type="hidden" name="task" class="dp-input dp-input-hidden">
	<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>" class="dp-input dp-input-hidden">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
