<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

 defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/adminform/default.css');
$this->dpdocument->loadScriptFile('views/couponform/default.js');

$action = $this->router->route('index.php?option=com_dpcalendar&view=coupon&co_id=' . (int)$this->coupon->id);
?>
<div class="com-dpcalendar-couponform com-dpcalendar-adminform">
	<form class="com-dpcalendar-couponform__form dp-form form-validate" method="post" name="adminForm" id="adminForm"
		  action="<?php echo $action; ?>">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'com-dpcalendar-form-', ['active' => 'general']); ?>
		<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
			<?php echo HTMLHelper::_('bootstrap.addTab', 'com-dpcalendar-form-', $name, $this->translate($fieldSet->label)); ?>
			<div class="com-dpcalendar-couponform__content dp-grid">
				<div class="com-dpcalendar-couponform__fields">
					<?php foreach ($this->form->getFieldset($name) as $field) { ?>
						<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
					<?php } ?>
				</div>
			</div>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php } ?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
