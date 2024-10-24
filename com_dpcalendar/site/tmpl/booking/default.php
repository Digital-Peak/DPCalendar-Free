<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/booking/default.css');
$this->dpdocument->loadScriptFile('views/booking/default.js');
$this->dpdocument->addStyle($this->params->get('booking_custom_css', ''));
?>
<div class="com-dpcalendar-booking com-dpcalendar-booking-default<?php echo $this->pageclass_sfx ? ' com-dpcalendar-booking-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('header'); ?>
	<div class="com-dpcalendar-booking__event-text">
		<?php echo $this->booking->displayEvent->beforeDisplayContent; ?>
	</div>
	<?php echo $this->loadTemplate('waiting'); ?>
	<?php echo $this->loadTemplate('content'); ?>
	<?php echo $this->loadTemplate('tickets'); ?>
	<div class="com-dpcalendar-booking__event-text">
		<?php echo $this->booking->displayEvent->afterDisplayContent; ?>
	</div>
	<?php echo $this->loadTemplate('registration'); ?>
</div>
