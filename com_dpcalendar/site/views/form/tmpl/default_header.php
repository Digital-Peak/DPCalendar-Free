<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$calendar = DPCalendarHelper::getCalendar($this->form->getValue('catid'));
?>
<div class="com-dpcalendar-eventform__actions dp-button-bar">
	<?php if (!$this->event->id || !$calendar || $calendar->canEdit || ($calendar->canEditOwn && $this->event->created_by == $this->user->id)) { ?>
		<button type="button" class="dp-button dp-button-apply" data-task="apply">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::OK]); ?>
			<?php echo $this->translate('JAPPLY'); ?>
		</button>
	<?php } ?>
	<button type="button" class="dp-button dp-button-save" data-task="save">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::OK]); ?>
		<?php echo $this->translate('JSAVE'); ?>
	</button>
	<button type="button" class="dp-button dp-button-save2new" data-task="save2new">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::OK]); ?>
		<?php echo $this->translate('JTOOLBAR_SAVE_AND_NEW'); ?>
	</button>
	<button type="button" class="dp-button dp-button-save2copy" data-task="save2copy">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::OK]); ?>
		<?php echo $this->translate('JTOOLBAR_SAVE_AS_COPY'); ?>
	</button>
	<button type="button" class="dp-button dp-button-cancel" data-task="cancel">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::CANCEL]); ?>
		<?php echo $this->translate('JCANCEL'); ?>
	</button>
	<button type="button" class="dp-button dp-button-delete" data-task="delete">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DELETE]); ?>
		<?php echo $this->translate('JACTION_DELETE'); ?>
	</button>
</div>
