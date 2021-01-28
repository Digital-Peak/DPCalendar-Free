<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$fields = $this->form->getFieldset();
\DPCalendar\Helper\DPCalendarHelper::sortFields($fields, $this->params->get('event_form_fields_order_', new stdClass()));
?>
<div class="com-dpcalendar-eventform__flat-fields">
	<?php foreach ($fields as $field) { ?>
		<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
		<?php if ($field->fieldname == 'rooms'
			&& !in_array('location_ids', $this->params->get('event_form_hidden_fields', []))
			&& !in_array('location', $this->params->get('event_form_hidden_tabs', []))) { ?>
			<?php echo $this->loadTemplate('map'); ?>
		<?php } ?>
	<?php } ?>
</div>
