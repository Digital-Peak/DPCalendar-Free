<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

?>
<div class="com-dpcalendar-adminlist__filters">
	<div id="filter-bar" class="com-dpcalendar-adminlist__filterbar js-stools-container-bar">
		<label class="element-invisible" for="filter_search_start">
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_START_DATE_AFTER_LABEL'); ?>:
		</label>
		<?php $this->displayData['title'] = $this->translate('COM_DPCALENDAR_FIELD_START_DATE_LABEL'); ?>
		<?php $this->displayData['name'] = 'filter[search_start]'; ?>
		<?php $this->displayData['date'] = $this->startDate; ?>
		<?php echo $this->layoutHelper->renderLayout('block.datepicker', $this->displayData); ?>
		<label class="element-invisible" for="filter_search_end">
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENTS_END_DATE_BEFORE_LABEL'); ?>:
		</label>
		<?php $this->displayData['title'] = $this->translate('COM_DPCALENDAR_FIELD_END_DATE_LABEL'); ?>
		<?php $this->displayData['name'] = 'filter[search_end]'; ?>
		<?php $this->displayData['date'] = $this->endDate; ?>
		<?php echo $this->layoutHelper->renderLayout('block.datepicker', $this->displayData); ?>
	</div>
	<?php echo $this->layoutHelper->renderLayout('joomla.searchtools.default', ['view' => $this]); ?>
</div>
