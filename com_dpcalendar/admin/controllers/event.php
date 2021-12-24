<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

class DPCalendarControllerEvent extends \Joomla\CMS\MVC\Controller\FormController
{
	protected $urlVar = 'e_id';

	protected function allowAdd($data = [])
	{
		// Initialize variables
		$categoryId = ArrayHelper::getValue($data, 'catid', $this->input->getInt('filter_category_id'), 'int');

		if (!$categoryId) {
			// In the absence of better information, revert to the component permissions
			return parent::allowAdd($data);
		}

		return Factory::getUser()->authorise('core.create', $this->option . '.category.' . $categoryId);
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$event = null;

		$recordId = (int)isset($data[$key]) ? $data[$key] : 0;
		if ($recordId) {
			$event = $this->getModel()->getItem($recordId);
		}

		if ($event != null && $event->id) {
			$calendar = DPCalendarHelper::getCalendar($event->catid);

			return $calendar->canEdit || ($calendar->canEditOwn && $event->created_by == Factory::getUser()->id);
		}

		// Since there is no asset tracking, revert to the component permissions
		return parent::allowEdit($data, $key);
	}

	public function save($key = null, $urlVar = 'e_id')
	{
		$this->transformDatesToSql();

		$data = $this->input->post->get('jform', [], 'array');

		if (empty($data['start_date_time']) && empty($data['end_date_time'])) {
			$data['all_day'] = '1';
		}

		if (!key_exists('all_day', $data)) {
			$data['all_day'] = 0;
		}

		if (!key_exists('color', $data)) {
			$data['color'] = '';
		}

		if (!key_exists('payment_provider', $data)) {
			$data['payment_provider'] = '';
		}

		if (!empty($data['location_ids'])) {
			foreach ($data['location_ids'] as $index => $locationId) {
				if (is_numeric($locationId) || !$locationId) {
					continue;
				}

				list($title, $coordinates) = explode(' [', $locationId);

				$location = \DPCalendar\Helper\Location::get(trim($coordinates, ']'), true, $title);
				if (!$location->id) {
					unset($data['location_ids'][$index]);
					continue;
				}
				$data['location_ids'][$index] = $location->id;
			}
		}

		if (DPCalendarHelper::isFree()) {
			foreach (DPCalendarHelper::$DISABLED_FREE_FIELDS as $field) {
				if (array_key_exists($field, $data)) {
					unset($data[$field]);
				}
			}
		}

		if ($this->getTask() == 'save2copy') {
			$data['capacity_used'] = null;
		}

		$this->input->post->set('jform', $data);

		$result = false;
		if (!is_numeric($data['catid'])) {
			PluginHelper::importPlugin('dpcalendar');
			$data['id'] = $this->input->getInt($urlVar, null);

			$model     = $this->getModel();
			$form      = $model->getForm($data, false);
			$validData = $model->validate($form, $data);

			if ($validData['all_day'] == 1) {
				$validData['start_date'] = DPCalendarHelper::getDate($validData['start_date'])->toSql(true);
				$validData['end_date']   = DPCalendarHelper::getDate($validData['end_date'])->toSql(true);
			}

			$tmp = Factory::getApplication()->triggerEvent('onEventSave', [$validData]);
			foreach ($tmp as $newEventId) {
				if ($newEventId === false) {
					continue;
				}
				$result = true;
				switch ($this->getTask()) {
					case 'apply':
						$this->setRedirect(
							Route::_(
								'index.php?option=' . $this->option . '&view=' .
								$this->view_item . $this->getRedirectToItemAppend($newEventId, $urlVar),
								false
							)
						);
						break;
					case 'save2new':
						$this->setRedirect(
							Route::_(
								'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend(null, $urlVar),
								false
							)
						);
						break;
					default:
						$this->setRedirect(
							Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false)
						);
						break;
				}
			}
		} else {
			$result = parent::save($key, $urlVar);
		}

		return $result;
	}

	public function edit($key = null, $urlVar = 'e_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'e_id')
	{
		return parent::cancel($key);
	}

	public function batch($model = null)
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_dpcalendar&view=events' . $this->getRedirectToListAppend(), false));

		return parent::batch($this->getModel());
	}

	public function reload($key = null, $urlVar = 'e_id')
	{
		$this->transformDatesToSql();

		return parent::reload($key, $urlVar);
	}

	public function getModel($name = 'AdminEvent', $prefix = 'DPCalendarModel', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function similar()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$formData = $this->input->get('jform', [], 'array');

		if (empty($formData['title'])) {
			DPCalendarHelper::sendMessage(null);
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = $this->getModel('Events');
		$model->getState();
		$model->setState('list.limit', 5);
		$model->setState('list.direction', 'desc');
		$model->setState('category.id', $formData['catid']);
		$model->setState('filter.ongoing', false);
		$model->setState('filter.expand', true);
		$model->setState('filter.language', $formData['language']);
		$model->setState('list.start-date', 0);
		$model->setState('filter.search.columns', ['a.title']);
		$model->setState('filter.search', '+' . str_replace(' ', " +", strtolower($formData['title'])));

		if (!isset($formData['id']) || !$formData['id']) {
			$formData['id'] = $this->input->get('id', 0);
		}

		$data = [];
		foreach ($model->getItems() as $e) {
			if ($formData['id'] && ($e->id == $formData['id'] || $e->original_id == $formData['id'])) {
				continue;
			}

			$item          = new stdClass();
			$item->value   = $e->id;
			$item->title   = $e->title;
			$desc          = strip_tags(HTMLHelper::_('string.truncate', $e->description, 100));
			$item->details = '[' . DPCalendarHelper::getDateStringFromEvent($e) . '] ' . $desc;
			$data[]        = $item;
		}

		DPCalendarHelper::sendMessage(null, false, $data);
	}

	public function overlapping()
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
			if ($e->id != $data['id'] && $e->original_id != $data['id']) {
				continue;
			}
			unset($events[$key]);
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
			['message' => $message, 'count' => count($events)]
		);
	}

	public function reloadfromevent($key = null, $urlVar = 'e_id')
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$data = $this->getModel('Event')->getItem($this->input->getInt('template_event_id'));

		if (!$data) {
			return parent::reload($key, $urlVar);
		}

		$formData = $this->input->post->get('jform', [], 'array');

		$data->id = !empty($formData['id']) ? $formData['id'] : 0;

		// Reset the color when equal to calendar
		if ($data->color == \DPCalendar\Helper\DPCalendarHelper::getCalendar($data->catid)->color) {
			$data->color = '';
		}

		$this->input->set('jform', (array)$data);
		$this->input->post->set('jform', (array)$data);

		return parent::reload($key, $urlVar);
	}

	public function newlocation()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$location = \DPCalendar\Helper\Location::get($this->input->getString('lookup'), false, $this->input->getString('lookup_title'));

		$data = [];
		if ($location->title) {
			$data = [
					'id'      => $location->id,
					'display' => $location->title . ' [' . $location->latitude . ',' . $location->longitude . ']'
				];
		}
		DPCalendarHelper::sendMessage(null, empty($data), $data);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'e_id')
	{
		return parent::getRedirectToItemAppend($recordId, $urlVar);
	}

	private function transformDatesToSql()
	{
		$data = $this->input->post->get('jform', [], 'array');

		if (empty($data['start_date_time']) && empty($data['end_date_time'])) {
			$data['all_day'] = '1';
		}

		$data['start_date'] = DPCalendarHelper::getDateFromString(
			$data['start_date'],
			$data['start_date_time'],
			$data['all_day'] == '1'
		)->toSql(false);
		$data['end_date'] = DPCalendarHelper::getDateFromString(
			$data['end_date'],
			$data['end_date_time'],
			$data['all_day'] == '1'
		)->toSql(false);

		if (!empty($data['scheduling_end_date'])) {
			$data['scheduling_end_date'] = DPCalendarHelper::getDateFromString($data['scheduling_end_date'], null, true)->toSql(false);
		}

		$this->input->set('jform', $data);
		$this->input->post->set('jform', $data);
	}
}
