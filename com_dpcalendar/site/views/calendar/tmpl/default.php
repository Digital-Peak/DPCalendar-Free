<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FULLCALENDAR);

if ($this->params->get('show_event_as_popup')) {
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MODAL);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/calendar/default.css');

$this->loadTemplate('options');
?>
<div class="com-dpcalendar-calendar<?php echo $this->pageclass_sfx ? ' com-dpcalendar-calendar-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-calendar__custom-text">
		<?php echo JHtml::_('content.prepare', $this->translate($this->params->get('textbefore'))); ?>
	</div>
	<div class="com-dpcalendar-calendar__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php echo $this->loadTemplate('list'); ?>
	<?php echo $this->loadTemplate('calendar'); ?>
	<?php echo $this->loadTemplate('map'); ?>
	<div class="com-dpcalendar-calendar__custom-text">
		<?php echo JHtml::_('content.prepare', $this->translate($this->params->get('textafter'))); ?>
	</div>
	<?php echo $this->loadTemplate('quickadd'); ?>
</div>
