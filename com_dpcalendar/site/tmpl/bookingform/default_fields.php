<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

$fields = $this->form->getFieldset();
DPCalendarHelper::sortFields($fields, $this->params->get('booking_form_fields_order_', new \stdClass()));
?>
<div class="com-dpcalendar-bookingform__fields">
	<?php foreach ($fields as $field) { ?>
		<?php if (in_array($field->fieldname, ['series', 'event_id', 'amount', 'options'])) { ?>
			<?php continue; ?>
		<?php } ?>
		<?php if (!$this->bookingId && $field->fieldname == 'coupon_id') { ?>
			<?php continue; ?>
		<?php } ?>
		<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
	<?php } ?>
</div>
