<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));

$function = JFactory::getApplication()->input->getCmd('function', 'jSelectEvent');
?>
<form action="<?php echo JRoute::_('index.php?option=com_dpcalendar&view=events&layout=modal&tmpl=component&function=' . $function . '&' . JSession::getFormToken() . '=1');?>" method="post" name="adminForm" id="adminForm" class="form-inline">
<div id="filter-bar" class="btn-toolbar">
		<div class="filter-search btn-group pull-left">
			<label class="element-invisible" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
				title="<?php echo JText::_('COM_DPCALENDAR_SEARCH_IN_TITLE'); ?>" />
		</div>
		<div class="btn-group pull-left">
			<label class="element-invisible" for="filter_search_start"><?php echo JText::_('COM_DPCALENDAR_VIEW_EVENTS_START_DATE_AFTER_LABEL'); ?>:</label>
			<?php
			echo JHtml::_(
                'datetime.render',
                $this->escape($this->state->get('filter.search_start')),
                'filter_search_start',
                'filter[search_start]',
                array(
                    'allDay'     => true,
                    'onchange'   => 'this.form.submit();',
                    'formated'   => true,
                    'dateFormat' => $this->params->get('event_form_date_format', 'm.d.Y')
                )
            );
			?>
		</div>
		<div class="btn-group pull-left">
			<label class="element-invisible" for="filter_search_end"><?php echo JText::_('COM_DPCALENDAR_VIEW_EVENTS_END_DATE_BEFORE_LABEL'); ?>:</label>
			<?php
			echo JHtml::_(
                'datetime.render',
                $this->escape($this->state->get('filter.search_end')),
                'filter_search_end',
                'filter[search_end]',
                array(
                    'allDay'     => true,
                    'onchange'   => 'this.form.submit();',
                    'formated'   => true,
                    'dateFormat' => $this->params->get('event_form_date_format', 'm.d.Y')
                )
            );
			?>
		</div>
		<div class="btn-group pull-left hidden-phone">
			<button class="btn tip" type="submit" rel="tooltip" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
			<button class="btn tip" type="button"
			onclick="document.getElementById('filter_search').value='';document.getElementById('filter_search_start').value='<?php echo DPCalendarHelper::getDate()->format($this->params->get('event_form_date_format', 'm.d.Y'))?>';document.getElementById('filter_search_end').value='';this.form.submit();"
			rel="tooltip" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
		</div>
	</div>
	<div class="clearfix"> </div>
<table class="table table-striped" class="adminlist">
    <thead>
        <tr>
            <th>
                <?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_TITLE', 'title', $listDirn, $listOrder); ?>
            </th>
            <th>
                <?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE', 'state', $listDirn, $listOrder); ?>
            </th>
            <th>
                <?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_START', 'state', $listDirn, $listOrder); ?>
            </th>
            <th>
                <?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_END', 'state', $listDirn, $listOrder); ?>
            </th>
            <th width="5">
                <?php echo JHtml::_('grid.sort', 'COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL', 'id', $listDirn, $listOrder); ?>
            </th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <td colspan="6"><?php echo $this->pagination->getListFooter(); ?></td>
        </tr>
    </tfoot>
    <tbody>
<?php foreach ($this->items as $i => $item)
{ ?>
        <tr class="row<?php echo $i % 2; ?>">
            <td>
				<a class="pointer" onclick="if (window.parent) window.parent.<?php echo $this->escape($function) . "('" . $item->id . "', '" . $this->escape(addslashes($item->title)) . "', '" . $this->escape($item->catid) . "', null, '" . $this->escape(DPCalendarHelperRoute::getEventRoute($item->id, $item->catid)); ?>', 'null', null);">
						<?php echo $this->escape($item->title); ?></a>
            </td>
            <td align="center"><?php echo JHtml::_('jgrid.published', $item->state, $i, 'events.'); ?></td>
            <td><?php echo DPCalendarHelper::getDate($item->start_date, $item->all_day); ?></td>
            <td><?php echo DPCalendarHelper::getDate($item->end_date, $item->all_day); ?></td>
            <td><?php echo $item->id; ?></td>
        </tr>
<?php
} ?>
    </tbody>
</table>
<div>
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
    <?php echo JHtml::_('form.token'); ?>
</div>
</form>
