<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

$token = $this->input->get('token') ? '&token=' . $this->input->get('token') : '';
?>
<div class="com-dpcalendar-ticket__actions dp-button-bar dp-print-hide">
	<?php if (in_array($this->booking->state, [2, 3]) && $this->user->id == $this->booking->user_id) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-confirm"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&view=booking&layout=confirm&uid=' . $this->booking->uid . $token); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CONFIRM_BUTTON' . ($this->booking->price ? '_PAY' : '')); ?>
		</button>
	<?php } ?>
	<?php if ($this->ticket->state == 1) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-download-ticket"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=ticket.pdfdownload&uid=' . $this->ticket->uid . $token); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DOWNLOAD]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_DOWNLOAD'); ?>
		</button>
	<?php } ?>
	<?php if ($this->ticket->state == 9) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-download-certificate"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=ticket.certificatedownload&uid=' . $this->ticket->uid . $token); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CERTIFICATE]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_CERTIFICATE'); ?>
		</button>
	<?php } ?>
	<?php if ($this->booking->invoice == 1 && $this->booking->price) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-download-invoice"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.invoice&b_id=' . $this->booking->id . $token); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DOWNLOAD]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_INVOICE'); ?>
		</button>
	<?php } ?>
	<button type="button" class="dp-button dp-button-action dp-button-event"
		data-href="<?php echo $this->router->getEventRoute($this->event->id, $this->event->catid); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::INFO]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_EVENT'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-booking"
		data-href="<?php echo $this->router->getBookingRoute($this->booking); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::USERS]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_BOOKING_LABEL'); ?>
	</button>
	<?php if ($this->booking->state == 5) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-invite-accept"
			data-href="<?php echo \DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper::getInviteChangeRoute($this->booking, true, false); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_INVITE_ACCEPT'); ?>
		</button>
		<button type="button" class="dp-button dp-button-action dp-button-invite-decline"
			data-href="<?php echo \DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper::getInviteChangeRoute($this->booking, false, false); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_INVITE_DECLINE'); ?>
		</button>
	<?php } ?>
	<?php if ($this->ticket->params->get('access-edit')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-edit"
			data-href="<?php echo $this->router->getTicketFormRoute($this->ticket->id); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EDIT]); ?>
			<?php echo $this->translate('JGLOBAL_EDIT'); ?>
		</button>
	<?php } ?>
	<?php if ($this->ticket->params->get('access-delete')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-delete"
			data-href="<?php echo $this->router->getTicketDeleteRoute($this->ticket->id); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DELETE]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_DELETE'); ?>
		</button>
	<?php } ?>
	<button type="button" class="dp-button dp-button-print" data-selector=".com-dpcalendar-ticket">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PRINTING]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'); ?>
	</button>
</div>
