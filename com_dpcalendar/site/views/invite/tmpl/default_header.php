<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-invite__actions dp-button-bar">
	<button type="button" class="dp-button dp-button-save" data-task="invite">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::OK]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_INVITE_SEND_BUTTON'); ?>
	</button>
	<button type="button" class="dp-button dp-button-cancel" data-task="cancel">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::CANCEL]); ?>
		<?php echo $this->translate('JCANCEL'); ?>
	</button>
</div>
