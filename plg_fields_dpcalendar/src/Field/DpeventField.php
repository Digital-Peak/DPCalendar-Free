<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Fields\DPCalendar\Field;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DateHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

class DpeventField extends ListField
{
	protected $type = 'Dpevent';

	protected function getOptions()
	{
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Site');
		$model->getState();
		$model->setState('filter.parentIds', explode(',', $this->element->attributes()->calendar_ids ?: ''));

		$ids = [];
		foreach ($model->getItems() as $calendar) {
			$ids[] = $calendar->getId();
		}

		$dateHelper = new DateHelper();

		$startDate = trim($this->element->attributes()->start_date ?: '');
		if ($startDate === 'start of day') {
			$startDate = $dateHelper->getDate(null, true, 'UTC');
			$startDate->setTime(0, 0, 0);
		} else {
			$startDate = $dateHelper->getDate($startDate);
		}

		// Round to the last quarter
		$startDate->sub(new \DateInterval("PT" . $startDate->format("s") . "S"));
		$startDate->sub(new \DateInterval("PT" . ($startDate->format("i") % 15) . "M"));

		$endDate = trim($this->element->attributes()->end_date ?: '');
		if ($endDate === 'same day') {
			$endDate = clone $startDate;
			$endDate->setTime(23, 59, 59);
		} elseif ($endDate !== '' && $endDate !== '0') {
			$tmp = $dateHelper->getDate($endDate);
			$tmp->sub(new \DateInterval("PT" . $tmp->format("s") . "S"));
			$tmp->sub(new \DateInterval("PT" . ($tmp->format("i") % 15) . "M"));
			$endDate = $tmp;
		} else {
			$endDate = null;
		}

		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Administrator', ['ignore_request' => true]);
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
			$options[] = HTMLHelper::_(
				'select.option',
				$event->id,
				$event->title . ' [' . strip_tags(DPCalendarHelper::getDateStringFromEvent($event)) . ']'
			);
		}

		return $options;
	}
}
