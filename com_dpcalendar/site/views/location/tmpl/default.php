<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
if ($this->params->get('location_show_resource_view', 1) && !\DPCalendar\Helper\DPCalendarHelper::isFree()) {
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FULLCALENDAR);
	$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_SCHEDULER);
	if ($this->params->get('location_header_show_datepicker', 1)) {
		$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DATEPICKER);
	}
}

$this->dpdocument->loadStyleFile('dpcalendar/views/location/default.css');
?>
<div class="com-dpcalendar-location<?php echo $this->pageclass_sfx ? ' com-dpcalendar-location-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('header'); ?>
	<?php echo $this->loadTemplate('title'); ?>
	<div class="com-dpcalendar-location__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php echo $this->loadTemplate('resource'); ?>
	<?php echo $this->loadTemplate('map'); ?>
	<?php echo $this->loadTemplate('details'); ?>
	<?php echo $this->loadTemplate('events'); ?>
</div>
