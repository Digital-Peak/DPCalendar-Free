<?php
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

$action = 'index.php?option=com_dpcalendar&view=bookings&Itemid=' . $this->input->getInt('Itemid', 0) . $this->tmpl;
if ($eventId = $this->input->getInt('e_id', 0)) {
	$action .= '&e_id=' . $this->input->getInt('e_id', 0);
}
?>
<div class="com-dpcalendar-bookings__actions dp-button-bar dp-print-hide">
	<div class="dp-buttons">
		<button type="button" class="dp-button dp-button-print" data-selector=".com-dpcalendar-bookings">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PRINTING]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'); ?>
		</button>
		<?php echo $this->afterButtonEventOutput; ?>
	</div>
	<form class="dp-form" action="<?php echo $this->router->route($action); ?>" method="post">
		<?php echo $this->pagination->getLimitBox(); ?>
		<input type="hidden" name="limitstart" class="dp-input dp-input-hidden">
	</form>
</div>
