<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Location;
use DPCalendar\HTML\Block\Icon;
use Joomla\CMS\Uri\Uri;
?>
<div class="com-dpcalendar-location__actions dp-button-bar dp-print-hide">
	<?php if ($this->location->params->get('access-edit')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-edit"
			data-href="<?php echo $this->router->getLocationFormRoute($this->location->id, Uri::getInstance()); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EDIT]); ?>
			<?php echo $this->translate('JACTION_EDIT'); ?>
		</button>
	<?php } ?>
	<?php if ($this->params->get('map_provider', 'openstreetmap') != 'none') { ?>
		<button type="button" class="dp-button dp-button-action dp-button-map-site" data-target="new"
			data-href="<?php echo Location::getMapLink($this->location, $this->params->get('location_map_zoom', 10)); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::MAP]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_MAP_SITE_LINK'); ?>
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EXTERNAL]); ?>
		</button>
		<button type="button" class="dp-button dp-button-action dp-button-map-directions" data-target="new"
			data-href="<?php echo Location::getDirectionsLink($this->location, $this->params->get('location_map_zoom', 10)); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::DIRECTIONS]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_LOCATION_MAP_DIRECTIONS_LINK'); ?>
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::EXTERNAL]); ?>
		</button>
	<?php } ?>
</div>
