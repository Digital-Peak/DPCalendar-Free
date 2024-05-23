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
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$this->dpdocument->loadStyleFile('dpcalendar/views/adminlist/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/adminlist/default.js');
$this->dpdocument->addScriptOptions('adminlist', ['listOrder' => $this->state->get('list.ordering')]);

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$canOrder  = $this->user->authorise('core.edit.state', 'com_dpcalendar');
$return    = '&return=' . base64_encode(Uri::getInstance()->toString());
?>
<div class="com-dpcalendar-tickets__tickets">
	<table class="dp-table" id="ticketsList">
		<thead>
		<tr>
			<td class="dp-table__col-check"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_UID', 'a.uid', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL', 'a.price', $listDirn, $listOrder); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_FIELD_EARLYBIRD_TYPE_LABEL'); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'COM_DPCALENDAR_EVENT', 'event_title', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_USERNAME', 'booking_name', $listDirn, $listOrder); ?></th>
			<th><?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'e.start_date', $listDirn, $listOrder); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?></th>
			<th><?php echo $this->translate('COM_DPCALENDAR_ACTION'); ?></th>
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
					<a href="<?php echo Route::_('index.php?option=com_dpcalendar&task=ticket.edit&t_id=' . (int)$item->id) ?>">
						<?php echo $this->escape($item->uid); ?>
					</a>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_PRICE_LABEL'); ?>">
					<?php echo $item->price ? \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::renderPrice($item->price) : ''; ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_FIELD_EARLYBIRD_TYPE_LABEL'); ?>">
					<?php $prices = is_string($item->event_prices) ? json_decode($item->event_prices) : ''; ?>
					<?php echo $prices && array_key_exists($item->type, $prices->label) ? $prices->label[$item->type] : '' ?>
				</td>
				<td data-column="<?php echo $this->translate('JSTATUS'); ?>"><?php echo Booking::getStatusLabel($item); ?></td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_TICKET_FIELD_NAME_LABEL'); ?>">
					<?php echo $this->escape($item->name); ?>
				</td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_EVENT'); ?>">
					<?php if ($canEdit) { ?>
						<a href="<?php echo \DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper::getFormRoute($item->event_id, Uri::getInstance()->toString()); ?>">
							<?php echo $this->escape($item->event_title); ?>
						</a>
					<?php } else { ?>
						<?php echo $this->escape($item->event_title); ?>
					<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JGLOBAL_USERNAME'); ?>">
					<a href="<?php echo $this->router->route('index.php?option=com_users&task=user.edit&id=' . $item->user_id . $return); ?>">
						<?php echo $this->escape($item->user_name); ?>
					</a>
				</td>
				<td data-column="<?php echo $this->translate('JDATE'); ?>"><?php echo \DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper::getDateStringFromEvent($item); ?></td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?>"><?php echo $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo','Administrator')->format($item); ?></td>
				<td data-column="<?php echo $this->translate('COM_DPCALENDAR_ACTION'); ?>">
				<span class="hasTooltip btn btn-small btn-default btn-sm"
					title="<?php echo $this->translate('COM_DPCALENDAR_DOWNLOAD'); ?>">
					<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=ticket.pdfdownload&uid=' . $item->uid); ?>"
						class="dp-action-download">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DOWNLOAD]); ?>
					</a>
				</span>
				<span class="hasTooltip btn btn-small btn-default btn-sm" title="<?php echo $this->translate('COM_DPCALENDAR_SEND'); ?>">
					<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=ticket.pdfsend&uid=' . $item->uid . $return); ?>" class="dp-action-send">
						<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::MAIL]); ?>
					</a>
				</span>
				<?php if ($item->state == 9) { ?>
					<span class="hasTooltip btn btn-small btn-default btn-sm" title="<?php echo $this->translate('COM_DPCALENDAR_CERTIFICATE'); ?>">
						<a href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=ticket.certificatedownload&uid=' . $item->uid); ?>"
							class="dp-action-certificate">
							<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CERTIFICATE]); ?>
						</a>
					</span>
				<?php } ?>
				</td>
				<td data-column="<?php echo $this->translate('JGRID_HEADING_ID'); ?>"><?php echo (int)$item->id; ?></td>
			</tr>
		<?php } ?>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="12">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
		</tfoot>
	</table>
</div>
