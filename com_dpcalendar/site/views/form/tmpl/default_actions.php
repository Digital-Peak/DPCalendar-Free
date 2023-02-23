<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\HTML\Block\Icon;

$calendar = DPCalendarHelper::getCalendar($this->form->getValue('catid'));
?>
<div class="com-dpcalendar-eventform__actions dp-button-bar">
	<?php if (!$this->event->id || !$calendar || $calendar->canEdit || ($calendar->canEditOwn && $this->event->created_by == $this->user->id)) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-apply" data-task="apply">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
			<?php echo $this->translate('JAPPLY'); ?>
		</button>
	<?php } ?>
	<button type="button" class="dp-button dp-button-action dp-button-save" data-task="save">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
		<?php echo $this->translate('JSAVE'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-save2new" data-task="save2new">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
		<?php echo $this->translate('JTOOLBAR_SAVE_AND_NEW'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-save2copy" data-task="save2copy">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
		<?php echo $this->translate('JTOOLBAR_SAVE_AS_COPY'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-cancel" data-task="cancel">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
		<?php echo $this->translate('JCANCEL'); ?>
	</button>
</div>
