<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
?>
<div class="com-dpcalendar-locationform__tabbed-fields dp-tabs">
	<?php $checked = 'checked="checked"'; ?>
	<?php foreach ($this->form->getFieldSets() as $name => $fieldSet) { ?>
		<?php $fields = $this->form->getFieldset($name); ?>
		<?php if (!$fields) { continue; } ?>
		<input type="radio" class="dp-tabs__input" name="dp-location-form-tabs" id="dp-tab-<?php echo $name; ?>" <?php echo $checked; ?>>
		<label for="dp-tab-<?php echo $name; ?>" class="dp-tabs__label">
			<?php echo $this->translate($fieldSet->label); ?>
		</label>
		<div class="dp-tabs__tab dp-tabs__tab-<?php echo $name; ?>">
			<?php foreach ($fields as $field) { ?>
				<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
			<?php } ?>
		</div>
		<?php $checked = ''; ?>
	<?php } ?>
</div>
