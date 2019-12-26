<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($this->params->get('show_selection', 1) == 2) {
	return;
}
?>
<div class="com-dpcalendar-calendar__toggle dp-toggle">
	<div class="dp-toggle__up dp-toggle_<?php echo $this->params->get('show_selection', 1) == 3 ? '' : 'hidden'; ?>"
		 data-direction="up">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::UP]); ?>
	</div>
	<div class="dp-toggle__down dp-toggle_<?php echo $this->params->get('show_selection', 1) == 3 ? 'hidden' : ''; ?>"
		 data-direction="down">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DOWN]); ?>
	</div>
</div>
