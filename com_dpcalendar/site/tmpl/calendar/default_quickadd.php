<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

if (!DPCalendarHelper::canCreateEvent()) {
	return;
}

$this->dpdocument->loadScriptFile('views/calendar/default.js');
?>
<div class="com-dpcalendar-calendar__quickadd dp-quickadd">
	<form action="<?php echo $this->router->getEventFormRoute(0, Uri::getInstance()->toString()); ?>" method="post" class="dp-form form-validate">
		<?php echo $this->quickaddForm->renderField('start_date'); ?>
		<?php echo $this->quickaddForm->renderField('end_date'); ?>
		<?php echo $this->quickaddForm->renderField('title'); ?>
		<?php echo $this->quickaddForm->renderField('catid'); ?>
		<?php echo $this->quickaddForm->renderField('color'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="urlhash" class="dp-input dp-input-hidden">
		<input type="hidden" name="jform[capacity]" value="0" class="dp-input dp-input-hidden">
		<?php if ($this->params->get('event_create_form', 1) == '1') { ?>
			<input type="hidden" name="jform[all_day]" value="0" class="dp-input dp-input-hidden">
		<?php } ?>
		<input type="hidden" name="layout" value="edit" class="dp-input dp-input-hidden">
		<input type="hidden" name="jform[location_ids][]" class="dp-input dp-input-hidden">
		<input type="hidden" name="jform[rooms][]" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
		<div class="dp-quickadd__buttons">
			<button type="button" class="dp-button dp-quickadd__button-submit">
				<?php echo $this->translate('JSAVE'); ?>
			</button>
			<button type="button" class="dp-button dp-quickadd__button-edit">
				<?php echo $this->translate('JACTION_EDIT'); ?>
			</button>
			<button type="button" class="dp-button dp-quickadd__button-cancel">
				<?php echo $this->translate('JCANCEL'); ?>
			</button>
		</div>
	</form>
</div>
