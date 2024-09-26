<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

if ($this->bookingId || !$this->needsPayment) {
	return;
}

$this->translator->translateJS('COM_DPCALENDAR_VIEW_BOOKINGFORM_TAX_INCLUSIVE_TEXT');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_BOOKINGFORM_TAX_EXCLUSIVE_TEXT');
?>
<div class="com-dpcalendar-bookingform__total-tax dp-tax">
	<span class="dp-tax__label"><?php echo $this->translate('COM_DPCALENDAR_TAX'); ?></span>
	<span class="dp-tax__title"></span>
	<span class="dp-tax__icon">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::INFO]); ?> :
	</span>
	<span class="dp-tax__content"><?php echo $this->booking && $this->booking->id ? $this->booking->tax : ''; ?></span>
</div>
