<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;

?>
<div class="com-dpcalendar-booking__actions dp-button-bar dp-print-hide">
	<button type="button" class="dp-button dp-button-action dp-button-booking"
			data-href="<?php echo RouteHelper::getBookingRoute($this->booking); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::USERS]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_BOOKING_LABEL'); ?>
	</button>
	<?php if ($this->booking->invoice == 1 && $this->booking->price) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-download dp-button-download-invoice"
			data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.invoice&b_id=' . $this->booking->id . $this->tmpl); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::INVOICE]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_INVOICE'); ?>
		</button>
	<?php } ?>
	<button type="button" class="dp-button dp-button-action dp-button-download"
		data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.receipt&b_id=' . $this->booking->id . $this->tmpl); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::RECEIPT]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_RECEIPT'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-print" data-selector=".com-dpcalendar-booking">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PRINTING]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'); ?>
	</button>
</div>
