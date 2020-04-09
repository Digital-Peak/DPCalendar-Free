<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->layoutHelper->renderLayout('block.map', $this->displayData);
$this->dpdocument->loadStyleFile('dpcalendar/views/form/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/form/default.js');

if (DPCalendarHelper::isFree()) {
	$this->translator->translateJS('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS');
}

if (!empty($this->event->tickets)) {
	$this->translator->translateJS('COM_DPCALENDAR_VIEW_EVENT_SEND_TICKET_HOLDERS_NOFICATION');
}
?>
<div class="com-dpcalendar-eventform">
	<div class="com-dpcalendar-eventform__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
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
		<?php echo JHtml::_('bootstrap.startTabSet', 'com-dpcalendar-form-', ['active' => 'general']); ?>
		<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
			<?php echo JHtml::_('bootstrap.addTab', 'com-dpcalendar-form-', $name, $this->translate($fieldSet->label)); ?>
			<?php foreach ($this->form->getFieldset($name) as $field) { ?>
				<?php echo $field->renderField(['class' => 'dp-field-' . str_replace('_', '-', $field->fieldname)]); ?>
			<?php } ?>
			<?php if ($name == 'location') { ?>
				<?php echo $this->loadTemplate('map'); ?>
			<?php } ?>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="template_event_id" class="dp-input dp-input-hidden">
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
