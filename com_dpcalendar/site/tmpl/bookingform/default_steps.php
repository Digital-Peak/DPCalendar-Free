<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

if ($this->bookingId) {
	return;
}

$counter = 1;
?>
<div class="com-dpcalendar-bookingform__step dp-steps">
	<span class="dp-step dp-step-choose dp-step_active">
		<span class="dp-step__number"><?php echo $counter++; ?></span>
	</span>
	<?php if ($this->params->get('booking_review_step', 2)) { ?>
		<span class="dp-steps__separator"><?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?></span>
		<span class="dp-step dp-step-configure">
			<span class="dp-step__number"><?php echo $counter++; ?></span>
		</span>
	<?php } ?>
	<?php if (!$this->needsPayment && $this->params->get('booking_confirm_step', 1)) { ?>
		<span class="dp-steps__separator"><?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?></span>
		<span class="dp-step dp-step-confirm ">
			<span class="dp-step__number"><?php echo $counter++; ?></span>
		</span>
	<?php } elseif ($this->needsPayment) { ?>
		<span class="dp-steps__separator"><?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?></span>
		<span class="dp-step dp-step-provider">
			<span class="dp-step__number"><?php echo $counter++; ?></span>
		</span>
		<span class="dp-steps__separator"><?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?></span>
		<span class="dp-step dp-step-pay">
			<span class="dp-step__number"><?php echo $counter++; ?></span>
		</span>
	<?php } ?>
</div>
