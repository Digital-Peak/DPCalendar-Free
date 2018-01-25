<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

JFactory::getApplication()->enqueueMessage(JText::_('COM_DPCALENDAR_VIEW_TOOLS_IMPORT_WARNING'), 'warning');

JPluginHelper::importPlugin('dpcalendar');
?>
<form action="<?php echo JRoute::_('index.php?option=com_dpcalendar&task=import.add'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<label class="element-invisible" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
				<input type="text" name="filter_search" id="filter_search" value="" placeholder="<?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>"
					title="<?php echo JText::_('COM_DPCALENDAR_SEARCH_IN_TITLE'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<label class="element-invisible" for="filter_search_start"><?php echo JText::_('COM_DPCALENDAR_VIEW_EVENTS_START_DATE_AFTER_LABEL'); ?>:</label>
				<?php echo JHtml::_('calendar',
						$this->escape(DPCalendarHelper::getDate()->format('Y-m-d')),
						'filter_search_start',
						'filter_search_start',
						'%Y-%m-%d',
						array('class' => 'inputbox', 'maxlength' => '10', 'size' => '10'));?>
			</div>
			<div class="btn-group pull-left">
				<label class="element-invisible" for="filter_search_end"><?php echo JText::_('COM_DPCALENDAR_VIEW_EVENTS_END_DATE_BEFORE_LABEL'); ?>:</label>
				<?php
				$end = DPCalendarHelper::getDate();
				$end->modify('+2 month');

				echo JHtml::_('calendar',
						$this->escape($end),
						'filter_search_end',
						'filter_search_end',
						'%Y-%m-%d',
						array('class' => 'inputbox', 'maxlength' => '10', 'size' => '10'));?>
			</div>
		</div>
		<div class="clearfix"> </div>
		<?php
			$tmp = JFactory::getApplication()->triggerEvent('onCalendarsFetch');
			$calendars = array();
			if (!empty($tmp))
			{
				foreach ($tmp as $tmpCalendars)
				{
					foreach ($tmpCalendars as $calendar)
					{
						$calendars[] = $calendar;
					}
				}
			}
			foreach (JPluginHelper::getPlugin('dpcalendar') as $plugin)
			{
			JFactory::getLanguage()->load('plg_dpcalendar_' . $plugin->name, JPATH_PLUGINS . '/dpcalendar/' . $plugin->name);
			?>
			<fieldset class="panelform">
			<legend><?php echo JText::_('PLG_DPCALENDAR_' . $plugin->name)?></legend>

			<?php
				foreach ($calendars as $cal)
				{
					if ($cal->plugin_name != $plugin->name)
					{
						continue;
					}?>
					<label class="checkbox">
				    	<input type="checkbox" name="calendar[]" value="<?php echo $cal->id;?>"><?php echo $cal->title;?>
					</label>
			<?php
				} ?>
			</fieldset>
		<?php
			}?>
		</div>
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>
<div class="clearfix"> </div>

<div align="center" style="clear: both">
	<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), JFactory::getApplication()->input->getVar('DPCALENDAR_VERSION'));?>
</div>
