<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
?>
<div class="com-dpcalendar-davcalendar__actions dp-button-bar">
	<button type="button" class="dp-button dp-button-action dp-button-save" data-task="save">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
		<?php echo $this->translate('JSAVE'); ?>
	</button>
	<button type="button" class="dp-button dp-button-action dp-button-cancel" data-task="cancel">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
		<?php echo $this->translate('JCANCEL'); ?>
	</button>
</div>
