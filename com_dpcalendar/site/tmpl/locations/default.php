<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

if ($this->params->get('locations_show_map', 1) && $this->params->get('map_provider', 'openstreetmap') != 'none') {
	$this->layoutHelper->renderLayout('block.map', $this->displayData);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/locations/default.css');
$this->dpdocument->loadScriptFile('views/locations/default.js');
$this->dpdocument->addStyle($this->params->get('locations_custom_css', ''));
?>
<div class="com-dpcalendar-locations dp-location<?php echo $this->pageclass_sfx ? ' com-dpcalendar-locations-' . $this->pageclass_sfx : ''; ?>s">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-locations__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php echo $this->loadTemplate('resource'); ?>
	<?php echo $this->loadTemplate('map'); ?>
	<?php if ($this->params->get('locations_expand', 2) == 0) { ?>
		<?php echo $this->loadTemplate('details_simple'); ?>
	<?php } ?>
	<?php if ($this->params->get('locations_expand', 2) == 1) { ?>
		<?php echo $this->loadTemplate('details_limited'); ?>
	<?php } ?>
	<?php if ($this->params->get('locations_expand', 2) == 2) { ?>
		<?php echo $this->loadTemplate('details_full'); ?>
	<?php } ?>
	<?php echo $this->loadTemplate('events'); ?>
	<?php echo $this->loadTemplate('icons'); ?>
</div>
