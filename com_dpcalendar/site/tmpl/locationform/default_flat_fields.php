<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

$fields = $this->form->getFieldset();
DPCalendarHelper::sortFields($fields, $this->params->get('location_form_fields_order_', new \stdClass()));
?>
<div class="com-dpcalendar-locationform__flat-fields">
	<?php foreach ($fields as $field) { ?>
		<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
	<?php } ?>
</div>
