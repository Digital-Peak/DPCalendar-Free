<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FORM);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_AUTOCOMPLETE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_AUTOCOMPLETE);
$this->dpdocument->loadStyleFile('dpcalendar/views/locationform/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/locationform/default.js');

$tmpl   = $this->input->getCmd('tmpl') ? '&tmpl=' . $this->input->getCmd('tmpl') : '';
$action = $this->router->route('index.php?option=com_dpcalendar&view=locationform&l_id=' . (int)$this->location->id . $tmpl);
?>
<div class="com-dpcalendar-locationform">
	<form class="com-dpcalendar-locationform__form dp-form form-validate" method="post" name="adminForm" id="adminForm" action="<?php echo $action; ?>">
		<?php echo JHtml::_('bootstrap.startTabSet', 'com-dpcalendar-form-', array('active' => 'general')); ?>
		<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
			<?php echo JHtml::_('bootstrap.addTab', 'com-dpcalendar-form-', $name, $this->translate($fieldSet->label)); ?>
			<div class="com-dpcalendar-locationform__content dp-grid">
				<div class="com-dpcalendar-locationform__fields">
					<?php foreach ($this->form->getFieldset($name) as $field) { ?>
						<?php echo $field->renderField(['class' => 'dp-field-' . str_replace('_', '-', $field->fieldname)]); ?>
					<?php } ?>
				</div>
				<?php if ($name == 'general') { ?>
					<div class="com-dpcalendar-locationform__map dp-map"></div>
				<?php } ?>
			</div>
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php } ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
