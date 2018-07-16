<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-cpanel__icons">
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=events">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LISTING]); ?>
			<span class="dp-quick-icon__text"><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_EVENTS'); ?></span>
		</a>
	</div>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=event&layout=edit">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PLUS]); ?>
			<span class="dp-quick-icon__text"><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_ADD_EVENT'); ?></span>
		</a>
	</div>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_categories&extension=com_dpcalendar">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::CALENDAR]); ?>
			<span class="dp-quick-icon__text"><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_CALENDARS'); ?></span>
		</a>
	</div>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=locations">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LOCATION]); ?>
			<span class="dp-quick-icon__text"><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_LOCATIONS'); ?></span>
		</a>
	</div>
	<?php if (!DPCalendarHelper::isFree()) {
		?>
		<div class="dp-quick-icon">
			<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=bookings">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::USERS]); ?>
				<span class="dp-quick-icon__text"><?php echo JText::_('COM_DPCALENDAR_VIEW_CPANEL_BOOKINGS'); ?></span>
			</a>
		</div>
		<?php
	} ?>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=tools">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::COG]); ?>
			<span class="dp-quick-icon__text"><?php echo JText::_('COM_DPCALENDAR_SUBMENU_TOOLS'); ?></span>
		</a>
	</div>
	<div class="dp-quick-icon">
		<a class="dp-link dp-quick-icon__link" href="index.php?option=com_dpcalendar&view=tools&layout=translate">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LANGUAGE]); ?>
			<span class="dp-quick-icon__text"><?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE'); ?></span>
		</a>
	</div>
</div>
