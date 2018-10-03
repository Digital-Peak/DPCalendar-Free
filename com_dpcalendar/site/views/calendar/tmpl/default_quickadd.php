<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

if (!\DPCalendar\Helper\DPCalendarHelper::canCreateEvent()) {
	return;
}

$this->dpdocument->loadScriptFile('dpcalendar/views/calendar/default.js');
?>
<div class="com-dpcalendar-calendar__quickadd dp-quickadd">
	<form action="<?php echo $this->router->getEventFormRoute(0, JUri::getInstance()->toString()); ?>" method="post"
		  class="dp-form form-validate">
		<?php echo $this->quickaddForm->renderField('start_date'); ?>
		<?php echo $this->quickaddForm->renderField('end_date'); ?>
		<?php echo $this->quickaddForm->renderField('title'); ?>
		<?php echo $this->quickaddForm->renderField('catid'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="urlhash" class="dp-input dp-input-hidden">
		<input type="hidden" name="jform[capacity]" value="0" class="dp-input dp-input-hidden">
		<input type="hidden" name="jform[all_day]" value="0" class="dp-input dp-input-hidden">
		<input type="hidden" name="layout" value="edit" class="dp-input dp-input-hidden">
		<input type="hidden" name="jform[location_ids][]" class="dp-input dp-input-hidden">
		<input type="hidden" name="jform[rooms][]" class="dp-input dp-input-hidden">
		<?php echo JHtml::_('form.token'); ?>
		<div class="dp-quickadd__buttons">
			<button type="button" class="dp-button dp-quickadd__button-submit">
				<?php echo $this->translate('COM_DPCALENDAR_VIEW_FORM_BUTTON_SUBMIT_EVENT'); ?>
			</button>
			<button type="button" class="dp-button dp-quickadd__button-edit">
				<?php echo $this->translate('COM_DPCALENDAR_VIEW_FORM_BUTTON_EDIT_EVENT'); ?>
			</button>
			<button type="button" class="dp-button dp-quickadd__button-cancel">
				<?php echo $this->translate('JCANCEL'); ?>
			</button>
		</div>
	</form>
</div>
