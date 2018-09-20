<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

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
