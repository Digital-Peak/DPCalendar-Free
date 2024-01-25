<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2023 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;

class DPCalendarControllerEvent extends FormController
{
	public $input;
	public function overlapping(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = $this->input->get('jform', [], 'array');

		if (empty($data['start_date_time']) && empty($data['end_date_time'])) {
			$data['all_day'] = '1';
		}

		$startDate = DPCalendarHelper::getDateFromString(
			$data['start_date'],
			$data['start_date_time'],
			$data['all_day'] == '1'
		);
		$endDate = DPCalendarHelper::getDateFromString(
			$data['end_date'],
			$data['end_date_time'],
			$data['all_day'] == '1'
		);

		// End date is exclusive
		$endDate->modify('-1 second');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = $this->getModel('Events');
		$model->getState();
		$model->setState('list.limit', 4);
		$model->setState('category.id', $data['catid']);
		$model->setState('filter.ongoing', false);
		$model->setState('filter.expand', true);
		$model->setState('filter.language', $data['language']);
		$model->setState('list.start-date', $startDate);
		$model->setState('list.end-date', $endDate);
		$model->setState('list.local-date', true);

		if (DPCalendarHelper::getComponentParameter('event_form_check_overlaping_locations')) {
			if (!empty($data['location_ids'])) {
				$model->setState('filter.locations', $data['location_ids']);
			}
			if (!empty($data['rooms'])) {
				$model->setState('filter.rooms', $data['rooms']);
			}
		}

		// Get the events in that period
		$events = $model->getItems();

		if (!isset($data['id']) || !$data['id']) {
			$data['id'] = $this->input->get('id', 0);
		}

		foreach ($events as $key => $e) {
			// Unset the own id
			if ($e->id == $data['id'] || ($e->original_id != 0 && $e->original_id == $data['id'])) {
				unset($events[$key]);
			}

			// Remove events where the end date is like the start date
			if ($e->end_date === $startDate->toSql()) {
				unset($events[$key]);
			}
		}

		Factory::getLanguage()->load('com_dpcalendar', JPATH_SITE . '/components/com_dpcalendar');

		// Reset the end date
		$endDate->modify('+1 second');

		$event                = new stdClass();
		$event->start_date    = $startDate->toSql();
		$event->end_date      = $endDate->toSql();
		$event->all_day       = $data['all_day'];
		$event->show_end_time = true;
		$date                 = strip_tags(DPCalendarHelper::getDateStringFromEvent($event));
		$message              = DPCalendarHelper::renderEvents(
			$events,
			Text::_('COM_DPCALENDAR_VIEW_FORM_OVERLAPPING_EVENTS_' . ($events ? '' : 'NOT_') . 'FOUND'),
			null,
			[
				'checkDate'    => $date,
				'calendarName' => DPCalendarHelper::getCalendar($data['catid'])->title
			]
		);

		DPCalendarHelper::sendMessage(
			null,
			false,
			['message' => $message, 'count' => is_countable($events) ? count($events) : 0]
		);
	}
}
