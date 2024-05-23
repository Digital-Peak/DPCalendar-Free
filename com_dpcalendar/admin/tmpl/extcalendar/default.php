<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/adminform/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/extcalendar/default.js');

if ($this->input->get('tmpl', '') === 'component') {
	$bar = Toolbar::getInstance('toolbar');
	echo $bar->render();
}

$fieldSets = $this->form->getFieldsets();
$fieldSets = ['params' => $fieldSets['params']] + $fieldSets;
$fieldSets = ['general' => $fieldSets['general']] + $fieldSets;
?>
<div class="com-dpcalendar-extcalendar com-dpcalendar-adminform">
	<form class="com-dpcalendar-extcalendar__form dp-form form-validate" method="post" name="adminForm"
		  action="<?php echo $this->router->route('index.php?option=com_dpcalendar&id=' . (int)$this->item->id); ?>">
		<?php foreach (array_keys($fieldSets) as $name) { ?>
			<?php foreach ($this->form->getFieldset($name) as $field) { ?>
				<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
			<?php } ?>
		<?php } ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="dpplugin" value="<?php echo $this->input->get('dpplugin') ?>" class="dp-input dp-input-hidden"/>
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl') ?>" class="dp-input dp-input-hidden"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
