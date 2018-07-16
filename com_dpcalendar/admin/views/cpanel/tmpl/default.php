<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/cpanel/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/cpanel/default.js');
?>
<div class="com-dpcalendar-cpanel">
	<div id="j-sidebar-container" class="com-dpcalendar-cpanel__sidebar span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="com-dpcalendar-cpanel__content span10">
		<?php echo $this->loadtemplate('icons'); ?>
		<div class="com-dpcalendar-cpanel__welcome">
			<h2 class="dp-heading"><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_WELCOME'); ?></h2>
			<p class="dp-paragraphe"><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_INTRO'); ?></p>
		</div>
		<div class="com-dpcalendar-cpanel__boxes dp-grid">
			<div class="dp-grid__column com-dpcalendar-cpanel__box"><?php echo $this->loadtemplate('events_upcoming'); ?></div>
			<div class="dp-grid__column com-dpcalendar-cpanel__box"><?php echo $this->loadtemplate('events_last'); ?></div>
			<div class="dp-grid__column com-dpcalendar-cpanel__box"><?php echo $this->loadtemplate('events_new'); ?></div>
		</div>
		<div class="com-dpcalendar-cpanel__boxes dp-grid">
			<div class="dp-grid__column com-dpcalendar-cpanel__box"><?php echo $this->loadtemplate('stats'); ?></div>
			<div class="dp-grid__column com-dpcalendar-cpanel__box"><?php echo $this->loadtemplate('checks'); ?></div>
			<div class="dp-grid__column com-dpcalendar-cpanel__box"><?php echo $this->loadtemplate('help'); ?></div>
		</div>
		<?php echo $this->loadtemplate('footer'); ?>
	</div>
</div>
