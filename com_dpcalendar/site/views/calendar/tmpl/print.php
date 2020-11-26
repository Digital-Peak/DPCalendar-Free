<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($this->params->get('show_map', 1) == 1) {
	$this->layoutHelper->renderLayout('block.map', $this->displayData);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/calendar/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/calendar/default.js');

$this->params->set('header_show_print', false);
$this->params->set('header_show_create', false);

$this->loadTemplate('options');
?>
<div class="com-dpcalendar-calendar com-dpcalendar-calendar_printable">
	<div class="com-dpcalendar-calendar__custom-text">
		<?php echo JHtml::_('content.prepare', $this->translate($this->params->get('textbefore'))); ?>
	</div>
	<div class="com-dpcalendar-calendar__content">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
		<?php echo $this->loadTemplate('list'); ?>
		<div class="com-dpcalendar-calendar__calendar dp-calendar"
			 data-options="DPCalendar.view.calendar.<?php echo $this->input->getInt('Itemid', 0); ?>.options"></div>
		<?php echo $this->loadTemplate('map'); ?>
		<?php echo $this->loadTemplate('icons'); ?>
	</div>
	<div class="com-dpcalendar-calendar__custom-text">
		<?php echo JHtml::_('content.prepare', $this->translate($this->params->get('textafter'))); ?>
	</div>
</div>
