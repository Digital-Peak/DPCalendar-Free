<?php
use Joomla\CMS\HTML\HTMLHelper;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/booking/confirm.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/booking/confirm.js');
$this->dpdocument->addStyle($this->params->get('booking_custom_css', ''));
?>
<div class="com-dpcalendar-booking com-dpcalendar-booking-confirm<?php echo $this->pageclass_sfx ? ' com-dpcalendar-booking-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('steps'); ?>
	<?php echo $this->loadTemplate('title'); ?>
	<div class="com-dpcalendar-booking__event-text">
		<?php echo $this->booking->displayEvent->beforeDisplayContent; ?>
	</div>
	<?php echo $this->loadTemplate('content'); ?>
	<div class="com-dpcalendar-booking__event-text">
		<?php echo $this->booking->displayEvent->afterDisplayContent; ?>
	</div>
	<?php echo $this->loadTemplate('tickets'); ?>
	<form class="com-dpcalendar-booking__form dp-form form-validate" method="post" name="adminForm"
		  action="<?php echo $this->router->route('index.php?option=com_dpcalendar&b_id=' . (int)$this->booking->id . $this->tmpl); ?>">
		<?php echo $this->loadTemplate('payment'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php echo $this->loadTemplate('terms'); ?>
	<?php echo $this->loadTemplate('actions'); ?>
</div>
