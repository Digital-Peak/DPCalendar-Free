<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

JLoader::import('joomla.application.component.controllerform');

class DPCalendarControllerEvent extends JControllerForm
{

	protected $view_item = 'form';
	protected $view_list = 'calendar';
	protected $option = 'com_dpcalendar';

	public function add()
	{
		if (!parent::add()) {
			// Redirect to the return page.
			$this->setRedirect($this->getReturnPage());
		}
	}

	protected function allowAdd($data = array())
	{
		$calendar = DPCalendarHelper::getCalendar(ArrayHelper::getValue($data, 'catid', $this->input->getVar('id'),
			'string'));
		$allow    = null;
		if ($calendar) {
			$allow = $calendar->canCreate;
		}

		if ($allow === null) {
			return parent::allowAdd($data);
		} else {
			return $allow;
		}
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
		$recordId = isset($data[$key]) ? $data[$key] : 0;
		$event    = null;

		if ($recordId) {
			$event = $this->getModel()->getItem($recordId);
		}

		if ($event != null) {
			$calendar = DPCalendarHelper::getCalendar($event->catid);

			return $calendar->canEdit || ($calendar->canEditOwn && $event->created_by == JFactory::getUser()->id);
		} else {
			return parent::allowEdit($data, $key);
		}
	}

	protected function allowDelete($data = array(), $key = 'id')
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
			return $calendar->canDelete || ($calendar->canEditOwn && $event->created_by == JFactory::getUser()->id);
		} else {
			return JFactory::getUser()->authorise('core.delete', $this->option);
		}
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

		if (!$this->allowDelete(array($key => $recordId), $key)) {
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
					false)
			);

			return false;
		}

		$event = $this->getModel()->getItem($recordId);

		if (!is_numeric($event->catid)) {
			JFactory::getApplication()->triggerEvent('onEventDelete', array(
				is_numeric($event->id) ? $event->xreference : $event->id
			));
		}
		if (is_numeric($event->id)) {
			$this->getModel()->publish($recordId, -2);
			if (!$this->getModel()->delete($recordId)) {
				$this->setError(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
				$this->setMessage($this->getModel()->getError(), 'error');

				$this->setRedirect(
					JRoute::_(
						'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
						false
					)
				);

				return false;
			}
		}
		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage(), JText::_('COM_DPCALENDAR_DELETE_SUCCESS'));

		return true;
	}

	public function edit($key = 'id', $urlVar = 'e_id')
	{
		$context  = "$this->option.edit.$this->context";
		$cid      = $this->input->getVar('cid', array(), 'post', 'array');
		$recordId = (count($cid) ? $cid[0] : $this->input->getVar($urlVar));

		if (!$this->allowEdit(array($key => $recordId), $key)) {
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
					false
				)
			);

			return false;
		}
		if ($this->getModel()->getItem($recordId) != null && !is_numeric($recordId)) {
			$app    = JFactory::getApplication();
			$values = (array)$app->getUserState($context . '.id');

			array_push($values, $recordId);
			$values = array_unique($values);
			$app->setUserState($context . '.id', $values);
			$app->setUserState($context . '.data', null);

			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($recordId,
						$urlVar),
					false
				)
			);

			return true;
		}

		return parent::edit($key, $urlVar);
	}

	public function getModel($name = 'form', $prefix = '', $config = array('ignore_request' => true))
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
			$hash . '#' . trim($hash, '#');
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
			$hash . '#' . trim($hash, '#');
		}

		if (empty($return) || !JUri::isInternal(base64_decode($return))) {
			return JURI::base();
		} else {
			return JRoute::_(base64_decode($return)) . $hash;
		}
	}

	public function move()
	{
		$data       = array();
		$data['id'] = $this->input->getVar('id');
		$success    = false;
		$model      = $this->getModel('form', 'DPCalendarModel', array('ignore_request' => false));
		if (!$this->allowSave($data)) {
			$model->setError(JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
		} else {
			$event = $model->getItem($data['id']);
			$data  = ArrayHelper::fromObject($event);

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

			// If we were moved from a full day
			if ($event->all_day == 1 && $this->input->getInt('minutes') != '0') {
				$data['all_day'] = '0';
				$end->modify('+2 hour');
			}

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
				$tmp = JFactory::getApplication()->triggerEvent('onEventSave', array($data));
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
				DPCalendarHelper::sendMessage(
					JText::_('JLIB_APPLICATION_SAVE_SUCCESS'),
					false,
					array('url' => DPCalendarHelperRoute::getEventRoute($data['id'], $data['catid']))
				);

				return;
			}


			DPCalendarHelper::sendMessage(JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), true);

			return;
		}

		DPCalendarHelper::sendMessage($model->getError(), true);
	}

	public function save($key = null, $urlVar = 'e_id')
	{
		if ($this->input->getInt($urlVar)) {
			$this->context = 'form';
		}

		$data = $this->input->post->get('jform', array(), 'array');

		if (empty($data['start_date_time']) && empty($data['end_date_time'])) {
			$data['all_day'] = '1';
		}

		if (!key_exists('all_day', $data)) {
			$data['all_day'] = 0;
		}

		$app        = JFactory::getApplication();
		$dateFormat = $app->getParams()->get('event_form_date_format', 'm.d.Y');
		$timeFormat = $app->getParams()->get('event_form_time_format', 'g:i a');

		if ($data['start_date_time'] == '') {
			$data['start_date_time'] = DPCalendarHelper::getDate()->format($timeFormat);
		}
		if ($data['end_date_time'] == '') {
			$data['end_date_time'] = DPCalendarHelper::getDate()->format($timeFormat);
		}

		// Get the start date from the date
		$start = DPCalendarHelper::getDateFromString($data['start_date'], $data['start_date_time'],
			$data['all_day'] == '1', $dateFormat, $timeFormat);

		// Format the start date to SQL format
		$data['start_date'] = $start->toSql(false);

		// Get the start date from the date
		$end = DPCalendarHelper::getDateFromString($data['end_date'], $data['end_date_time'], $data['all_day'] == '1',
			$dateFormat, $timeFormat);
		if ($end->format('U') < $start->format('U')) {
			$end = clone $start;
			$end->modify('+30 min');
		}
		// Format the end date to SQL format
		$data['end_date'] = $end->toSql(false);

		$this->input->post->set('jform', $data);

		$result   = false;
		$calendar = DPCalendarHelper::getCalendar($data['catid']);
		if ($calendar->external) {
			JPluginHelper::importPlugin('dpcalendar');
			$data['id'] = $this->input->getVar($urlVar, null);

			$app->setUserState('com_dpcalendar.edit.event.data', $data);

			$model     = $this->getModel();
			$form      = $model->getForm($data, true);
			$validData = $model->validate($form, $data);
			$model->detach();

			if (isset($validData['all_day']) && $validData['all_day'] == 1) {
				$validData['start_date'] = DPCalendarHelper::getDate($validData['start_date'])->toSql(true);
				$validData['end_date']   = DPCalendarHelper::getDate($validData['end_date'])->toSql(true);
			}

			// If the calendar is native, then we are editing an event in
			// advanced cache mode
			if ($calendar->native) {
				$validData['id'] = $data['xreference'];
			}

			try {
				$tmp = JFactory::getApplication()->triggerEvent('onEventSave', array($validData));
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
			$canChangeState = $calendar->external || JFactory::getUser()->authorise('core.edit.state',
					'com_dpcalendar.category.' . $data['catid']);
			if ($this->getTask() == 'save') {
				$app->setUserState('com_dpcalendar.edit.event.data', null);
				$return = $this->getReturnPage();
				if ($return == JURI::base() && $canChangeState) {
					$return = DPCalendarHelperRoute::getEventRoute($app->getUserState('dpcalendar.event.id'),
						$data['catid']);
				}
				$this->setRedirect($return);
			}
			if ($this->getTask() == 'apply' || $this->getTask() == 'save2copy') {
				$return = $this->getReturnPage();
				if ($canChangeState) {
					$return = DPCalendarHelperRoute::getFormRoute($app->getUserState('dpcalendar.event.id'),
						$this->getReturnPage());
				}
				$this->setRedirect($return);
			}
			if ($this->getTask() == 'save2new') {
				$app->setUserState('com_dpcalendar.edit.event.data', null);
				$return = DPCalendarHelperRoute::getFormRoute(0, $this->getReturnPage());
				$this->setRedirect($return);
			}
		} else if (!$this->redirect) {
			$this->setRedirect(
				DPCalendarHelperRoute::getEventRoute($app->getUserState('dpcalendar.event.id'), $data['catid'])
			);
		}

		return $result;
	}

	public function invite()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$data = $this->input->post->get('jform', array(), 'array');
		$this->getModel()->invite(
			$data['event_id'],
			isset($data['users']) ? $data['users'] : array(),
			isset($data['groups']) ? $data['groups'] : array()
		);

		$this->setRedirect(
			base64_decode($this->input->getBase64('return')),
			JText::_('COM_DPCALENDAR_SENT_INVITATION')
		);
	}

	public function overlapping()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$data = $this->input->get('jform', array(), 'array');

		if (empty($data['start_date_time']) && empty($data['end_date_time'])) {
			$data['all_day'] = '1';
		}

		$startDate = DPCalendarHelper::getDateFromString(
			$data['start_date'],
			$data['start_date_time'],
			$data['all_day'] == '1'
		);
		$endDate   = DPCalendarHelper::getDateFromString(
			$data['end_date'],
			$data['end_date_time'],
			$data['all_day'] == '1'
		);

		$model = $this->getModel('Events');
		$model->getState();
		$model->setState('list.limit', 2);
		$model->setState('category.id', $data['catid']);
		$model->setState('filter.ongoing', false);
		$model->setState('filter.expand', true);
		$model->setState('filter.language', $data['language']);
		$model->setState('list.start-date', $startDate);
		$model->setState('list.end-date', $endDate);

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
			if ($e->id != $data['id']) {
				continue;
			}
			unset($events[$key]);
			break;
		}

		$event                = new stdClass();
		$event->start_date    = $startDate->toSql();
		$event->end_date      = $endDate->toSql();
		$event->all_day       = $data['all_day'];
		$event->show_end_time = true;
		$date                 = DPCalendarHelper::getDateStringFromEvent($event);
		$message              = DPCalendarHelper::renderEvents(
			$events,
			JText::_('COM_DPCALENDAR_VIEW_FORM_OVERLAPING_EVENTS_' . ($events ? '' : 'NOT_') . 'FOUND'), null,
			array(
				'checkDate'    => $date,
				'calendarName' => DPCalendarHelper::getCalendar($data['catid'])->title
			)
		);

		DPCalendarHelper::sendMessage(
			null,
			false,
			array('message' => $message, 'count' => count($events))
		);
	}

	public function checkin()
	{
		// Check for request forgeries.
		\JSession::checkToken('get') or jexit(\JText::_('JINVALID_TOKEN'));

		$model = $this->getModel();
		$event = $model->getItem($this->input->getInt('e_id'));

		$message = JText::sprintf('COM_DPCALENDAR_N_ITEMS_CHECKED_IN_1', 1);
		$type    = null;

		if ($model->checkin([$event->id]) === false) {
			// Checkin failed
			$message = \JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError());
			$type    = 'error';
		}

		$this->setRedirect(DPCalendarHelperRoute::getEventRoute($event->id, $event->catid), $message, $type);

		return $type == null;
	}

	public function reload($key = null, $urlVar = 'e_id')
	{
		$data = $this->input->post->get('jform', array(), 'array');

		if (empty($data['start_date_time']) && empty($data['end_date_time'])) {
			$data['all_day'] = '1';
		}

		$data['start_date'] = DPCalendarHelper::getDateFromString(
			$data['start_date'],
			$data['start_date_time'],
			$data['all_day'] == '1'
		)->toSql(false);
		$data['end_date']   = DPCalendarHelper::getDateFromString(
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
}
