<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/bookingform/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/bookingform/default.js');
?>
<div class="com-dpcalendar-bookingform">
	<form class="com-dpcalendar-bookingform__form dp-form form-validate" method="post" name="adminForm"
		action="<?php echo $this->router->route('index.php?option=com_dpcalendar&view=booking&b_id=' . (int)$this->booking->id); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
		<?php foreach ($this->form->getFieldsets() as $name => $fieldSet) { ?>
			<div class="com-dpcalendar-bookingform__fieldset dp-fieldset-<?php echo $name; ?>">
				<?php foreach ($this->form->getFieldset($name) as $field) { ?>
					<?php echo $field->renderField(['class' => DPCalendarHelper::getFieldName($field, true)]); ?>
				<?php } ?>
			</div>
		<?php } ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
