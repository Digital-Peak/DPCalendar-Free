<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/booking/order.css');
$this->dpdocument->loadScriptFile('views/booking/order.js');
$this->dpdocument->addStyle($this->params->get('booking_custom_css', ''));
?>
<div class="com-dpcalendar-booking com-dpcalendar-booking-order<?php echo $this->pageclass_sfx ? ' com-dpcalendar-booking-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('steps'); ?>
	<?php echo $this->loadTemplate('header'); ?>
	<h3 class="com-dpcalendar-booking__heading dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_BOOKING_MESSAGE_THANKYOU'); ?>
	</h3>
	<?php echo $this->loadTemplate('content'); ?>
	<?php echo $this->loadTemplate('registration'); ?>
</div>
