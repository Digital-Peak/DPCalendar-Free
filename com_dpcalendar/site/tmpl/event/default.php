<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

if ($this->params->get('event_show_map', '1')
	&& $this->event->locations
	&& $this->params->get('event_show_location', '2') && $this->params->get('map_provider', 'openstreetmap') != 'none') {
	$this->layoutHelper->renderLayout('block.map', $this->displayData);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/event/default.css');
$this->dpdocument->loadScriptFile('views/event/default.js');
$this->dpdocument->addStyle($this->params->get('event_custom_css', ''));

$contentClass = '';
if ($imageContent = $this->loadTemplate('image_full')) {
	$contentClass .= ' has-image';
}
if ($taxesContent = $this->loadTemplate('taxes')) {
	$contentClass .= ' has-taxes';
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
if ($bookingFormContent = $this->loadTemplate('booking_form')) {
	$contentClass .= ' has-bookingform';
}
if ($bookingsContent = $this->loadTemplate('bookings')) {
	$contentClass .= ' has-bookings';
}
if ($seriesContent = $this->loadTemplate('series')) {
	$contentClass .= ' has-series';
}
if ($scheduleContent = $this->loadTemplate('schedule')) {
	$contentClass .= ' has-schedule';
}
if ($ticketsContent = $this->loadTemplate('tickets')) {
	$contentClass .= ' has-tickets';
}
if ($locationsContent = $this->loadTemplate('locations')) {
	$contentClass .= ' has-locations';
}
?>
<div class="com-dpcalendar-event <?php echo $this->pageclass_sfx ? ' com-dpcalendar-event-' . $this->pageclass_sfx : ''; ?> <?php echo $contentClass; ?>">
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-event__header">
		<?php if ($this->event->prices) { ?>
			<?php echo $this->layoutHelper->renderLayout('block.currency', $this->displayData); ?>
		<?php } ?>
			<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
		<div class="com-dpcalendar-event__header-title">
			<?php echo $this->loadTemplate('title'); ?>
		</div>
		<div class="com-dpcalendar-event__custom-text com-dpcalendar-event__custom-text-before">
			<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('event_textbefore', ''))); ?>
		</div>
	</div>
	<?php echo $taxesContent; ?>
	<?php echo $imageContent; ?>
	<?php echo $informationContent; ?>
	<?php echo $headerContent; ?>
	<?php echo $ctaContent; ?>
	<?php echo $bookingFormContent; ?>
	<?php echo $descriptionContent; ?>
	<?php echo $bookingsContent; ?>
	<?php echo $seriesContent; ?>
	<?php echo $scheduleContent; ?>
	<?php echo $locationsContent; ?>
	<?php echo $ticketsContent; ?>
	<div class="com-dpcalendar-event__custom-text com-dpcalendar-event__custom-text-after">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('event_textafter', ''))); ?>
	</div>
	<?php echo $this->layoutHelper->renderLayout('schema.event', $this->displayData); ?>
</div>
