<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_SELECT);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_IFRAME_CHILD);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FORM);

$this->dpdocument->loadStyleFile('dpcalendar/views/extcalendar/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/extcalendar/default.js');

if ($this->input->getCmd('tmpl') == 'component') {
	$bar = JToolbar::getInstance('toolbar');
	echo $bar->render();
}

$fieldSets = $this->form->getFieldsets();
$fieldSets = ['params' => $fieldSets['params']] + $fieldSets;
$fieldSets = ['general' => $fieldSets['general']] + $fieldSets;
?>
<div class="com-dpcalendar-extcalendar">
	<form class="com-dpcalendar-extcalendar__form dp-form form-validate" method="post" name="adminForm"
	      action="<?php echo $this->router->route('index.php?option=com_dpcalendar&id=' . (int)$this->item->id); ?>">
		<?php foreach ($fieldSets as $name => $fieldSet) { ?>
			<?php foreach ($this->form->getFieldset($name) as $field) { ?>
				<?php echo $field->renderField(['class' => 'dp-field-' . str_replace('_', '-', $field->fieldname)]); ?>
			<?php } ?>
		<?php } ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="dpplugin" value="<?php echo $this->input->get('dpplugin') ?>" class="dp-input dp-input-hidden"/>
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl') ?>" class="dp-input dp-input-hidden"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
