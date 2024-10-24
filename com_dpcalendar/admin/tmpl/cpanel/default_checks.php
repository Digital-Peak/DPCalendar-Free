<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
?>
<div class="com-dpcalendar-cpanel__checks">
	<h3 class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_CHECKS'); ?>
	</h3>
	<div class="dp-information-container">
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_EXTERNAL_CALENDARS'); ?>: </span>
			<span class="dp-information__content">
				<a href="index.php?option=com_plugins&view=plugins&filter[folder]=dpcalendar&filter[enabled]=1" class="dp-link">
					<?php echo is_countable($this->calendarsExternal) ? count($this->calendarsExternal) : 0; ?>
				</a>
			</span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_INTERNAL_CALENDARS'); ?>: </span>
			<span class="dp-information__content">
				<a href="index.php?option=com_categories&extension=com_dpcalendar" class="dp-link">
					<?php echo is_countable($this->calendarsInternal) ? count($this->calendarsInternal) : 0; ?>
				</a>
			</span>
		</div>
		<div class="dp-information dp-grid">
			<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_CHECKS_VERSION'); ?>: </span>
			<span class="dp-information__content">
				<span class="dp-information__version-current">
					<?php echo $this->input->getString('DPCALENDAR_VERSION', ''); ?>
				</span>
				<span class="dp-information__version-actual"></span>
				<span class="dp-information__version-update">
					<a href="index.php?option=com_installer&view=update">
					<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DOWNLOAD]); ?>
						<?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_UPDATE_EXTENSION'); ?>
					</a>
				</span>
				<span class="dp-information__version-no-update">
					<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
				</span>
			</span>
		</div>
		<?php if ($this->extensionsWithDifferentVersion) { ?>
			<div class="dp-information dp-grid">
				<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_CHECKS_VERSION_EXTENSIONS'); ?>: </span>
				<span class="dp-information__content">
					<?php foreach ($this->extensionsWithDifferentVersion as $extension) { ?>
						<p><?php echo $extension->version . ' ' . $extension->name; ?></p>
					<?php } ?>
				</span>
			</div>
		<?php } ?>
	</div>
</div>
