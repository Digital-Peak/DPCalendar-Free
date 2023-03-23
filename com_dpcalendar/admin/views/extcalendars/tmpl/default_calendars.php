<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder) {
	$saveOrderingUrl = 'index.php?option=com_dpcalendar&task=extcalendars.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'extcalendarsList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>
<div class="com-dpcalendar-extcalendars__calendars">
	<table class="dp-table" id="extcalendarsList">
		<thead>
		<tr>
			<td class="dp-table__col-check"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
			<th class="dp-table__col-order">
				<?php echo HTMLHelper::_(
					'searchtools.sort',
					'',
					'a.ordering',
					$listDirn,
					$listOrder,
					null,
					'asc',
					'JGRID_HEADING_ORDERING',
					'icon-menu-2'
				); ?>
			</th>
			<th class="dp-table__col-state"><?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?></th>
			<th class="dp-table__col-expand"><?php echo HTMLHelper::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
		</tr>
		</thead>
		<tbody <?php if ($saveOrder) { ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php  } ?>>
		<?php foreach ($this->items as $i => $item) { ?>
			<?php $ordering = $listOrder == 'a.ordering'; ?>
			<?php $canCreate = $this->user->authorise('core.create', 'com_dpcalendar'); ?>
			<?php $canEdit = $this->user->authorise('core.edit', 'com_dpcalendar'); ?>
			<?php $canChange = $this->user->authorise('core.edit.state', 'com_dpcalendar'); ?>
			<tr sortable-group-id="" data-draggable-group="0">
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ORDERING'); ?>">
					<?php if ($canChange) { ?>
						<span class="sortable-handler <?php echo $saveOrder ? '' : 'inactive tip-top'; ?>"><i class="icon-menu"></i></span>
						<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>"
							   class="width-20 text-area-order"/>
					<?php } else { ?>
						<span class="sortable-handler inactive"><i class="icon-menu"></i></span>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JGLOBAL_CHECK_ALL'); ?>"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td data-column="<?php echo $this->translate('JSTATUS'); ?>">
					<?php echo HTMLHelper::_(
						'jgrid.published',
						$item->state,
						$i,
						'extcalendars.',
						$canChange,
						'cb',
						$item->publish_up,
						$item->publish_down
					); ?>
				</td>
				<td data-column="<?php echo $this->translate('JGLOBAL_TITLE'); ?>">
					<?php if ($canEdit) { ?>
						<a href="<?php echo Route::_('index.php?option=com_dpcalendar&task=extcalendar.edit&id=' . (int)$item->id . '&tmpl=' . $this->input->getWord('tmpl') . '&dpplugin=' . $this->input->getWord('dpplugin')); ?>">
							<?php echo $this->escape($item->title); ?></a>
					<?php } else { ?>
						<?php echo $this->escape($item->title); ?>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ACCESS'); ?>"><?php echo $this->escape($item->access_level); ?></td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_LANGUAGE'); ?>">
					<?php if ($item->language == '*') { ?>
						<?php echo Text::alt('JALL', 'language'); ?>
					<?php } else { ?>
						<?php echo $item->language_title ? $this->escape($item->language_title) : $this->translate('JUNDEFINED'); ?>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ID'); ?>"><?php echo (int)$item->id; ?></td>
			</tr>
		<?php } ?>
	</table>
	<tfoot>
	<tr>
		<td colspan="10"><?php echo $this->pagination->getListFooter(); ?></td>
	</tr>
	</tfoot>
</div>
