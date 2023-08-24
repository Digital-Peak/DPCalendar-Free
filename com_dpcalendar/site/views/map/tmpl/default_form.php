<?php
use DPCalendar\HTML\Block\Icon;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<form action="index.php?option=com_dpcalendar&view=map&layout=events&format=raw" method="post"
	  class="form-validate dp-print-hide com-dpcalendar-map__form dp-form">
	<div class="com-dpcalendar-map__form-container">
		<div class="com-dpcalendar-map__text-search">
			<input type="text" name="search" value="<?php echo $this->state->get('filter.search'); ?>"
				   class="dp-input dp-input-text"
				   placeholder="<?php echo $this->translate('JGLOBAL_FILTER_LABEL'); ?>">
		</div>
		<div class="com-dpcalendar-map__date-search">
			<?php $this->displayData['title'] = $this->translate('COM_DPCALENDAR_FIELD_START_DATE_LABEL'); ?>
			<?php $this->displayData['name'] = 'start-date'; ?>
			<?php $this->displayData['date'] = $this->startDate; ?>
			<?php echo $this->layoutHelper->renderLayout('block.datepicker', $this->displayData); ?>
			<?php $this->displayData['title'] = $this->translate('COM_DPCALENDAR_FIELD_END_DATE_LABEL'); ?>
			<?php $this->displayData['name'] = 'end-date'; ?>
			<?php $this->displayData['date'] = $this->endDate; ?>
			<?php echo $this->layoutHelper->renderLayout('block.datepicker', $this->displayData); ?>
		</div>
	</div>
	<div class="com-dpcalendar-map__form-container">
		<div class="com-dpcalendar-map__location-search">
			<input type="text" name="location" value="<?php echo $this->state->get('filter.location'); ?>"
				class="dp-input dp-input-text dp-input_location" autocomplete="off"
				data-dp-autocomplete="<?php echo $this->params->get('map_autocomplete', 1); ?>"
				placeholder="<?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?>">
		</div>
		<div class="com-dpcalendar-map__radius-search">
			<?php $radius = $this->state->get('filter.radius', 20); ?>
			<select name="radius" class="dp-input dp-input-select" data-default="<?php echo $this->params->get('map_view_radius', 20); ?>"
				aria-label="<?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_MAP_RADIUS_LABEL'); ?>">
				<option value="5"<?php echo $radius == 5 ? ' selected' : ''; ?>>5</option>
				<option value="10"<?php echo $radius == 10 ? ' selected' : ''; ?>>10</option>
				<option value="20"<?php echo $radius == 20 ? ' selected' : ''; ?>>20</option>
				<option value="50"<?php echo $radius == 50 ? ' selected' : ''; ?>>50</option>
				<option value="100"<?php echo $radius == 100 ? ' selected' : ''; ?>>100</option>
				<option value="500"<?php echo $radius == 500 ? ' selected' : ''; ?>>500</option>
				<option value="1000"<?php echo $radius == 1000 ? ' selected' : ''; ?>>1000</option>
				<option value="-1"<?php echo $radius == '-1' ? ' selected' : ''; ?>><?php echo $this->translate('JALL'); ?></option>
			</select>
			<?php $length = $this->state->get('filter.length-type', 'm'); ?>
			<select name="length-type" class="dp-input dp-input-select" data-default="<?php echo $this->params->get('map_view_length_type', 'm'); ?>"
				aria-label="<?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_MAP_LENGTH_TYPE_LABEL'); ?>">
				<option value="m"<?php echo $length == 'm' ? ' selected' : ''; ?>>
					<?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_MAP_LENGTH_TYPE_METER'); ?>
				</option>
				<option value="mile"<?php echo $length == 'mile' ? ' selected' : ''; ?>>
					<?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_MAP_LENGTH_TYPE_MILE'); ?>
				</option>
			</select>
		</div>
	</div>
	<div class="com-dpcalendar-map__button-bar dp-button-bar">
		<button class="dp-button dp-button-current-location" type="button">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::LOCATION]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_MAP_LABEL_CURRENT_LOCATION'); ?>
		</button>
		<button class="dp-button dp-button-search" type="button">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::OK]); ?>
			<?php echo $this->translate('JSEARCH_FILTER'); ?>
		</button>
		<button class="dp-button dp-button-clear" type="button">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => Icon::CANCEL]); ?>
			<?php echo $this->translate('JCLEAR'); ?>
		</button>
	</div>
	<input type="hidden" name="Itemid" value="<?php echo $this->input->getInt('Itemid'); ?>" class="dp-input dp-input-hidden">
</form>
