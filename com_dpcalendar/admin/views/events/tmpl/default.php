<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');

$user		= JFactory::getUser();
$userId		= $user->get('id');
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$archived	= $this->state->get('filter.state') == 2 ? true : false;
$trashed	= $this->state->get('filter.state') == -2 ? true : false;
$canOrder	= $user->authorise('core.edit.state', 'com_dpcalendar.category');

JFactory::getDocument()->addStyleDeclaration('.ui-datepicker { z-index: 1003 !important; }');
?>
<script type="text/javascript">
	Joomla.orderTable = function() {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>') {
			dirn = 'asc';
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_dpcalendar&view=events'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<div id="filter-bar" class="js-stools-container-bar pull-left">
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
                        'dateFormat' => DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y')
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
                        'dateFormat' => DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y')
                    )
                );
				?>
			</div>
			&nbsp;
		</div>
		<?php
		echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<table class="table table-striped" id="eventList">
			<thead>
				<tr>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>
					<th width="1%" style="min-width:55px" class="nowrap center">
						<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
					</th>
					<th class="title">
						<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
					</th>
					<th width="15%">
						<?php echo JHtml::_('searchtools.sort', 'JDATE', 'a.start_date', $listDirn, $listOrder); ?>
					</th>
					<th width="3%">
						<?php echo JHtml::_('searchtools.sort', 'COM_DPCALENDAR_FIELD_COLOR_LABEL', 'a.color', $listDirn, $listOrder); ?>
					</th>
					<th width="5%" class="nowrap hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'JAUTHOR', 'a.created_by', $listDirn, $listOrder); ?>
					</th>
					<th width="5%" class="nowrap hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap">
						<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="11">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $i => $item)
			{
				$canCreate	= $user->authorise('core.create',		'com_dpcalendar.category.' . $item->catid);
				$canEdit	= $user->authorise('core.edit',			'com_dpcalendar.category.' . $item->catid);
				$canEditOwn = $user->authorise('core.edit.own',     'com_dpcalendar.category.' . $item->catid);
				$canCheckin	= $user->authorise('core.manage',		'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
				$canChange	= $user->authorise('core.edit.state',	'com_dpcalendar.category.' . $item->catid) && $canCheckin;
				?>
				<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid?>">
					<td class="center hidden-phone">
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					</td>
					<td class="center">
						<div class="btn-group">
							<?php echo JHtml::_('jgrid.published', $item->state, $i, 'events.', $canChange, 'cb', $item->publish_up, $item->publish_down); ?>
							<?php
							$states	= array(
									0	=> array('star-empty',	'events.featured',	'COM_DPCALENDAR_VIEW_EVENTS_UNFEATURED',	'COM_DPCALENDAR_VIEW_EVENTS_TOGGLE_TO_FEATURE'),
									1	=> array('star',		'events.unfeatured',	'COM_DPCALENDAR_FEATURED',		'COM_DPCALENDAR_VIEW_EVENTS_TOGGLE_TO_UNFEATURE'),
							);
							$state	= JArrayHelper::getValue($states, (int) $item->featured, $states[1]);
							$icon	= $state[0];
							if ($canChange)
							{
								echo '<a href="#" onclick="return listItemTask(\'cb' . $i . '\',\'' . $state[1] . '\')" class="btn btn-micro ' .
									($item->featured == 1 ? 'active' : '') . '"
									rel="tooltip" title="' . JText::_($state[3]) . '"><i class="icon-'
									. $icon . '"></i></a>';
							}
							?>
						</div>
					</td>
					<td class="nowrap has-context">
						<div class="pull-left">
							<?php if ($item->checked_out)
							{ ?>
								<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'events.', $canCheckin); ?>
							<?php
							} ?>
							<?php if ($item->language == '*')
							{?>
								<?php $language = JText::alt('JALL', 'language'); ?>
							<?php
							} else
							{?>
								<?php $language = $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
							<?php
							}?>
							<?php if ($canEdit || $canEditOwn)
							{ ?>
								<a href="<?php echo JRoute::_('index.php?option=com_dpcalendar&task=event.edit&e_id=' . $item->id);?>"
									title="<?php echo JText::_('JACTION_EDIT');?>">
									<?php echo $this->escape($item->title); ?></a>
							<?php
							} else
							{ ?>
								<span title="<?php echo JText::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias));?>">
									<?php echo $this->escape($item->title); ?>
								</span>
							<?php
							} ?>
							<div class="small">
								<?php echo JText::_('COM_DPCALENDAR_CALENDAR') . ": " . $this->escape($item->category_title); ?>
							</div>
						</div>
						<div class="pull-left">
							<?php
								// Create dropdown items
								JHtml::_('dropdown.edit', $item->id, 'event.');

								if ($item->original_id > 0)
								{
									JHtml::_('dropdown.addCustomItem',
									JText::_('COM_DPCALENDAR_VIEW_EVENTS_DROPDOWN_EDIT_ORIGINAL'),
									JRoute::_('index.php?option=com_dpcalendar&task=event.edit&e_id=' . $item->original_id));
								}

								JHtml::_('dropdown.divider');
								if ($item->state)
								{
									JHtml::_('dropdown.unpublish', 'cb' . $i, 'events.');
								}
								else
								{
									JHtml::_('dropdown.publish', 'cb' . $i, 'events.');
								}

								if ($item->featured)
								{
									JHtml::_('dropdown.unfeatured', 'cb' . $i, 'events.');
								}
								else
								{
									JHtml::_('dropdown.featured', 'cb' . $i, 'events.');
								}

								JHtml::_('dropdown.divider');

								if ($archived)
								{
									JHtml::_('dropdown.unarchive', 'cb' . $i, 'events.');
								}
								else
								{
									JHtml::_('dropdown.archive', 'cb' . $i, 'events.');
								}

								if ($item->checked_out)
								{
									JHtml::_('dropdown.checkin', 'cb' . $i, 'events.');
								}

								if ($trashed)
								{
									JHtml::_('dropdown.untrash', 'cb' . $i, 'events.');
								}
								else
								{
									JHtml::_('dropdown.trash', 'cb' . $i, 'events.');
								}

								// Render dropdown list
								echo JHtml::_('dropdown.render');
								?>
						</div>
					</td>
					<td class="small hidden-phone">
						<?php echo DPCalendarHelper::getDateStringFromEvent($item); ?>
					</td>
					<td class="small hidden-phone" style="background: none repeat scroll 0 0 #<?php echo $item->color;?>">
					</td>
					<td class="small hidden-phone">
						<?php echo $this->escape($item->access_level); ?>
					</td>
					<td class="small hidden-phone">
						<?php echo $this->escape($item->author_name); ?>
					</td>
					<td class="small hidden-phone">
						<?php if ($item->language == '*')
						{?>
							<?php echo JText::alt('JALL', 'language'); ?>
						<?php
						}
						else
						{?>
							<?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
						<?php
						}?>
					</td>
					<td class="center hidden-phone">
						<?php echo (int) $item->id; ?>
					</td>
				</tr>
				<?php
				} ?>
			</tbody>
		</table>
	</div>

	<?php echo $this->loadTemplate('batch'); ?>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::_('form.token'); ?>
</form>

<div align="center" style="clear: both">
	<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), JFactory::getApplication()->input->getVar('DPCALENDAR_VERSION'));?>
</div>
