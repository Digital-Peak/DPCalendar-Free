<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/tools/default.css');
?>
<div class="com-dpcalendar-tools">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<div id="cpanel">
		    <div class="com-dpcalendar-tools__icons">
		            <div class="dp-quick-icon">
		                <a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=tools&layout=import" >
			                <?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DOWNLOAD]); ?>
			                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_IMPORT'); ?></span>
		                </a>
		            </div>
		            <div class="dp-quick-icon">
		                <a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&task=caldav.sync" >
			                <?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::SYNC]); ?>
			                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_CALDAV'); ?></span>
		                </a>
		            </div>
		            <div class="dp-quick-icon">
		                <a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=tools&layout=translate" >
			                <?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LANGUAGE]); ?>
			                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE'); ?></span>
		                </a>
		            </div>
		    </div>
		</div>
		<div class="com-dpcalendar-tools__footer">
			<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), $this->input->getString('DPCALENDAR_VERSION'));?>
		</div>
	</div>
</div>

