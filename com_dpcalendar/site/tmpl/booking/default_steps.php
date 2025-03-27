<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

$reviewStep = $this->params->get('booking_review_step', 2);
$counter = 1;
?>
<div class="com-dpcalendar-booking__step dp-steps">
	<span class="dp-step dp-step-choose">
		<span class="dp-step__number"><?php echo $counter++; ?></span>
	</span>
	<?php if ($reviewStep == 1 || ($reviewStep == 2 && (is_countable($this->booking->tickets) ? count($this->booking->tickets) : 0) > 1)) { ?>
		<span class="dp-steps__separator"><?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?></span>
		<span class="dp-step dp-step-configure <?php echo $this->getLayout() === 'review' ? 'dp-step_active' : ''; ?>">
			<span class="dp-step__number"><?php echo $counter++; ?></span>
		</span>
	<?php } ?>
	<?php if (!$this->booking->price && $this->params->get('booking_confirm_step', 1)) { ?>
		<span class="dp-steps__separator"><?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?></span>
		<span class="dp-step dp-step-confirm <?php echo $this->getLayout() === 'confirm' ? 'dp-step_active' : ''; ?>">
			<span class="dp-step__number"><?php echo $counter++; ?></span>
		</span>
	<?php } elseif ($this->booking->price) { ?>
		<span class="dp-steps__separator"><?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?></span>
		<span class="dp-step dp-step-provider <?php echo $this->getLayout() === 'confirm' ? 'dp-step_active' : ''; ?>">
			<span class="dp-step__number"><?php echo $counter++; ?></span>
		</span>
	<?php } ?>
	<span class="dp-steps__separator"><?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::NEXT]); ?></span>
	<span class="dp-step dp-step-pay <?php echo $this->getLayout() === 'order' || $this->getLayout() === 'pay' ? 'dp-step_active' : ''; ?>">
		<span class="dp-step__number"><?php echo $counter++; ?></span>
	</span>
</div>
