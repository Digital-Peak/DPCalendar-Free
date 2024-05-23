<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/ticketform/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/ticketform/default.js');

$action = $this->router->route('index.php?option=com_dpcalendar&view=ticketform&t_id=' . (int)$this->ticket->id . $this->tmpl);
?>
<div class="com-dpcalendar-ticketform">
	<form class="com-dpcalendar-ticketform__form dp-form form-validate" method="post" name="adminForm" id="adminForm" action="<?php echo $action; ?>">
		<div class="com-dpcalendar-ticketform__fields">
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'com-dpcalendar-form-', ['active' => 'general']); ?>
			<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
				<?php echo HTMLHelper::_('bootstrap.addTab', 'com-dpcalendar-form-', $name, $this->translate($fieldSet->label)); ?>
				<?php foreach ($this->form->getFieldset($name) as $field) { ?>
					<?php echo $field->renderField(['class' => \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::getFieldName($field, true)]); ?>
				<?php } ?>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
			<?php } ?>
			<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		</div>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
