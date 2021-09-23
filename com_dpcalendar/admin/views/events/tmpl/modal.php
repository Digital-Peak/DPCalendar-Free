<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$this->dpdocument->loadStyleFile('dpcalendar/views/adminlist/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/adminlist/default.js');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$function = $this->input->getCmd('function', 'jSelectEvent');
?>
<div class="com-dpcalendar-events com-dpcalendar-events-modal com-dpcalendar-adminlist">
	<form action="<?php echo Route::_('index.php?option=com_dpcalendar&view=events&layout=modal&tmpl=component&function=' . $function . '&' . Session::getFormToken() . '=1'); ?>"
		  method="post" name="adminForm" id="adminForm">
		<?php echo $this->loadTemplate('filters'); ?>
		<table class="dp-table" class="adminlist">
			<thead>
				<tr>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_STATE', 'a.state', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_START', 'a.start_date', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_VIEW_EVENTS_MODAL_COLUMN_END', 'a.end_date', $listDirn, $listOrder); ?></th>
					<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_BOOKING_FIELD_ID_LABEL', 'a.id', $listDirn, $listOrder); ?></th>
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
						<td><?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'events.'); ?></td>
						<td><?php echo DPCalendarHelper::getDate($item->start_date, $item->all_day); ?></td>
						<td><?php echo DPCalendarHelper::getDate($item->end_date, $item->all_day); ?></td>
						<td><?php echo $item->id; ?></td>
					</tr>
				<?php } ?>
				</tbody>
			<tfoot>
				<tr><td colspan="6"><?php echo $this->pagination->getListFooter(); ?></td></tr>
			</tfoot>
		</table>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
