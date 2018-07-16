<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadStyleFile('dpcalendar/views/event/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/event/default.js');

$contentClass = '';
if ($imageContent = $this->loadTemplate('image_full')) {
	$contentClass .= ' has-image';
}
if ($informationContent = $this->loadTemplate('information')) {
	$contentClass .= ' has-information';
}
if ($headerContent = $this->loadTemplate('header')) {
	$contentClass .= ' has-header';
}
if ($ctaContent = $this->loadTemplate('cta')) {
	$contentClass .= ' has-cta';
}
if ($descriptionContent = $this->loadTemplate('description')) {
	$contentClass .= ' has-description';
}
if ($tagsContent = $this->loadTemplate('tags')) {
	$contentClass .= ' has-tags';
}
if ($bookingsContent = $this->loadTemplate('bookings')) {
	$contentClass .= ' has-bookings';
}
if ($ticketsContent = $this->loadTemplate('tickets')) {
	$contentClass .= ' has-tickets';
}
if ($locationsContent = $this->loadTemplate('locations')) {
	$contentClass .= ' has-locations';
}
?>
<div class="com-dpcalendar-event <?php echo $this->pageclass_sfx ? ' com-dpcalendar-event-' . $this->pageclass_sfx : ''; ?> <?php echo $contentClass; ?>">
	<div class="com-dpcalendar-event__header">
		<?php echo $this->loadTemplate('title'); ?>
		<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
		<div class="com-dpcalendar-event__custom-text">
			<?php echo JHtml::_('content.prepare', $this->translate($this->params->get('event_textbefore'))); ?>
		</div>
	</div>
	<?php echo $imageContent; ?>
	<?php echo $informationContent; ?>
	<?php echo $headerContent; ?>
	<?php echo $ctaContent; ?>
	<?php echo $descriptionContent; ?>
	<?php echo $tagsContent; ?>
	<?php echo $bookingsContent; ?>
	<?php echo $locationsContent; ?>
	<?php echo $ticketsContent; ?>
	<div class="com-dpcalendar-event__custom-text">
		<?php echo JHtml::_('content.prepare', $this->translate($this->params->get('event_textafter'))); ?>
	</div>
	<?php echo $this->layoutHelper->renderLayout('schema.event', $this->displayData); ?>
</div>