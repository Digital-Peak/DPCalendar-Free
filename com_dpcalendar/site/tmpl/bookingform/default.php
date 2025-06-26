<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/bookingform/default.css');
$this->dpdocument->loadScriptFile('views/bookingform/default.js');
$this->dpdocument->addStyle($this->params->get('booking_form_custom_css', ''));

$this->dpdocument->addScriptOptions(
	'price.url',
	'task=booking.calculateprice&e_id=' .
	(empty($this->event) ? 0 : $this->event->id) . '&b_id=' . (int)$this->bookingId
);
$this->translator->translateJS('COM_DPCALENDAR_OPTIONS');
?>
<div class="com-dpcalendar-bookingform<?php echo $this->pageclass_sfx ? ' com-dpcalendar-bookingform-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php if ($this->needsPayment) { ?>
		<?php echo $this->layoutHelper->renderLayout('block.currency', $this->displayData); ?>
	<?php } ?>
	<form class="com-dpcalendar-bookingform__form dp-form form-validate" method="post" name="adminForm"
		action="<?php echo $this->router->route('index.php?option=com_dpcalendar&view=bookingform&b_id=' . (int)$this->bookingId . $this->tmpl); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
		<?php echo $this->loadTemplate('steps'); ?>
		<?php echo $this->loadTemplate('existing_booking'); ?>
		<?php echo $this->loadTemplate('info_text'); ?>
		<?php echo $this->loadTemplate('info_text_events_discount'); ?>
		<?php echo $this->loadTemplate('info_text_tickets_discount'); ?>
		<?php echo $this->loadTemplate('series_info'); ?>
		<?php echo $this->loadTemplate('events'); ?>
		<?php echo $this->loadTemplate('total_events'); ?>
		<?php echo $this->loadTemplate('total_coupon'); ?>
		<?php echo $this->loadTemplate('total_events_discount'); ?>
		<?php echo $this->loadTemplate('total_tickets_discount'); ?>
		<?php echo $this->loadTemplate('total_user_group_discount'); ?>
		<?php echo $this->loadTemplate('total_earlybird_discount'); ?>
		<?php echo $this->loadTemplate('total_tax'); ?>
		<?php echo $this->loadTemplate('total'); ?>
		<?php echo $this->loadTemplate('fields'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>" class="dp-input dp-input-hidden">
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php echo $this->loadTemplate('actions'); ?>
</div>
