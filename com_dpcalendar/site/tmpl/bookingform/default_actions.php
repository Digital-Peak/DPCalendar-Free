<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

$this->translator->translateJS('COM_DPCALENDAR_VIEW_BOOKINGFORM_CONFIRM_BUTTON');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_BOOKINGFORM_GO_REVIEW_BUTTON');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_BOOKINGFORM_GO_CONFIRM_BUTTON');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_BOOKINGFORM_GO_CONFIRM_PAYMENT_BUTTON');

$buttonText = $this->bookingId ? 'JSAVE' : 'COM_DPCALENDAR_VIEW_BOOKINGFORM_GO_REVIEW_BUTTON';
if ($this->event->capacity !== null && $this->event->capacity_used >= $this->event->capacity && $this->event->booking_waiting_list
	&& ((is_countable($this->events) ? count($this->events) : 0) == 1 || $this->event->booking_series != 2) && !$this->bookingId) {
	$buttonText = 'COM_DPCALENDAR_VIEW_BOOKINGFORM_WAITING_BUTTON';
}
// Ensure when tickets are on waiting list, that the button state is in waiting mode
if ((int)$this->event->waiting_list_count > 0) {
	$buttonText = 'COM_DPCALENDAR_VIEW_BOOKINGFORM_WAITING_BUTTON';
}
?>
<div class="com-dpcalendar-bookingform__actions dp-button-bar">
	<button type="button" class="dp-button dp-button-action dp-button-save" data-task="save"
		data-waiting="<?php echo $buttonText === 'COM_DPCALENDAR_VIEW_BOOKINGFORM_WAITING_BUTTON' ? 1 : 0; ?>"
		data-review="<?php echo $this->bookingId ? '' : $this->params->get('booking_review_step', 2); ?>"
		data-confirm="<?php echo $this->bookingId ? '' : $this->params->get('booking_confirm_step', 1); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?>
		<span class="dp-button-save__text"><?php echo $this->translate($buttonText); ?></span>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-cancel" data-task="cancel">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_' . ($this->bookingId ? 'CANCEL' : 'ABORT') . '_BUTTON'); ?>
	</button>
</div>
