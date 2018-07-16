<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-cpanel__checks">
	<h3 class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_CHECKS'); ?>
	</h3>
	<div class="dp-information-container">
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_CHECKS_VERSION'); ?>: </span>
			<span class="dp-information__content">
				<?php echo $this->input->getString('DPCALENDAR_VERSION'); ?>
			</span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_EXTERNAL_CALENDARS'); ?>: </span>
			<span class="dp-information__content">
				<a href="index.php?option=com_plugins&view=plugins&filter[folder]=dpcalendar&filter[enabled]=1" class="dp-link">
					<?php echo count($this->calendarsExternal); ?>
				</a>
			</span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_INTERNAL_CALENDARS'); ?>: </span>
			<span class="dp-information__content">
				<a href="index.php?option=com_categories&extension=com_dpcalendar" class="dp-link">
					<?php echo count($this->calendarsInternal); ?>
				</a>
			</span>
		</div>
	</div>
</div>
