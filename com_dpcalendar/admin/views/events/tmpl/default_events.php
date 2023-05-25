<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\HTML\Block\Icon;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$archived  = $this->state->get('filter.state') == 2;
$trashed   = $this->state->get('filter.state') == -2;
$canOrder  = $this->user->authorise('core.edit.state', 'com_dpcalendar.category');
$return    = '&return=' . base64_encode(Uri::getInstance()->toString());
?>
<div class="com-dpcalendar-events__events">
	<table class="dp-table dp-events-table" id="eventList">
		<thead>
		<tr>
			<td class="dp-table__col-check"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
			<th class="dp-table__col-state"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?></th>
			<th class="dp-table__col-state"><?php echo HTMLHelper::_('searchtools.sort', 'JFEATURED', 'a.featured', $listDirn, $listOrder); ?></th>
			<th class="dp-table__col-expand"><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
			<th class="dp-table__col-date"><?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'a.start_date', $listDirn, $listOrder); ?></th>
			<th class="dp-table__col-color">
				<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_FIELD_COLOR_LABEL', 'a.color', $listDirn, $listOrder); ?>
			</th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'a.created_by', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_VIEW_EVENTS_ORIGINAL_TITLE', 'original_title', $listDirn, $listOrder); ?></th>
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
			<tr sortable-group-id="<?php echo $item->catid ?>" class="dp-event">
				<td data-column=""><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td data-column="<?php echo $this->translate('JSTATUS'); ?>" class="dp-event__state">
					<div class="btn-group">
						<?php echo HTMLHelper::_(
							'jgrid.published',
							$item->state,
							$i,
							'events.',
							$canChange,
							'cb',
							$item->publish_up,
							$item->publish_down
						); ?>
					</div>
				</td>
				<td data-column="<?php echo $this->translate('JFEATURED'); ?>">
					<?php if ($canChange) { ?>
						<?php $state = $this->getState($item); ?>
						<a href="#" data-cb="<?php echo $i; ?>" data-state="<?php echo $state[1]; ?>"
							class="dp-link dp-link-featured btn btn-micro <?php echo($item->featured == 1 ? 'active' : ''); ?>"
							rel="tooltip" title="<?php echo $this->translate($state[3]); ?>">
							<i class="icon-<?php echo $state[0]; ?>"></i>
						</a>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JGLOBAL_TITLE'); ?>">
					<?php if ($item->checked_out) { ?>
						<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'events.', $canCheckin); ?>
					<?php } ?>
					<?php $icon = $item->original_id == -1 ? Icon::RECURRING : ($item->original_id > 0 ? Icon::MULTIPLE : Icon::SINGLE); ?>
					<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => $icon]); ?>
					<?php if ($canEdit || $canEditOwn) { ?>
						<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=event.edit&e_id=' . $item->id); ?>"
						   title="<?php echo $this->translate('JACTION_EDIT'); ?>">
							<?php echo $this->escape($item->title); ?>
						</a>
					<?php } else { ?>
						<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>">
							<?php echo $this->escape($item->title); ?>
						</span>
					<?php } ?>
					<div><?php echo $this->translate('COM_DPCALENDAR_CALENDAR') . ": " . $this->escape($item->category_title); ?></div>
				</td>
				<td data-column="<?php echo $this->translate('JDATE'); ?>">
					<div><?php echo $this->dateHelper->getDateStringFromEvent($item); ?></div>
					<div><?php echo $this->dateHelper->transformRRuleToString($item->rrule ?: $item->original_rrule, $item->start_date); ?></div>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_FIELD_COLOR_LABEL'); ?>">
					<?php $color = $item->color ?: DPCalendarHelper::getCalendar($item->catid)->color; ?>
					<div style="background: none repeat scroll 0 0 #<?php echo $color; ?>; color: #<?php echo DPCalendarHelper::getOppositeBWColor($color); ?>"
						 class="dp-event__color">
						<?php echo $this->escape($color); ?>
					</div>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ACCESS'); ?>"><?php echo $this->escape($item->access_level); ?></td>
				<td data-column="<?php echo $this->translate('JAUTHOR'); ?>">
					<a href="<?php echo $this->router->route('index.php?option=com_users&task=user.edit&id=' . $item->created_by . $return); ?>">
						<?php echo $this->escape($item->author_name); ?>
					</a>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_LANGUAGE'); ?>">
					<?php if ($item->language == '*') { ?>
						<?php echo Text::alt('JALL', 'language'); ?>
					<?php } else { ?>
						<?php echo $item->language_title ? $this->escape($item->language_title) : $this->translate('JUNDEFINED'); ?>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ID'); ?>"><?php echo (int)$item->id; ?></td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_ORIGINAL_TITLE'); ?>">
					<?php if (($canEdit || $canEditOwn) && $item->original_title) { ?>
						<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=event.edit&e_id=' . $item->original_id); ?>"
						   title="<?php echo $this->translate('JACTION_EDIT'); ?>">
							<?php echo $this->escape($item->original_title); ?>
						</a>
					<?php } else if ($item->original_title) { ?>
						<span title="<?php echo Text::sprintf('JFIELD_ALIAS_LABEL', $this->escape($item->alias)); ?>">
							<?php echo $this->escape($item->title); ?>
						</span>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="12"><?php echo $this->pagination->getListFooter(); ?></td>
		</tr>
		</tfoot>
	</table>
</div>
