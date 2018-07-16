<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$tmpl   = $this->input->getCmd('tmpl') ? '&tmpl=' . $this->input->getCmd('tmpl') : '';
$action = $this->router->route('index.php?option=com_dpcalendar' . $tmpl);
?>
<form class="com-dpcalendar-invite__form dp-form form-validate" method="post" name="adminForm" id="adminForm" action="<?php echo $action; ?>">
	<div class="com-dpcalendar-invite__fields">
		<?php foreach ($this->form->getFieldSet() as $field) { ?>
			<?php echo $field->renderField(['class' => 'dp-field-' . str_replace('_', '-', $field->fieldname)]); ?>
		<?php } ?>
	</div>
	<input type="hidden" name="task" class="dp-input dp-input-hidden">
	<input type="hidden" name="return" value="<?php echo $this->input->getBase64('return'); ?>" class="dp-input dp-input-hidden">
	<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
	<?php echo JHtml::_('form.token'); ?>
</form>
