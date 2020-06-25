<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JFormHelper::loadFieldClass('list');

class JFormFieldDpevent extends JFormFieldList
{
	protected $type = 'Dpevent';

	public function getOptions()
	{
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$model = JModelLegacy::getInstance('Calendar', 'DPCalendarModel');
		$model->getState();
		$model->setState('filter.parentIds', explode(',', $this->element->attributes()->calendar_ids));
		$ids = [];
		foreach ($model->getItems() as $calendar) {
			$ids[] = $calendar->id;
		}

		$dateHelper = new \DPCalendar\Helper\DateHelper();

		$startDate = trim($this->element->attributes()->start_date);
		if ($startDate == 'start of day') {
			$startDate = $dateHelper->getDate(null, true, 'UTC');
			$startDate->setTime(0, 0, 0);
		} else {
			$startDate = $dateHelper->getDate($startDate);
		}

		// Round to the last quater
		$startDate->sub(new DateInterval("PT" . $startDate->format("s") . "S"));
		$startDate->sub(new DateInterval("PT" . ($startDate->format("i") % 15) . "M"));

		$endDate = trim($this->element->attributes()->end_date);
		if ($endDate == 'same day') {
			$endDate = clone $startDate;
			$endDate->setTime(23, 59, 59);
		} else if ($endDate) {
			$tmp = $dateHelper->getDate($endDate);
			$tmp->sub(new DateInterval("PT" . $tmp->format("s") . "S"));
			$tmp->sub(new DateInterval("PT" . ($tmp->format("i") % 15) . "M"));
			$endDate = $tmp;
		} else {
			$endDate = null;
		}

		$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.limit', 100);
		$model->setState('category.id', $ids);
		$model->setState('category.recursive', true);
		$model->setState('filter.expand', (int)$this->element->attributes()->expand);
		$model->setState('filter.state', [1, 3]);
		$model->setState('filter.publish_date', true);
		$model->setState('list.start-date', $startDate);
		$model->setState('list.end-date', $endDate);

		$options = parent::getOptions();
		foreach ($model->getItems() as $event) {
			$options[] = JHtml::_(
				'select.option',
				$event->id,
				$event->title . ' [' . strip_tags(DPCalendarHelper::getDateStringFromEvent($event)) . ']'
			);
		}

		return $options;
	}
}
