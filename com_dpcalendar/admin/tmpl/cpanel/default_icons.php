<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
?>
<div class="com-dpcalendar-cpanel__icons">
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=events">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::LISTING]); ?>
			<span class="dp-quick-icon__text"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_EVENTS'); ?></span>
		</a>
	</div>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=event&layout=edit">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::PLUS]); ?>
			<span class="dp-quick-icon__text"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_ADD_EVENT'); ?></span>
		</a>
	</div>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_categories&extension=com_dpcalendar">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CALENDAR]); ?>
			<span class="dp-quick-icon__text"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_CALENDARS'); ?></span>
		</a>
	</div>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=locations">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::LOCATION]); ?>
			<span class="dp-quick-icon__text"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_LOCATIONS'); ?></span>
		</a>
	</div>
	<?php if (!DPCalendarHelper::isFree()) { ?>
		<div class="dp-quick-icon">
			<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=bookings">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::USERS]); ?>
				<span class="dp-quick-icon__text"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_BOOKINGS'); ?></span>
			</a>
		</div>
	<?php } ?>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=tools">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::COG]); ?>
			<span class="dp-quick-icon__text"><?php echo $this->translate('COM_DPCALENDAR_SUBMENU_TOOLS'); ?></span>
		</a>
	</div>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=tools&layout=translate">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::LANGUAGE]); ?>
			<span class="dp-quick-icon__text"><?php echo $this->translate('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE'); ?></span>
		</a>
	</div>
	<?php if (!DPCalendarHelper::isFree() && $this->needsGeoDBUpdate) { ?>
		<div class="dp-quick-icon">
			<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&task=import.geodb">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DATABASE]); ?>
				<span class="dp-icon-overlay">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EXCLAMATION]); ?>
				</span>
				<span class="dp-quick-icon__text"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_UPDATE_GEO'); ?></span>
			</a>
		</div>
	<?php } ?>
</div>
