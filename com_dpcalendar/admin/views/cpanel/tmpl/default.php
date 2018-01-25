<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

DPCalendarHelper::loadLibrary();
JFactory::getDocument()->addStyleSheet('components/com_dpcalendar/views/cpanel/tmpl/default.css');
?>
<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
<div style="width:500px;">
<h2><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_WELCOME'); ?></h2>
<p>
<?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_INTRO'); ?>
</p>
<br>

<div id="cpanel" style="float:left">
    <div style="float:left;margin-right:20px">
            <div class="icon">
                <a href="index.php?option=com_dpcalendar&view=events" >
                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-events.png" height="50px" width="50px">
                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_EVENTS'); ?></span>
                </a>
            </div>
            <div class="icon">
                <a href="index.php?option=com_dpcalendar&view=event&layout=edit" >
                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-event.png" height="50px" width="50px">
                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_ADD_EVENT'); ?></span>
                </a>
            </div>
            <div class="icon">
                <a href="index.php?option=com_categories&extension=com_dpcalendar" >
                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-dpcalendar.png" height="50px" width="50px">
                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_CALENDARS'); ?></span>
                </a>
            </div>
            <div class="icon">
                <a href="index.php?option=com_dpcalendar&view=locations" >
                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-locations.png" height="50px" width="50px">
                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_LOCATIONS'); ?></span>
                </a>
            </div>
            <?php if (!DPCalendarHelper::isFree())
            {?>
            <div class="icon">
                <a href="index.php?option=com_dpcalendar&view=bookings" >
                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-bookings.png" height="50px" width="50px">
                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_BOOKINGS'); ?></span>
                </a>
            </div>
            <?php
            }?>
            <div class="icon">
                <a href="index.php?option=com_dpcalendar&view=tools" >
                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-tools.png" height="50px" width="50px">
                <span><?php echo JText::_('COM_DPCALENDAR_SUBMENU_TOOLS'); ?></span>
                </a>
            </div>
            <div class="icon">
                <a href="index.php?option=com_dpcalendar&view=support" >
                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-support.png" height="50px" width="50px">
                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_SUPPORT'); ?></span>
                </a>
            </div>
            <div class="icon">
                <a href="index.php?option=com_dpcalendar&view=tools&layout=translate">
                <img src="<?php echo JURI::base(true);?>/../media/com_dpcalendar/images/admin/48-translation.png" height="50px" width="50px">
                <span><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE'); ?></span>
                </a>
            </div>
    </div>
</div>
</div>
<a class="twitter-timeline" href="https://twitter.com/digitpeak" data-widget-id="346951058737750017">Tweets by @digitpeak</a>
<script>!function(d,s,id){
	var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';
	if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";
	fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

<div align="center" style="clear: both">
	<br>
	<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), JFactory::getApplication()->input->getVar('DPCALENDAR_VERSION'));?>
	<br/>
	<span class="small">If you like DPCalendar, please post a positive review at the
	<a href="http://extensions.joomla.org/extensions/extension/calendars-a-events/events/dpcalendar<?php echo DPCalendarHelper::isFree() ? '-lite' : '' ?>"
		target="_blank">
		Joomla! Extensions Directory
	</a>.</span>
</div>
</div>
