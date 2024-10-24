<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Location;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canOrder  = $this->user->authorise('core.edit.state', 'com_dpcalendar');
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder) {
	$saveOrderingUrl = 'index.php?option=com_dpcalendar&task=locations.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'locationsList', 'adminForm', strtolower((string) $listDirn), $saveOrderingUrl);
}
?>
<div class="com-dpcalendar-locations__locations">
	<table class="dp-table dp-locations-table" id="locationList">
		<thead>
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
		<th class="dp-table__col-state"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?></th>
		<th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
		<th><?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_DETAILS'); ?></th>
		<th class="dp-table__col-color">
			<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_FIELD_COLOR_LABEL', 'a.color', $listDirn, $listOrder); ?>
		</th>
		<th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?></th>
		<th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
		</tr>
		</thead>
		<tbody <?php if ($saveOrder) { ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower((string) $listDirn); ?>" data-nested="false"<?php  } ?>>
		<?php foreach ($this->items as $i => $item) { ?>
			<?php $ordering = $listOrder == 'a.ordering'; ?>
			<?php $canCreate = $this->user->authorise('core.create', 'com_dpcalendar'); ?>
			<?php $canEdit = $this->user->authorise('core.edit', 'com_dpcalendar'); ?>
			<?php $canCheckin = $this->user->authorise('core.manage', 'com_checkin')
				|| $item->checked_out == $this->user->get('id') || $item->checked_out == 0; ?>
			<?php $canChange = $this->user->authorise('core.edit.state', 'com_dpcalendar') && $canCheckin; ?>
			<tr sortable-group-id="" data-draggable-group="0" data-draggable-group="0" class="dp-location">
				<td data-column="<?php echo $this->translate('JGLOBAL_CHECK_ALL'); ?>"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td class="order" data-column="<?php echo $this->translate('JGRID_HEADING_ORDERING'); ?>">
					<?php if ($canChange) { ?>
						<span class="sortable-handler <?php echo $saveOrder ? '' : 'inactive tip-top'; ?>"><i class="icon-menu"></i></span>
						<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>"
							   class="width-20 text-area-order"/>
					<?php } else { ?>
						<span class="sortable-handler inactive"><i class="icon-menu"></i></span>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JSTATUS'); ?>">
					<?php echo HTMLHelper::_(
						'jgrid.published',
						$item->state,
						$i,
						'locations.',
						$canChange,
						'cb',
						$item->publish_up,
						$item->publish_down
					); ?>
				</td>
				<td class="dp-table__col-expand" data-column="<?php echo $this->translate('JGLOBAL_TITLE'); ?>">
					<?php if ($item->checked_out) { ?>
						<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'locations.', $canCheckin); ?>
					<?php } ?>
					<?php if ($canEdit) { ?>
						<a href="<?php echo Route::_('index.php?option=com_dpcalendar&task=location.edit&l_id=' . (int)$item->id); ?>">
							<?php echo $this->escape($item->title); ?>
						</a>
					<?php } else { ?>
						<?php echo $this->escape($item->title); ?>
					<?php } ?>
					<span><?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?></span>
					<div><a href="<?php echo $this->escape($item->url); ?>"><?php echo $this->escape($item->url); ?></a></div>
				</td>
				<td class="dp-table__col-expand" data-column="<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_DETAILS'); ?>">
					<?php echo $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo','Administrator')->format([$item]); ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_FIELD_COLOR_LABEL'); ?>">
					<?php $color = $item->color ?: \Joomla\CMS\Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($item->catid)->color; ?>
					<div style="background: none repeat scroll 0 0 #<?php echo $color; ?>; color: #<?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::getOppositeBWColor($color); ?>"
						 class="dp-location__color">
						<?php echo $this->escape($color); ?>
					</div>
				</td>
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
		</tbody>
		<tfoot>
		<tr>
			<td colspan="10"><?php echo $this->pagination->getListFooter(); ?></td>
		</tr>
		</tfoot>
	</table>
</div>
