<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

JFactory::getDocument()->addStyleSheet('components/com_dpcalendar/views/cpanel/tmpl/default.css');
?>
<div class="row-fluid">
<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<div id="cpanel" style="float:left">
		    <div style="float:left;">
		            <div class="icon">
		                <a href="index.php?option=com_dpcalendar&view=tools&layout=import" >
		                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-import.png" height="50px" width="50px">
		                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_IMPORT'); ?></span>
		                </a>
		            </div>
		            <div class="icon">
		                <a href="index.php?option=com_dpcalendar&task=caldav.sync" >
		                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-caldav.png" height="50px" width="50px">
		                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_CALDAV'); ?></span>
		                </a>
		            </div>
		            <div class="icon">
		                <a href="index.php?option=com_dpcalendar&view=tools&layout=translate" >
		                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-translation.png" height="50px" width="50px">
		                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE'); ?></span>
		                </a>
		            </div>
		    </div>
		</div>
	</div>
</div>
<div align="center" style="clear: both">
	<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), JFactory::getApplication()->input->getVar('DPCALENDAR_VERSION'));?>
</div>
