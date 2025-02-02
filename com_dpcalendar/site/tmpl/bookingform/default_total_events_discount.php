<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

if ($this->bookingId || !$this->needsPayment) {
	return;
}
?>
<div class="com-dpcalendar-bookingform__total-events-discount dp-events-discount">
	<span class="dp-events-discount__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_EVENTS_DISCOUNT_LABEL'); ?></span>
	<span class="dp-events-discount__icon">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::INFO]); ?> :
	</span>
	<span class="dp-events-discount__content"></span>
</div>
