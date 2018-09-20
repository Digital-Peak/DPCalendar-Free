<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/locations/default.css');
?>
<div class="com-dpcalendar-locations dp-location<?php echo $this->pageclass_sfx ? ' com-dpcalendar-locations-' . $this->pageclass_sfx : ''; ?>s">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-locations__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php echo $this->loadTemplate('resource'); ?>
	<?php echo $this->loadTemplate('map'); ?>
	<?php if ($this->params->get('locations_expand', 1) == 0) { ?>
		<?php echo $this->loadTemplate('details_simple'); ?>
	<?php } ?>
	<?php if ($this->params->get('locations_expand', 1) == 1) { ?>
		<?php echo $this->loadTemplate('details_limited'); ?>
	<?php } ?>
	<?php if ($this->params->get('locations_expand', 1) == 2) { ?>
		<?php echo $this->loadTemplate('details_full'); ?>
	<?php } ?>
	<?php echo $this->loadTemplate('events'); ?>
</div>
