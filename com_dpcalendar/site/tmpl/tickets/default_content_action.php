<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
?>
<?php if ($this->ticket->state == 1) { ?>
	<button type="button" class="dp-button dp-button-action dp-button-checkin"
		data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=ticket.checkin&uid=' . $this->ticket->uid); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK, 'title' => $this->translate('COM_DPCALENDAR_VIEW_TICKETS_ACTION_CHECKIN')]); ?>
	</button>
<?php } ?>
<?php if ($this->ticket->state == 9) { ?>
	<button type="button" class="dp-button dp-button-action dp-button-download-certificate"
		data-href="<?php echo $this->router->route('index.php?option=com_dpcalendar&task=ticket.certificatedownload&uid=' . $this->ticket->uid); ?>">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CERTIFICATE, 'title' => $this->translate('COM_DPCALENDAR_VIEW_TICKETS_ACTION_DOWNLOAD_CERTIFICATE')]); ?>
	</button>
<?php } ?>
