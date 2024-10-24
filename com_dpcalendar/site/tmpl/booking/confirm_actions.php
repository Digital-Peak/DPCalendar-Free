<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

$reviewStep = DPCalendarHelper::getComponentParameter('booking_review_step', 2);
?>
<div class="com-dpcalendar-booking__actions dp-button-bar">
	<button type="button" class="dp-button dp-button-action dp-button-confirm" data-task="confirm" disabled>
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CONFIRM_BUTTON' . ($this->booking->price ? '_PAY' : '')); ?>
	</button>
	<?php if ($reviewStep == 1 || ($reviewStep == 2 && (is_countable($this->booking->tickets) ? count($this->booking->tickets) : 0) > 1)) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-review dp-button-action"
				data-href="<?php echo $this->router->getBookingRoute($this->booking) . '&layout=review'; ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::BACK]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CONFIRM_BACK_SAVE_TICKETS_BUTTON'); ?>
		</button>
	<?php } ?>
	<button type="button" class="dp-button dp-button-action dp-button-abort" data-task="abort">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_ABORT'); ?>
	</button>
</div>
