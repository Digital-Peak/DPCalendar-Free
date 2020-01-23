<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-location__actions dp-button-bar dp-print-hide">
	<?php if ($this->user->authorise('core.edit', 'com_dpcalendar')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-edit"
				data-href="<?php echo $this->router->getLocationFormRoute($this->location->id, JUri::getInstance()); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EDIT]); ?>
			<?php echo $this->translate('JACTION_EDIT'); ?>
		</button>
	<?php } ?>
	<?php if ($this->params->get('map_provider', 'openstreetmap') != 'none') { ?>
		<button type="button" class="dp-button dp-button-action dp-button-map-site" data-target="new"
				data-href="<?php echo \DPCalendar\Helper\Location::getMapLink($this->location); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::MAP]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_MAP_SITE_LINK'); ?>
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EXTERNAL]); ?>
		</button>
		<button type="button" class="dp-button dp-button-action dp-button-map-directions" data-target="new"
				data-href="<?php echo \DPCalendar\Helper\Location::getDirectionsLink($this->location); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DIRECTIONS]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_MAP_DIRECTIONS_LINK'); ?>
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EXTERNAL]); ?>
		</button>
	<?php } ?>
</div>
