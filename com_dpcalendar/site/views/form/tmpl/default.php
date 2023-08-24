<?php
use Joomla\CMS\HTML\HTMLHelper;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

// Load the maps scripts when required
if (!in_array('location_ids', $this->params->get('event_form_hidden_fields', []))
	&& !in_array('location', $this->params->get('event_form_hidden_tabs', []))) {
	$this->layoutHelper->renderLayout('block.map', $this->displayData);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/form/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/form/default.js');
$this->dpdocument->addStyle($this->params->get('event_form_custom_css'));

if (DPCalendarHelper::isFree()) {
	$this->translator->translateJS('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS');
}

if (!empty($this->event->tickets)) {
	$this->translator->translateJS('COM_DPCALENDAR_VIEW_EVENT_SEND_TICKET_HOLDERS_NOFICATION');
	$this->translator->translateJS('JYES');
	$this->translator->translateJS('JNO');
	$this->translator->translateJS('JCANCEL');
}

if (!empty($this->seriesEvents)) {
	$this->translator->translateJS('COM_DPCALENDAR_VIEW_EVENT_FORM_UPDATE_MODIFIED');
	$this->translator->translateJS('COM_DPCALENDAR_VIEW_EVENT_FORM_UPDATE_RESET');
	$this->translator->translateJS('JYES');
	$this->translator->translateJS('JNO');
	$this->translator->translateJS('JCANCEL');
	$this->dpdocument->addScriptOptions('event.form.seriesevents',$this->seriesEvents);
}

$this->translator->translateJS('COM_DPCALENDAR_OPTIONS');

$action = $this->router->route('index.php?option=com_dpcalendar&e_id=' . $this->event->id);
?>
<div class="com-dpcalendar-eventform<?php echo $this->pageclass_sfx ? ' com-dpcalendar-eventform-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<div class="com-dpcalendar-eventform__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('information'); ?>
	<?php echo $this->loadTemplate('overlapping'); ?>
	<form class="com-dpcalendar-eventform__form dp-form form-validate" method="post" name="adminForm" action="<?php echo $action; ?>">
		<?php if ($this->params->get('event_form_flat_mode')) { ?>
			<?php echo $this->loadTemplate('flat_fields'); ?>
		<?php } else { ?>
			<?php echo $this->loadTemplate('tabbed_fields'); ?>
		<?php } ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>" class="dp-input dp-input-hidden">
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
		<input type="hidden" name="urlhash" value="<?php echo $this->input->getString('urlhash'); ?>" class="dp-input dp-input-hidden">
		<input type="hidden" name="template_event_id" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php echo $this->loadTemplate('actions'); ?>
</div>
