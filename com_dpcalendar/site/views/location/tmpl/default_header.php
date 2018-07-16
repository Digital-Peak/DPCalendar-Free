<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-location__actions dp-button-bar dp-print-hide">
	<?php if ($this->user->authorise('core.edit', 'com_dpcalendar')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-delete"
		        data-href="<?php echo $this->router->getLocationFormRoute($this->location->id, JUri::getInstance()); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EDIT]); ?>
			<?php echo $this->translate('JACTION_EDIT'); ?>
		</button>
	<?php } ?>
</div>
