<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;

$this->translator->translateJS('COM_DPCALENDAR_CONFIRM_DELETE');
?>
<div class="com-dpcalendar-booking__actions dp-button-bar dp-print-hide">
	<?php if ($this->booking->state == 5) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-invite-accept"
			data-href="<?php echo RouteHelper::getInviteChangeRoute($this->booking, true, false); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_INVITE_ACCEPT'); ?>
		</button>
		<button type="button" class="dp-button dp-button-action dp-button-invite-decline"
			data-href="<?php echo RouteHelper::getInviteChangeRoute($this->booking, false, false); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_INVITE_DECLINE'); ?>
		</button>
	<?php } ?>
	<?php if ($this->booking->state == 0 && $this->user->id == $this->booking->user_id) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-edit"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&view=booking&layout=review&uid=' . $this->booking->uid); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_REVIEW_TICKETS_BUTTON'); ?>
		</button>
	<?php } ?>
	<?php if (in_array($this->booking->state, [2, 3]) && $this->user->id == $this->booking->user_id) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-edit"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&view=booking&layout=confirm&uid=' . $this->booking->uid); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CONFIRM_BUTTON' . ($this->booking->price ? '_PAY' : '')); ?>
		</button>
	<?php } ?>
	<?php if ($this->booking->params->get('access-edit')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-edit"
			data-href="<?php echo $this->router->getBookingFormRoute($this->booking); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EDIT]); ?>
			<?php echo $this->translate('JGLOBAL_EDIT'); ?>
		</button>
	<?php } ?>
	<?php if ($this->booking->invoice == 1 && $this->booking->price) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-download-invoice"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.invoice&b_id=' . $this->booking->id . $this->tmpl); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::INVOICE]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_INVOICE'); ?>
		</button>
	<?php } ?>
	<button type="button" class="dp-button dp-button-action dp-button-download-receipt"
		data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.receipt&b_id=' . $this->booking->id . $this->tmpl); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::RECEIPT]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_RECEIPT'); ?>
	</button>
	<?php if ($this->booking->params->get('access-delete') && $this->booking->state != 5) { ?>
		<?php $return = '&return=' . base64_encode('index.php?Itemid=' . $this->input->getInt('Itemid', 0)); ?>
		<button type="button" class="dp-button dp-button-action dp-button-delete dp-action-delete"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=bookingform.delete&b_id=' . $this->booking->id . $return . $this->tmpl); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DELETE]); ?>
			<?php echo $this->translate('JACTION_DELETE'); ?>
		</button>
	<?php } ?>
	<?php if ($this->booking->price && $this->booking->state != 6 && Booking::openForCancel($this->booking)) { ?>
		<?php $return = '&return=' . base64_encode('index.php?Itemid=' . $this->input->getInt('Itemid', 0)); ?>
		<button type="button" class="dp-button dp-button-action dp-button-cancel dp-action-delete"
			data-confirmtext="<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CANCEL_TICKET_CONFIRMATION'); ?>"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.cancel&b_id=' . $this->booking->id . $return . $this->tmpl); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DELETE]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CANCEL_BOOKING'); ?>
		</button>
	<?php } ?>
	<button type="button" class="dp-button dp-button-print" data-selector=".com-dpcalendar-booking">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PRINTING]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'); ?>
	</button>
</div>
