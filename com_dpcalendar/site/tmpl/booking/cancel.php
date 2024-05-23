<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/booking/cancel.css');
$this->dpdocument->addStyle($this->params->get('booking_custom_css', ''));
?>
<div class="com-dpcalendar-booking com-dpcalendar-booking-cancel<?php echo $this->pageclass_sfx ? ' com-dpcalendar-booking-' . $this->pageclass_sfx : ''; ?>">
	<h3 class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_CANCEL_' . ($this->booking && $this->booking->id ? '' : 'PAY_') . 'HEADING'); ?>
	</h3>
	<div class="com-dpcalendar-booking__text-canceled"><?php echo $this->cancelText; ?></div>
</div>
