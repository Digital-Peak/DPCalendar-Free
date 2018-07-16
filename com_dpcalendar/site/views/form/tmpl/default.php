<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_CORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_URL);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MOMENT);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_SELECT);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FORM);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_AUTOCOMPLETE);
$this->dpdocument->loadStyleFile('dpcalendar/views/form/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/form/default.js');

if ($this->params->get('save_history')) {
	JHtml::_('behavior.modal', 'a.modal_jform_contenthistory');
}

if (DPCalendarHelper::isFree()) {
	$this->translator->translateJS('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS');
}

// Load the maps scripts when required
if ($this->params->get('event_form_change_location', 1)) {
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
}

$this->translator->translateJS('COM_DPCALENDAR_VIEW_EVENT_SEND_TICKET_HOLDERS_NOFICATION');

$action = $this->router->route('index.php?option=com_dpcalendar&view=form&e_id=' . $this->event->id);
?>
<div class="com-dpcalendar-eventform<?php echo $this->pageclass_sfx ? ' com-dpcalendar-eventform-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('header'); ?>
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
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
