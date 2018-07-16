<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

// Load the required assets
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FULLCALENDAR);

if ($this->params->get('show_event_as_popup')) {
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MODAL);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/calendar/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/calendar/default.js');

$this->params->set('header_show_print', false);

$this->loadTemplate('options');
?>
<div class="com-dpcalendar-calendar com-dpcalendar-calendar_printable">
	<div class="com-dpcalendar-calendar__custom-text">
		<?php echo JHtml::_('content.prepare', $this->translate($this->params->get('textbefore'))); ?>
	</div>
	<div class="com-dpcalendar-calendar__content">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
		<?php echo $this->loadTemplate('list'); ?>
		<div class="com-dpcalendar-calendar__calendar dp-calendar" data-options="DPCalendar.view.calendar.options"></div>
		<?php echo $this->loadTemplate('map'); ?>
	</div>
	<div class="com-dpcalendar-calendar__custom-text">
		<?php echo JHtml::_('content.prepare', $this->translate($this->params->get('textafter'))); ?>
	</div>
</div>
