<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/booking/pay.css');
$this->dpdocument->addStyle($this->params->get('booking_custom_css', ''));

$button = $this->app->triggerEvent('onDPPaymentNew', [$this->booking]);
?>
<div class="com-dpcalendar-booking com-dpcalendar-booking-pay<?php echo $this->pageclass_sfx ? ' com-dpcalendar-booking-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('steps'); ?>
	<?php foreach ($button as $button) { ?>
		<div class="com-dpcalendar-booking__payment"><?php echo $button; ?></div>
	<?php } ?>
</div>
