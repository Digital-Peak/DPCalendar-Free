<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/tools/import.css');

$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_VIEW_TOOLS_IMPORT_WARNING'), 'warning');
?>
<form action="<?php echo JRoute::_('index.php?option=com_dpcalendar&task=import.add'); ?>" method="post" name="adminForm" id="adminForm"
	  class="com-dpcalendar-tools-import">
	<?php if ($this->sidebar) { ?>
		<div id="j-sidebar-container"><?php echo $this->sidebar; ?></div>
	<?php } ?>
	<div id="j-main-container">
		<div id="filter-bar" class="com-dpcalendar-tools-import__filters dp-filter">
			<div class="dp-filter__search">
				<label class="element-invisible" for="filter_search"><?php echo $this->translate('JSEARCH_FILTER_LABEL'); ?></label>
				<input type="text" name="filter_search" id="filter_search"
					   placeholder="<?php echo $this->translate('JSEARCH_FILTER_LABEL'); ?>"
					   title="<?php echo $this->translate('COM_DPCALENDAR_SEARCH_IN_TITLE'); ?>"/>
			</div>
			<div class="dp-filter__start">
				<label class="element-invisible"
					   for="filter_search_start">
					<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_START_DATE_AFTER_LABEL'); ?>:
				</label>
				<?php echo JHtml::_(
					'calendar',
					$this->escape($this->dateHelper->getDate()->format('Y-m-d')),
					'filter_search_start',
					'filter_search_start',
					'%Y-%m-%d',
					['class' => 'inputbox', 'maxlength' => '10', 'size' => '10']
				); ?>
			</div>
			<div class="dp-filter__end">
				<label class="element-invisible" for="filter_search_end">
					<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_END_DATE_BEFORE_LABEL'); ?>:
				</label>
				<?php $end = $this->dateHelper->getDate(); ?>
				<?php $end->modify('+2 month');; ?>
				<?php echo JHtml::_(
					'calendar',
					$this->escape($end),
					'filter_search_end',
					'filter_search_end',
					'%Y-%m-%d',
					['class' => 'inputbox', 'maxlength' => '10', 'size' => '10']
				); ?>
			</div>
		</div>
		<fieldset class="com-dpcalendar-tools-import__plugins">
			<?php foreach ($this->plugins as $plugin) { ?>
				<legend><?php echo $this->translate('PLG_DPCALENDAR_' . $plugin->name) ?></legend>
				<?php foreach ($this->calendars as $cal) { ?>
					<?php if ($cal->plugin_name != $plugin->name) { ?>
						<?php continue; ?>
					<?php } ?>
					<label class="checkbox">
						<input type="checkbox" name="calendar[]" value="<?php echo $cal->id; ?>"><?php echo $cal->title; ?>
					</label>
				<?php } ?>
			<?php } ?>
		</fieldset>
		<div class="com-dpcalendar-tools-import__footer">
			<?php echo sprintf($this->translate('COM_DPCALENDAR_FOOTER'), $this->input->getString('DPCALENDAR_VERSION')); ?>
		</div>
	</div>
	<input type="hidden" name="task" value=""/>
	<?php echo JHtml::_('form.token'); ?>
</form>
