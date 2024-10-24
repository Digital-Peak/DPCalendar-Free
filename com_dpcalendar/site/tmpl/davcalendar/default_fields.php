<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-davcalendar__fields">
	<?php foreach ($this->form->getFieldset() as $field) { ?>
		<?php echo $field->renderField(['class' => \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::getFieldName($field, true)]); ?>
	<?php } ?>
</div>
