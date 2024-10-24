<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

$action = $this->router->route('index.php?option=com_dpcalendar&view=invite' . $this->tmpl);
?>
<form class="com-dpcalendar-invite__form dp-form form-validate" method="post" name="adminForm" id="adminForm" action="<?php echo $action; ?>">
	<div class="com-dpcalendar-invite__fields">
		<?php foreach ($this->form->getFieldSet() as $field) { ?>
			<?php echo $field->renderField(['class' => \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::getFieldName($field, true)]); ?>
		<?php } ?>
	</div>
	<input type="hidden" name="task" class="dp-input dp-input-hidden">
	<input type="hidden" name="return" value="<?php echo $this->input->getBase64('return'); ?>" class="dp-input dp-input-hidden">
	<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
