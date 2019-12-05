<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$archived  = $this->state->get('filter.state') == 2;
$trashed   = $this->state->get('filter.state') == -2;
$canOrder  = $this->user->authorise('core.edit.state', 'com_dpcalendar.category');
?>
<div class="com-dpcalendar-events__events">
	<table class="dp-table" id="eventList">
		<thead>
		<tr>
			<th class="dp-table__col-check">
				<input type="checkbox" name="checkall-toggle" title="<?php echo $this->translate('JGLOBAL_CHECK_ALL'); ?>"
					   class="dp-input dp-input-checkbox dp-input-check-all"/>
			</th>
			<th class="dp-table__col-state"><?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?></th>
			<th class="dp-table__col-expand"><?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
			<th class="dp-events-table__col-date"><?php echo JHtml::_('searchtools.sort', 'JDATE', 'a.start_date', $listDirn, $listOrder); ?></th>
			<th class="dp-events-table__col-color">
				<?php echo JHtml::_('searchtools.sort', 'COM_DPCALENDAR_FIELD_COLOR_LABEL', 'a.color', $listDirn, $listOrder); ?>
			</th>
			<th><?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?></th>
			<th><?php echo JHtml::_('searchtools.sort', 'JAUTHOR', 'a.created_by', $listDirn, $listOrder); ?></th>
			<th><?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?></th>
			<th><?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $i => $item) {
			$canCreate  = $this->user->authorise('core.create', 'com_dpcalendar.category.' . $item->catid);
			$canEdit    = $this->user->authorise('core.edit', 'com_dpcalendar.category.' . $item->catid);
			$canEditOwn = $this->user->authorise('core.edit.own', 'com_dpcalendar.category.' . $item->catid);
			$canCheckin = $this->user->authorise('core.manage', 'com_checkin')
				|| $item->checked_out == $this->user->get('id') || $item->checked_out == 0;
			$canChange  = $this->user->authorise('core.edit.state', 'com_dpcalendar.category.' . $item->catid) && $canCheckin;
			?>
			<tr sortable-group-id="<?php echo $item->catid ?>">
				<td data-column=""><?php echo JHtml::_('grid.id', $i, $item->id); ?></td>
				<td data-column="<?php echo $this->translate('JSTATUS'); ?>">
					<div class="btn-group">
						<?php echo JHtml::_('jgrid.published', $item->state, $i, 'events.', $canChange, 'cb', $item->publish_up,
							$item->publish_down); ?>
						<?php $state = $this->getState($item); ?>
						<?php if ($canChange) { ?>
							<a href="#" data-cb="<?php echo $i; ?>" data-state="<?php echo $state[1]; ?>"
							   class="dp-link btn btn-micro <?php echo($item->featured == 1 ? 'active' : ''); ?>"
							   rel="tooltip" title="<?php echo $this->translate($state[3]); ?>">
								<i class="icon-<?php echo $state[0]; ?>"></i>
							</a>
						<?php } ?>
					</div>
				</td>
				<td data-column="<?php echo $this->translate('JGLOBAL_TITLE'); ?>">
					<?php if ($item->checked_out) { ?>
						<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'events.', $canCheckin); ?>
					<?php } ?>
					<?php if ($canEdit || $canEditOwn) { ?>
						<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=event.edit&e_id=' . $item->id); ?>"
						   title="<?php echo $this->translate('JACTION_EDIT'); ?>">
							<?php echo $this->escape($item->title); ?>
						</a>
					<?php } else { ?>
						<span title="<?php echo JText::sprintf('JFIELD_ALIAS_LABEL',
							$this->escape($item->alias)); ?>"><?php echo $this->escape($item->title); ?>
						</span>
					<?php } ?>
					<div><?php echo $this->translate('COM_DPCALENDAR_CALENDAR') . ": " . $this->escape($item->category_title); ?></div>
				</td>
				<td data-column="<?php echo $this->translate('JDATE'); ?>"><?php echo $this->dateHelper->getDateStringFromEvent($item); ?></td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_FIELD_COLOR_LABEL'); ?>">
					<div style="background: none repeat scroll 0 0 #<?php echo $item->color ?: DPCalendarHelper::getCalendar($item->catid)->color; ?>">
						<?php echo $this->escape($item->color); ?>
					</div>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ACCESS'); ?>"><?php echo $this->escape($item->access_level); ?></td>
				<td data-column="<?php echo $this->translate('JAUTHOR'); ?>"><?php echo $this->escape($item->author_name); ?></td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_LANGUAGE'); ?>">
					<?php if ($item->language == '*') { ?>
						<?php echo JText::alt('JALL', 'language'); ?>
					<?php } else { ?>
						<?php echo $item->language_title ? $this->escape($item->language_title) : $this->translate('JUNDEFINED'); ?>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ID'); ?>"><?php echo (int)$item->id; ?></td>
			</tr>
		<?php } ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="11"><?php echo $this->pagination->getListFooter(); ?></td>
		</tr>
		</tfoot>
	</table>
</div>