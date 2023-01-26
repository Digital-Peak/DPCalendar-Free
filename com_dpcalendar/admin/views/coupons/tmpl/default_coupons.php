<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canOrder  = $this->user->authorise('core.edit.state', 'com_dpcalendar');
$saveOrder = $listOrder == 'a.ordering';
if ($saveOrder) {
	$saveOrderingUrl = 'index.php?option=com_dpcalendar&task=coupons.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'couponsList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
$format = DPCalendarHelper::getComponentParameter('event_date_format', 'd.m.Y') . ' '
	. DPCalendarHelper::getComponentParameter('event_time_format', 'H:i');
$return = '&return=' . base64_encode(Uri::getInstance()->toString());
?>
<div class="com-dpcalendar-coupons__coupons">
	<table class="dp-table dp-coupons-table" id="couponList">
		<thead>
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
		<th class="dp-table__col-check">
			<input type="checkbox" name="checkall-toggle" value="" title="<?php echo $this->translate('JGLOBAL_CHECK_ALL'); ?>"
				   class="dp-input dp-input-checkbox dp-input-check-all"/>
		</th>
		<th class="dp-table__col-state"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?></th>
		<th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
		<th><?php echo HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'a.author_name', $listDirn, $listOrder); ?></th>
		<th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_CREATED', 'a.created', $listDirn, $listOrder); ?></th>
		<th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
		</tr>
		</thead>
		<tbody <?php if ($saveOrder) { ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php  } ?>>
		<?php foreach ($this->items as $i => $item) { ?>
			<?php $ordering = $listOrder == 'a.ordering'; ?>
			<?php $canCreate = $this->user->authorise('core.create', 'com_dpcalendar'); ?>
			<?php $canEdit = $this->user->authorise('core.edit', 'com_dpcalendar'); ?>
			<?php $canCheckin = $this->user->authorise('core.manage', 'com_checkin')
				|| $item->checked_out == $this->user->get('id') || $item->checked_out == 0; ?>
			<?php $canChange = $this->user->authorise('core.edit.state', 'com_dpcalendar') && $canCheckin; ?>
			<tr sortable-group-id="" data-draggable-group="0" class="dp-coupon">
				<td class="order" data-column="<?php echo $this->translate('JGRID_HEADING_ORDERING'); ?>">
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
						'coupons.',
						$canChange,
						'cb',
						$item->publish_up,
						$item->publish_down
					); ?>
				</td>
				<td class="dp-table__col-expand" data-column="<?php echo $this->translate('JGLOBAL_TITLE'); ?>">
					<?php if ($item->checked_out) { ?>
						<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'coupons.', $canCheckin); ?>
					<?php } ?>
					<?php if ($canEdit) { ?>
						<a href="<?php echo Route::_('index.php?option=com_dpcalendar&task=coupon.edit&co_id=' . (int)$item->id); ?>">
							<?php echo $this->escape($item->title); ?>
						</a>
					<?php } else { ?>
						<?php echo $this->escape($item->title); ?>
					<?php } ?>
					<span><?php echo $this->translate('COM_DPCALENDAR_FIELD_COUPON_CODE_LABEL') . ': ' . $this->escape($item->code); ?></span>
				</td>
				<td data-column="<?php echo $this->translate('JAUTHOR'); ?>">
					<a href="<?php echo $this->router->route('index.php?option=com_users&task=user.edit&id=' . $item->created_by . $return); ?>">
						<?php echo $this->escape($item->author_name); ?>
					</a>
				</td>
				<td data-column="<?php echo $this->translate('JGLOBAL_CREATED'); ?>">
					<?php echo DPCalendarHelper::getDate($item->created)->format($format, true); ?>
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
