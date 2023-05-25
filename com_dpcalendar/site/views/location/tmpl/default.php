<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($this->params->get('location_show_map', 1) && $this->params->get('map_provider', 'openstreetmap') != 'none') {
	$this->layoutHelper->renderLayout('block.map', $this->displayData);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/location/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/location/default.js');
$this->dpdocument->addStyle($this->params->get('location_custom_css'));
?>
<div class="com-dpcalendar-location<?php echo $this->pageclass_sfx ? ' com-dpcalendar-location-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('title'); ?>
	<?php echo $this->loadTemplate('header'); ?>
	<div class="com-dpcalendar-location__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php echo $this->loadTemplate('resource'); ?>
	<?php echo $this->loadTemplate('map'); ?>
	<?php echo $this->loadTemplate('details'); ?>
	<?php echo $this->loadTemplate('tags'); ?>
	<?php echo $this->loadTemplate('events'); ?>
	<?php echo $this->loadTemplate('icons'); ?>
</div>
