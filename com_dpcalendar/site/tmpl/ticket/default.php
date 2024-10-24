<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/ticket/default.css');
$this->dpdocument->loadScriptFile('views/ticket/default.js');
$this->dpdocument->addStyle($this->params->get('ticket_custom_css', ''));
?>
<div class="com-dpcalendar-ticket<?php echo $this->pageclass_sfx ? ' com-dpcalendar-ticket-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('header'); ?>
	<div class="com-dpcalendar-ticket__event-text">
		<?php echo $this->ticket->displayEvent->beforeDisplayContent; ?>
	</div>
	<?php echo $this->loadTemplate('content'); ?>
	<?php echo $this->loadTemplate('qrcode'); ?>
	<div class="com-dpcalendar-ticket__event-text">
		<?php echo $this->ticket->displayEvent->afterDisplayContent; ?>
	</div>
</div>
