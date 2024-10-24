<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/tickets/default.css');
$this->dpdocument->loadScriptFile('views/tickets/default.js');
$this->dpdocument->addStyle($this->params->get('tickets_custom_css', ''));

if ($this->event) {
	$this->app->enqueueMessage(Text::sprintf('COM_DPCALENDAR_VIEW_TICKETS_SHOW_FROM_EVENT', $this->escape($this->event->title)));
}

// If we have a booking, show an information message
if ($this->booking) {
	$this->app->enqueueMessage(Text::sprintf('COM_DPCALENDAR_VIEW_TICKETS_SHOW_FROM_BOOKING', $this->escape($this->booking->uid)));
}
?>
<div class="com-dpcalendar-tickets<?php echo $this->pageclass_sfx ? ' com-dpcalendar-tickets-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-tickets__custom-text">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('tickets_textbefore', ''))); ?>
	</div>
	<?php echo $this->loadTemplate('form'); ?>
	<?php echo $this->loadTemplate('content'); ?>
	<?php echo $this->loadTemplate('footer'); ?>
	<div class="com-dpcalendar-tickets__custom-text">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('tickets_textafter', ''))); ?>
	</div>
</div>
