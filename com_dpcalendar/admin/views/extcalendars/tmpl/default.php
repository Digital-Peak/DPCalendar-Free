<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadScriptFile('dpcalendar/views/extcalendars/default.js');
$this->dpdocument->loadStyleFile('dpcalendar/views/adminlist/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/adminlist/default.js');
$this->dpdocument->addScriptOptions('adminlist', ['listOrder' => $this->state->get('list.ordering')]);

if ($this->input->getCmd('tmpl') == 'component') {
	echo JToolbar::getInstance('toolbar')->render();
}

if ($this->pluginParams->get('cache', 1) == '2') {
	$this->app->enqueueMessage(JText::_('COM_DPCALENDAR_VIEW_EXTCALENDARS_SYNC_STARTED'), 'notice');
}
?>
<div class="com-dpcalendar-extcalendars com-dpcalendar-adminlist"
	 data-sync="<?php echo $this->pluginParams->get('cache', 1); ?>"
	 data-sync-plugin="<?php echo $this->input->getWord('dpplugin'); ?>">
	<form action="<?php echo $this->router->route('index.php?option=com_dpcalendar&view=extcalendars&dpplugin=' . $this->input->getWord('dpplugin')) . '&tmpl=' . $this->input->getWord('tmpl'); ?>"
		  method="post" name="adminForm" id="adminForm">
		<?php echo $this->layoutHelper->renderLayout('joomla.searchtools.default', ['view' => $this]); ?>
		<?php echo $this->loadTemplate('calendars'); ?>
		<input type="hidden" name="action" value="" id="extcalendar-action"/>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<input type="hidden" name="filter_order" value="<?php echo $this->escape($this->state->get('list.ordering')); ?>"/>
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->escape($this->state->get('list.direction')); ?>"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
