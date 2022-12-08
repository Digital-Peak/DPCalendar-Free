<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/adminform/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/couponform/default.js');

$action = $this->router->route('index.php?option=com_dpcalendar&view=coupon&co_id=' . (int)$this->coupon->id . $this->tmpl);
?>
<div class="com-dpcalendar-couponform com-dpcalendar-adminform">
	<form class="com-dpcalendar-couponform__form dp-form form-validate" method="post" name="adminForm" id="adminForm"
		  action="<?php echo $action; ?>">
		<?php echo JHtml::_('bootstrap.startTabSet', 'com-dpcalendar-form-', ['active' => 'general']); ?>
		<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
			<?php echo JHtml::_('bootstrap.addTab', 'com-dpcalendar-form-', $name, $this->translate($fieldSet->label)); ?>
			<div class="com-dpcalendar-couponform__content dp-grid">
				<div class="com-dpcalendar-couponform__fields">
					<?php foreach ($this->form->getFieldset($name) as $field) { ?>
						<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
					<?php } ?>
				</div>
			</div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
