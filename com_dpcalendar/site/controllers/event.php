<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\LayoutHelper;
use DPCalendar\Router\Router;
use DPCalendar\Translator\Translator;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

class DPCalendarControllerEvent extends FormController
{
	protected $view_item = 'form';
	protected $view_list = 'calendar';
	protected $option    = 'com_dpcalendar';

	public function add()
	{
		if (!parent::add()) {
			// Redirect to the return page.
			$this->setRedirect($this->getReturnPage());
		}
	}

	protected function allowAdd($data = [])
	{
		$calendar = DPCalendarHelper::getCalendar(ArrayHelper::getValue(
			$data,
			'catid',
			$this->input->getVar('id'),
			'string'
		));
		$allow = null;
		if ($calendar) {
			$allow = $calendar->canCreate;
		}

		if ($allow === null) {
			return parent::allowAdd($data);
		}
		return $allow;
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$recordId = isset($data[$key]) ? $data[$key] : 0;
		$event    = null;

		if ($recordId) {
			$event = $this->getModel()->getItem($recordId);
		}

		if ($event != null) {
			$calendar = DPCalendarHelper::getCalendar($event->catid);

			return $calendar->canEdit || ($calendar->canEditOwn && $event->created_by == Factory::getUser()->id);
		}
		return parent::allowEdit($data, $key);
	}

	protected function allowDelete($data = [], $key = 'id')
	{
		$calendar = null;
		$event    = null;
		if (isset($data['catid'])) {
			$calendar = DPCalendarHelper::getCalendar($data['catid']);
		}
		if ($calendar == null) {
			$recordId = (int)isset($data[$key]) ? $data[$key] : 0;
			$event    = $this->getModel()->getItem($recordId);
			$calendar = DPCalendarHelper::getCalendar($event->catid);
		}

		if ($calendar != null && $event != null) {
			return $calendar->canDelete || ($calendar->canEditOwn && $event->created_by == Factory::getUser()->id);
		}

		return Factory::getUser()->authorise('core.delete', $this->option);
	}

	public function cancel($key = 'e_id')
	{
		$return   = true;
		$recordId = $this->input->getVar($key);
		if (!$recordId || is_numeric($recordId)) {
			$return = parent::cancel($key);
		}
		$this->setRedirect($this->getReturnPage());

		return $return;
	}

	public function delete($key = 'e_id')
	{
		$recordId = $this->input->getVar($key);

		if (!$this->allowDelete([$key => $recordId], $key)) {
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false)
			);

			return false;
		}

		$event = $this->getModel()->getItem($recordId);

		if (!is_numeric($event->catid)) {
			Factory::getApplication()->triggerEvent('onEventDelete', [
				is_numeric($event->id) ? $event->xreference : $event->id
			]);
		}
		if (is_numeric($event->id)) {
			$this->getModel()->getTable('Event', 'DPCalendarTable')->publish([$recordId], -2);
			if (!$this->getModel()->delete($recordId)) {
				$this->setError(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
				$this->setMessage($this->getModel()->getError(), 'error');

				$this->setRedirect(
					Route::_(
						'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
						false
					)
				);

				return false;
			}
		}
		// Redirect to the return page
		$redirect = $this->getReturnPage();

		// J4 router redirects to the delete task again
		if ($return = $this->input->getVar('return', null, 'default', 'base64')) {
			$redirect = base64_decode($return);
		}
		$this->setRedirect($redirect, Text::_('COM_DPCALENDAR_DELETE_SUCCESS'));

		return true;
	}

	public function edit($key = 'id', $urlVar = 'e_id')
	{
		$context  = "$this->option.edit.$this->context";
		$cid      = $this->input->getVar('cid', [], 'post', 'array');
		$recordId = (count($cid) ? $cid[0] : $this->input->getVar($urlVar));

		if (!$this->allowEdit([$key => $recordId], $key)) {
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
					false
				)
			);

			return false;
		}
		if ($this->getModel()->getItem($recordId) != null && !is_numeric($recordId)) {
			$app    = Factory::getApplication();
			$values = (array)$app->getUserState($context . '.id');

			array_push($values, $recordId);
			$values = array_unique($values);
			$app->setUserState($context . '.id', $values);
			$app->setUserState($context . '.data', null);

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend(
						$recordId,
						$urlVar
					),
					false
				)
			);

			return true;
		}

		return parent::edit($key, $urlVar);
	}

	public function getModel($name = 'form', $prefix = '', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = null)
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$itemId = $this->input->getInt('Itemid');
		$return = $this->getReturnPage();

		$hash = $this->input->getString('urlhash');
		if ($hash) {
			$hash = '#' . trim($hash, '#');
		}

		if ($itemId) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($return) {
			$append .= '&return=' . base64_encode($return);
		}

		return $append . $hash;
	}

	protected function getReturnPage()
	{
		$return = $this->input->getVar('return', null, 'default', 'base64');
		$hash   = $this->input->getString('urlhash');
		if ($hash) {
			$hash = '#' . trim($hash, '#');
		}

		if (empty($return) || !Uri::isInternal(base64_decode($return))) {
			return Uri::base();
		}

		return Route::_(base64_decode($return), false) . $hash;
	}

	public function move()
	{
		$data       = [];
		$data['id'] = $this->input->getVar('id');
		$success    = false;
		$model      = $this->getModel('form', 'DPCalendarModel', ['ignore_request' => false]);
		// Load state, so the event id won't be overwritten on load state
		$model->getState();

		if (!$this->allowSave($data)) {
			$model->setError(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
		} else {
			$event = $model->getItem($data['id']);
			$data  = ArrayHelper::fromObject($event);

			if (!empty($data['tags']) && array_key_exists('tags', $data['tags'])) {
				$data['tags'] = explode(',', $data['tags']['tags']);
			}

			$start = DPCalendarHelper::getDate($event->start_date, $event->all_day);
			$end   = DPCalendarHelper::getDate($event->end_date, $event->all_day);

			$minutes = $this->input->getInt('minutes') . ' minute';
			if (strpos($minutes, '-') === false) {
				$minutes = '+' . $minutes;
			}
			if ($this->input->get('onlyEnd', 'false') == 'false') {
				$start->modify($minutes);
			}
			$end->modify($minutes);

			$data['start_date']         = $start->toSql();
			$data['end_date']           = $end->toSql();
			$data['date_range_correct'] = true;
			$data['all_day']            = $this->input->get('allDay') == 'true' ? '1' : '0';

			if (!is_numeric($data['catid'])) {
				$id = $data['id'];
				// If the id is numeric, then we are editing an event in advanced cache mode
				if (is_numeric($data['id'])) {
					$data['id'] = $data['xreference'];
				}
				$tmp = Factory::getApplication()->triggerEvent('onEventSave', [$data]);
				foreach ($tmp as $newEventId) {
					if ($newEventId === false) {
						continue;
					}

					if (is_numeric($id)) {
						$success = $model->save($data);
					} else {
						$data['id'] = $newEventId;
						$success    = true;
					}
				}
			} else {
				$success = $model->save($data);
			}
		}

		if ($success) {
			$event = $model->getItem($data['id']);

			if ($event->start_date == $data['start_date'] && $event->end_date == $data['end_date']) {
				$displayData = [
					'router'       => new Router(),
					'layoutHelper' => new LayoutHelper(),
					'translator'   => new Translator(),
					'input'        => $this->input,
					'params'       => Factory::getApplication()->getMenu()->getActive()->getParams()
				];
				$displayData['event'] = $event;
				$description          = trim($displayData['layoutHelper']->renderLayout('event.tooltip', $displayData));
				$description          = \DPCalendar\Helper\DPCalendarHelper::fixImageLinks($description);

				DPCalendarHelper::sendMessage(
					Text::_('JLIB_APPLICATION_SAVE_SUCCESS'),
					false,
					['url' => DPCalendarHelperRoute::getEventRoute($data['id'], $data['catid']), 'description' => $description]
				);

				return;
			}


			DPCalendarHelper::sendMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), true);

			return;
		}

		DPCalendarHelper::sendMessage($model->getError(), true);
	}

	public function save($key = null, $urlVar = 'e_id')
	{
		if ($this->input->getInt($urlVar)) {
			$this->context = 'form';
		}

		/** @var SiteApplication $app */
		$app = Factory::getApplication();

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

		if (!key_exists('capacity', $data)) {
			$data['capacity'] = $app->getParams()->get('event_form_capacity', '0');
		}

		foreach (['schedule', 'price', 'earlybird', 'user_discount', 'booking_options'] as $field) {
			if (key_exists($field, $data)) {
				continue;
			}
			$data[$field] = [];
		}

		$dateFormat = $app->getParams()->get('event_form_date_format', 'd.m.Y');
		$timeFormat = $app->getParams()->get('event_form_time_format', 'H:i');

		if ($data['start_date_time'] == '') {
			$data['start_date_time'] = DPCalendarHelper::getDate()->format($timeFormat);
		}
		if ($data['end_date_time'] == '') {
			$data['end_date_time'] = DPCalendarHelper::getDate()->format($timeFormat);
		}

		// Get the start date from the date
		$start = DPCalendarHelper::getDateFromString(
			$data['start_date'],
			$data['start_date_time'],
			$data['all_day'] == '1',
			$dateFormat,
			$timeFormat
		);

		// Format the start date to SQL format
		$data['start_date'] = $start->toSql(false);

		// Get the start date from the date
		$end = DPCalendarHelper::getDateFromString(
			$data['end_date'],
			$data['end_date_time'],
			$data['all_day'] == '1',
			$dateFormat,
			$timeFormat
		);
		if ($end->format('U') < $start->format('U')) {
			$end = clone $start;
			$end->modify('+30 min');
		}
		// Format the end date to SQL format
		$data['end_date'] = $end->toSql(false);

		if (!empty($data['location_ids'])) {
			$data['location_ids'] = (array)$data['location_ids'];
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

		if ($this->getTask() == 'save2copy') {
			$data['capacity_used'] = null;
		}

		if (DPCalendarHelper::isFree()) {
			foreach (DPCalendarHelper::$DISABLED_FREE_FIELDS as $field) {
				if (array_key_exists($field, $data)) {
					unset($data[$field]);
				}
			}
		}

		$this->input->post->set('jform', $data);

		$result   = false;
		$calendar = DPCalendarHelper::getCalendar($data['catid']);
		if ($calendar->external) {
			PluginHelper::importPlugin('dpcalendar');
			$data['id'] = $this->input->getVar($urlVar, null);

			$app->setUserState('com_dpcalendar.edit.event.data', $data);

			$model     = $this->getModel();
			$form      = $model->getForm($data, true);
			$validData = $model->validate($form, $data);

			if (isset($validData['all_day']) && $validData['all_day'] == 1) {
				$validData['start_date'] = DPCalendarHelper::getDate($validData['start_date'])->toSql(true);
				$validData['end_date']   = DPCalendarHelper::getDate($validData['end_date'])->toSql(true);
			}

			// If the calendar is native, then we are editing an event in
			// advanced cache mode
			if ($calendar->native && !empty($data['xreference'])) {
				$validData['id'] = $data['xreference'];
			}

			try {
				$tmp = Factory::getApplication()->triggerEvent('onEventSave', [$validData]);
			} catch (InvalidArgumentException $e) {
				$this->setMessage($e->getMessage(), 'error');

				$this->setRedirect(DPCalendarHelperRoute::getFormRoute($app->getUserState('dpcalendar.event.id'), $this->getReturnPage()));

				return false;
			}

			foreach ($tmp as $newEventId) {
				if ($newEventId === false) {
					continue;
				}

				$app->setUserState('dpcalendar.event.id', $newEventId);

				// If the id is numeric wee need to save it in the database too
				if ($calendar->native) {
					$validData['xreference'] = $newEventId;
					$this->input->post->set('jform', $validData);
					$result = parent::save($key, $urlVar);
				} else {
					$result = true;
					$return = $this->input->getBase64('return');
					if (!empty($urlVar) && !empty($return) && !empty($data['id'])) {
						$uri = base64_decode($return);
						$uri = str_replace($data['id'], $newEventId, $uri);
						$this->input->set('return', base64_encode($uri));
					}
				}
			}
		} else {
			$result = parent::save($key, $urlVar);
		}
		// If ok, redirect to the return page.
		if ($result) {
			$canChangeState = $calendar->external
				|| Factory::getUser()->authorise('core.edit.state', 'com_dpcalendar.category.' . $data['catid']);
			if ($this->getTask() == 'save') {
				$app->setUserState('com_dpcalendar.edit.event.data', null);
				$return = $this->getReturnPage();
				if ($return == Uri::base() && $canChangeState) {
					$return = DPCalendarHelperRoute::getEventRoute(
						$app->getUserState('dpcalendar.event.id'),
						$data['catid']
					);
				}
				$this->setRedirect($return);
			}
			if ($this->getTask() == 'apply' || $this->getTask() == 'save2copy') {
				$return = $this->getReturnPage();
				if ($canChangeState) {
					$return = DPCalendarHelperRoute::getFormRoute(
						$app->getUserState('dpcalendar.event.id'),
						$this->getReturnPage()
					);
				}
				$this->setRedirect($return);
			}
			if ($this->getTask() == 'save2new') {
				$app->setUserState('com_dpcalendar.edit.event.data', null);
				$return = DPCalendarHelperRoute::getFormRoute(0, $this->getReturnPage());
				$this->setRedirect($return);
			}
		} elseif (!$this->redirect) {
			$this->setRedirect(
				DPCalendarHelperRoute::getEventRoute($app->getUserState('dpcalendar.event.id'), $data['catid'])
			);
		}

		return $result;
	}

	public function invite()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = $this->input->post->get('jform', [], 'array');
		$this->getModel()->invite(
			$data['event_id'],
			isset($data['users']) ? $data['users'] : [],
			isset($data['groups']) ? $data['groups'] : []
		);

		$this->setRedirect(
			base64_decode($this->input->getBase64('return')),
			Text::_('COM_DPCALENDAR_SENT_INVITATION')
		);
	}

	public function overlapping()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = $this->input->get('jform', [], 'array');

		if (!key_exists('all_day', $data)) {
			$data['all_day'] = false;
		}

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

		if ($data['all_day']) {
			$startDate->setTime(0, 0, 0);
			$endDate->setTime(23, 59, 59);
		}

		// End date is exclusive
		$endDate->modify('-1 second');

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
			$data['id'] = $this->input->get('id');
		}
		foreach ($events as $key => $e) {
			if ($e->id != $data['id'] && ($e->original_id == 0 || $e->original_id != $data['id'])) {
				continue;
			}
			unset($events[$key]);
		}

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

	public function similar()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$formData = $this->input->get('jform', [], 'array');

		if (empty($formData['title'])) {
			DPCalendarHelper::sendMessage(null);
		}

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
			$item->details = '[' . DPCalendarHelper::getDateStringFromEvent($e) . '] ' .
				strip_tags(HTMLHelper::_('string.truncate', $e->description, 100));

			$data[] = $item;
		}

		DPCalendarHelper::sendMessage(null, false, $data);
	}

	public function checkin()
	{
		// Check for request forgeries.
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$model = $this->getModel();
		$event = $model->getItem($this->input->getInt('e_id'));

		$message = Text::sprintf('COM_DPCALENDAR_N_ITEMS_CHECKED_IN_1', 1);
		$type    = null;

		if ($model->checkin([$event->id]) === false) {
			// Checkin failed
			$message = Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError());
			$type    = 'error';
		}

		$this->setRedirect(DPCalendarHelperRoute::getEventRoute($event->id, $event->catid), $message, $type);

		return $type == null;
	}

	public function reloadfromevent($key = null, $urlVar = 'e_id')
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

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

	public function reload($key = null, $urlVar = 'e_id')
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
}
