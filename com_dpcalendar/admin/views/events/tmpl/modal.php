<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/adminlist/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/adminlist/default.js');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$function = $this->input->getCmd('function', 'jSelectEvent');
?>
<div class="com-dpcalendar-events com-dpcalendar-events-modal com-dpcalendar-adminlist">
	<form action="<?php echo JRoute::_('index.php?option=com_dpcalendar&view=events&layout=modal&tmpl=component&function=' . $function . '&' . JSession::getFormToken() . '=1'); ?>"
		  method="post" name="adminForm" id="adminForm">
		<div id="filter-bar" class="com-dpcalendar-adminlist__filterbar js-stools-container-bar">
			<div class="dp-event-list__date-filters">
				<label class="element-invisible" for="filter_search_start">
					<?php echo JText::_('COM_DPCALENDAR_VIEW_EVENTS_START_DATE_AFTER_LABEL'); ?>:
				</label>
				<?php $this->displayData['title'] = $this->translate('COM_DPCALENDAR_FIELD_START_DATE_LABEL'); ?>
				<?php $this->displayData['name'] = 'filter[search_start]'; ?>
				<?php $this->displayData['date'] = $this->startDate; ?>
				<?php echo $this->layoutHelper->renderLayout('block.datepicker', $this->displayData); ?>
				<label class="element-invisible" for="filter_search_end">
					<?php echo JText::_('COM_DPCALENDAR_VIEW_EVENTS_END_DATE_BEFORE_LABEL'); ?>:
				</label>
				<?php $this->displayData['title'] = $this->translate('COM_DPCALENDAR_FIELD_END_DATE_LABEL'); ?>
				<?php $this->displayData['name'] = 'filter[search_end]'; ?>
				<?php $this->displayData['date'] = $this->endDate; ?>
				<?php echo $this->layoutHelper->renderLayout('block.datepicker', $this->displayData); ?>
			</div>
			<?php echo $this->layoutHelper->renderLayout('joomla.searchtools.default', ['view' => $this]); ?>
		</div>
		<table class="dp-table" class="adminlist">
			<thead>
			<tr>
				<th><?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_TITLE', 'title', $listDirn, $listOrder); ?></th>
				<th><?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE', 'state', $listDirn, $listOrder); ?></th>
				<th><?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_START', 'state', $listDirn, $listOrder); ?></th>
				<th><?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_END', 'state', $listDirn, $listOrder); ?></th>
				<th><?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL', 'id', $listDirn, $listOrder); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($this->items as $i => $item) { ?>
				<tr>
					<td class="dp-table__col-expand">
						<a class="dp-link"
						   data-id="<?php echo $item->id; ?>"
						   data-title="<?php echo $this->escape(addslashes($item->title)); ?>"
						   data-calid="<?php echo $this->escape($item->catid); ?>"
						   data-url="<?php echo $this->escape(DPCalendarHelperRoute::getEventRoute($item->id, $item->catid)); ?>"
						   data-function="<?php echo $this->escape($function); ?>">
							<?php echo $this->escape($item->title); ?>
						</a>
					</td>
					<td><?php echo JHtml::_('jgrid.published', $item->state, $i, 'events.'); ?></td>
					<td><?php echo DPCalendarHelper::getDate($item->start_date, $item->all_day); ?></td>
					<td><?php echo DPCalendarHelper::getDate($item->end_date, $item->all_day); ?></td>
					<td><?php echo $item->id; ?></td>
				</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<td colspan="6"><?php echo $this->pagination->getListFooter(); ?></td>
			</tr>
			</tfoot>
		</table>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
