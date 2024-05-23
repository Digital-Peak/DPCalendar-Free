<?php
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($this->params->get('show_selection', 1) == 2) {
	return;
}
?>
<div class="com-dpcalendar-calendar__toggle dp-toggle">
	<div class="dp-toggle__up <?php echo $this->params->get('show_selection', 1) == 3 ? '' : 'dp-toggle_hidden'; ?>"
		 data-direction="up">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::UP]); ?>
	</div>
	<div class="dp-toggle__down <?php echo $this->params->get('show_selection', 1) == 3 ? 'dp-toggle_hidden' : ''; ?>"
		 data-direction="down">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DOWN]); ?>
	</div>
</div>
