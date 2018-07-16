<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$fields = $this->form->getFieldset();
\DPCalendar\Helper\DPCalendarHelper::sortFields($fields, $this->params->get('event_form_fields_order_', new stdClass()));
?>
<div class="com-dpcalendar-eventform__flat-fields">
	<?php foreach ($fields as $field) { ?>
		<?php echo $field->renderField(['class' => 'dp-field-' . str_replace('_', '-', $field->fieldname)]); ?>
		<?php if ($field->fieldname == 'rooms' && $this->params->get('event_form_change_location', 1)) { ?>
			<?php echo $this->loadTemplate('location'); ?>
		<?php } ?>
	<?php } ?>
</div>
