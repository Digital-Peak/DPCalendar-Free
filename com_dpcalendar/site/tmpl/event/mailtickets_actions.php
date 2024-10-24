<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
?>
<div class="com-dpcalendar-event-mailtickets__actions dp-button-bar">
	<button type="button" class="dp-button dp-button-action dp-button-send" data-task="mailtickets">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_SEND_MAIL_TICKETHOLDERS'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-senduser" data-task="mailticketsuser" title="<?php echo $this->user->email; ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DIRECTIONS]); ?>
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_SEND_MAIL_ME'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-cancel" data-task="cancel">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
		<?php echo $this->translate('JCANCEL'); ?>
	</button>
</div>
