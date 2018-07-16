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
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
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

$this->translator->translateJS('COM_DPCALENDAR_VIEW_EVENT_SEND_TICKET_HOLDERS_NOFICATION');
?>
<div class="com-dpcalendar-eventform">
	<?php if ($this->event->original_id == '-1') { ?>
		<h4 class="dp-info-box"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_ORIGINAL_WARNING'); ?></h4>
	<?php } else if (!empty($this->event->original_id)) { ?>
		<h4 class="dp-info-box">
			<?php echo JText::sprintf(
				'COM_DPCALENDAR_VIEW_EVENT_GOTO_ORIGINAL',
				$this->router->getEventFormRoute($this->event->original_id, base64_decode($this->returnPage))
			); ?>
		</h4>
	<?php } ?>
	<?php if ($this->params->get('event_form_check_overlaping', 0)) { ?>
		<div class="com-dpcalendar-eventform__overlapping dp-info-box"
		     data-overlapping="<?php echo $this->params->get('event_form_check_overlaping', 0) == '2'; ?>"></div>
	<?php } ?>
	<form class="com-dpcalendar-eventform__form dp-form form-validate" method="post" name="adminForm"
	      action="<?php echo $this->router->route('index.php?option=com_dpcalendar&e_id=' . $this->event->id); ?>">
		<?php echo JHtml::_('bootstrap.startTabSet', 'com-dpcalendar-form-', array('active' => 'general')); ?>
		<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
			<?php echo JHtml::_('bootstrap.addTab', 'com-dpcalendar-form-', $name, $this->translate($fieldSet->label)); ?>
			<?php foreach ($this->form->getFieldset($name) as $field) { ?>
				<?php echo $field->renderField(['class' => 'dp-field-' . str_replace('_', '-', $field->fieldname)]); ?>
			<?php } ?>
			<?php if ($name == 'location') { ?>
				<?php echo $this->loadTemplate('location'); ?>
			<?php } ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="template_event_id" class="dp-input dp-input-hidden">
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
