<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
?>
<div class="com-dpcalendar-booking__actions dp-button-bar">
	<button type="button" class="dp-button dp-button-action dp-button-review" data-task="review">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_REVIEW_SAVE_TICKETS'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-abort" data-task="abort">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_ABORT'); ?>
	</button>
</div>
