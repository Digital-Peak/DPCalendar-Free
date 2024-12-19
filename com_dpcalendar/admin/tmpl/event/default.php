<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\HTML\HTMLHelper;

$this->layoutHelper->renderLayout('block.map', $this->displayData);
$this->dpdocument->loadStyleFile('dpcalendar/views/form/default.css');
$this->dpdocument->loadScriptFile('views/form/default.js');
$this->dpdocument->loadStyleFile('dpcalendar/views/adminevent/default.css');
$this->dpdocument->addStyle($this->params->get('event_form_custom_css', ''));

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
?>
<div class="com-dpcalendar-eventform">
	<div class="com-dpcalendar-eventform__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php if ($this->event->original_id == '-1') { ?>
		<h4 class="com-dpcalendar-eventform__original-warning dp-info-box">
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_ORIGINAL_WARNING'); ?>
		</h4>
	<?php } elseif (!empty($this->event->original_id)) { ?>
		<h4 class="dp-info-box">
			<?php
				echo sprintf(
					$this->translate('COM_DPCALENDAR_VIEW_EVENT_GOTO_ORIGINAL'),
					$this->router->getEventFormRoute($this->event->original_id)
				);
			?>
		</h4>
	<?php } ?>
	<?php if ($this->params->get('event_form_check_overlaping', 0)) { ?>
		<div class="com-dpcalendar-eventform__overlapping dp-info-box"
			data-overlapping="<?php echo $this->params->get('event_form_check_overlaping', 0) == '2'; ?>"></div>
	<?php } ?>
	<form class="com-dpcalendar-eventform__form dp-form form-validate" method="post" name="adminForm"
		action="<?php echo $this->router->route('index.php?option=com_dpcalendar&e_id=' . $this->event->id); ?>">
		<div class="dp-form__title">
			<?php echo $this->form->getField('title')->renderField(['class' => 'dp-field-title']); ?>
			<?php if ($this->form->getField('alias')) { ?>
				<?php echo $this->form->getField('alias')->renderField(['class' => 'dp-field-alias']); ?>
			<?php } ?>
		</div>
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'com-dpcalendar-form-', ['active' => 'general']); ?>
		<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
			<?php echo HTMLHelper::_('bootstrap.addTab', 'com-dpcalendar-form-', $name, $this->translate($fieldSet->label)); ?>
			<?php foreach ($this->form->getFieldset($name) as $field) { ?>
				<?php if ($field->fieldname == 'title' || $field->fieldname == 'alias') { ?>
					<?php continue; ?>
				<?php } ?>
				<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
			<?php } ?>
			<?php if ($name == 'location') { ?>
				<?php echo $this->loadTemplate('map'); ?>
			<?php } ?>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php } ?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="template_event_id" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
