<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/tools/default.css');
?>
<div class="com-dpcalendar-tools-default">
	<?php if ($this->sidebar) { ?>
		<div id="j-sidebar-container"><?php echo $this->sidebar; ?></div>
	<?php } ?>
	<div id="j-main-container">
		<div id="cpanel">
			<div class="com-dpcalendar-tools-default__icons">
				<div class="dp-quick-icon">
					<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=tools&layout=import">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DOWNLOAD]); ?>
						<span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_IMPORT'); ?></span>
					</a>
				</div>
				<div class="dp-quick-icon">
					<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&task=caldav.sync">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::SYNC]); ?>
						<span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_CALDAV'); ?></span>
					</a>
				</div>
				<div class="dp-quick-icon">
					<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=tools&layout=translate">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LANGUAGE]); ?>
						<span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE'); ?></span>
					</a>
				</div>
			</div>
		</div>
		<div class="com-dpcalendar-tools-default__footer">
			<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), $this->input->getString('DPCALENDAR_VERSION')); ?>
		</div>
	</div>
</div>
