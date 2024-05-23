<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Location;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canOrder  = $this->user->authorise('core.edit.state', 'com_dpcalendar');
$format    = DPCalendarHelper::getComponentParameter('event_date_format', 'd.m.Y') . ' '
	. DPCalendarHelper::getComponentParameter('event_time_format', 'H:i');
$return    = '&return=' . base64_encode(Uri::getInstance()->toString());
?>
<div class="com-dpcalendar-bookings__bookings">
	<table class="dp-table" id="bookingList">
		<thead>
		<tr>
			<td class="dp-table__col-check"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_UID', 'a.uid', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL', 'a.price', $listDirn, $listOrder); ?></th>
			<th class="dp-table__col-status"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_USERNAME', 'user_name', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_CREATED_DATE', 'a.book_date', $listDirn, $listOrder); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_EVENT'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_INVOICE'); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $i => $item) { ?>
			<?php $canEdit = $this->user->authorise('core.edit', 'com_dpcalendar'); ?>
			<?php $canChange = $this->user->authorise('core.edit.state', 'com_dpcalendar'); ?>
			<tr sortable-group-id="">
				<td data-column="<?php echo $this->translate('JGLOBAL_CHECK_ALL'); ?>"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_UID'); ?>">
					<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.edit&b_id=' . (int)$item->id); ?>">
						<?php echo $item->uid; ?>
					</a>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?>">
					<?php echo DPCalendarHelper::renderPrice($item->price); ?>
				</td>
				<td data-column="<?php echo $this->translate('JSTATUS'); ?>">
					<?php echo Booking::getStatusLabel($item); ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_NAME_LABEL'); ?>">
					<?php echo $this->escape($item->name); ?>
					<span><?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_EMAIL_LABEL') . ': ' . $this->escape($item->email); ?></span>
					<div><?php echo $this->escape($item->telephone); ?></div>
				</td>
				<td data-column="<?php echo $this->translate('JGLOBAL_USERNAME'); ?>">
					<a href="<?php echo $this->router->route('index.php?option=com_users&task=user.edit&id=' . $item->user_id . $return); ?>">
						<?php echo $this->escape($item->user_name); ?>
					</a>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_CREATED_DATE'); ?>">
					<?php echo DPCalendarHelper::getDate($item->book_date)->format($format, true); ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?>">
					<?php echo $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo','Administrator')->format($item); ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_EVENT'); ?>">
					<?php foreach ($item->tickets as $ticket) { ?>
						<a href="<?php echo \DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper::getFormRoute($ticket->event_id, Uri::getInstance()->toString()); ?>">
							<?php echo $this->escape($ticket->event_title); ?>
						</a>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_ACTION'); ?>">
					<span class="hasTooltip dp-button"
						  title="<?php echo $this->translate('COM_DPCALENDAR_DOWNLOAD'); ?>">
						<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.invoice&b_id=' . $item->id); ?>"
						   class="dp-action-download">
							<i class="icon-download"></i>
						</a>
					</span>
					<span class="hasTooltip dp-button" title="<?php echo $this->translate('COM_DPCALENDAR_SEND'); ?>">
						<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=booking.invoicesend&b_id=' . $item->id . $return); ?>" class="dp-action-send">
							<i class="icon-mail"></i>
						</a>
					</span>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ID'); ?>"><?php echo (int)$item->id; ?></td>
			</tr>
		<?php } ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="9"><?php echo $this->pagination->getListFooter(); ?></td>
		</tr>
		</tfoot>
	</table>
</div>
