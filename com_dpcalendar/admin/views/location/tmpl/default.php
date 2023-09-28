<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

$this->layoutHelper->renderLayout('block.map', $this->displayData);
$this->dpdocument->loadStyleFile('dpcalendar/views/locationform/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/locationform/default.js');

$action = $this->router->route('index.php?option=com_dpcalendar&view=locationform&l_id=' . (int)$this->location->id . $this->tmpl);
?>
<div class="com-dpcalendar-locationform">
	<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	<form class="com-dpcalendar-locationform__form dp-form form-validate" method="post" name="adminForm" id="adminForm"
		  action="<?php echo $action; ?>">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'com-dpcalendar-form-', ['active' => 'general']); ?>
		<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
			<?php echo HTMLHelper::_('bootstrap.addTab', 'com-dpcalendar-form-', $name, $this->translate($fieldSet->label)); ?>
			<div class="com-dpcalendar-locationform__content dp-grid">
				<div class="com-dpcalendar-locationform__fields">
					<?php foreach ($this->form->getFieldset($name) as $field) { ?>
						<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
					<?php } ?>
				</div>
				<?php if ($name == 'general' && $this->params->get('map_provider', 'openstreetmap') != 'none') { ?>
					<div class="com-dpcalendar-locationform__map dp-map"
						data-zoom="<?php echo $this->params->get('location_form_map_zoom', 10); ?>"
						data-latitude="<?php echo $this->params->get('location_form_map_latitude', 47); ?>"
						data-longitude="<?php echo $this->params->get('location_form_map_longitude', 4); ?>"
						data-ask-consent="<?php echo $this->params->get('map_ask_consent'); ?>"></div>
				<?php } ?>
			</div>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php } ?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
