<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Router\Router;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class EventController extends FormController implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $view_item         = 'form';
	protected $view_list         = 'calendar';
	protected $option            = 'com_dpcalendar';
	private string $editCalendar = '';

	public function add(): bool
	{
		if (!parent::add()) {
			// Redirect to the return page.
			$this->setRedirect($this->getReturnPage());
			return false;
		}

		return true;
	}

	protected function allowAdd($data = [])
	{
		$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar(ArrayHelper::getValue(
			$data,
			'catid',
			$this->input->getString('id', ''),
			'string'
		));
		if ($calendar instanceof CalendarInterface) {
			return $calendar->canCreate();
		}

		return parent::allowAdd($data);
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$recordId = $data[$key] ?? 0;
		$event    = null;

		if ($recordId) {
			$event = $this->getModel()->getItem($recordId);
		}

		if ($event != null) {
			$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);

			return $calendar instanceof CalendarInterface && ($calendar->canEdit() || ($calendar->canEditOwn() && $event->created_by == $this->getCurrentUser()->id));
		}

		return parent::allowEdit($data, $key);
	}

	protected function allowDelete(array $data = [], string $key = 'id'): bool
	{
		$calendar = null;
		$event    = null;
		if (isset($data['catid'])) {
			$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($data['catid']);
		}
		if ($calendar == null) {
			$recordId = (int)isset($data[$key]) !== 0 ? $data[$key] : 0;
			$event    = $this->getModel()->getItem($recordId);
			$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event ? $event->catid : 0);
		}

		if ($calendar != null && $event != null) {
			if ($calendar->canDelete()) {
				return true;
			}
			return $calendar->canEditOwn() && $event->created_by == $this->getCurrentUser()->id;
		}

		return $this->getCurrentUser()->authorise('core.delete', $this->option);
	}

	public function cancel($key = 'e_id')
	{
		$success  = true;
		$return   = true;
		$recordId = $this->input->getString($key, '');
		if (!$recordId || is_numeric($recordId)) {
			$success = parent::cancel($key);
		}

		$return = $this->getReturnPage();

		$params = $this->getModel('Event', 'Administrator', ['ignore_request' => false])->getState('params', new Registry());
		if ($redirect = $params->get('event_form_redirect')) {
			$article = $this->app->bootComponent('content')->getMVCFactory()->createTable('Article', 'Administrator');
			$article->load($redirect);

			if (!empty($article->id) && !empty($article->catid)) {
				$return = Route::_('index.php?option=com_content&view=article&id=' . $article->id . '&catid=' . $article->catid);
			}
		}

		$this->setRedirect($return);

		return $success;
	}

	public function delete(string $key = 'e_id'): bool
	{
		$recordId = $this->input->getString($key, '');

		if (!$this->allowDelete([$key => $recordId], $key)) {
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');

			$this->setRedirect(
				Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false)
			);

			return false;
		}

		$event = $this->getModel()->getItem($recordId);

		if ($event && !is_numeric($event->catid)) {
			$this->app->triggerEvent('onEventDelete', [is_numeric($event->id) ? $event->xreference : $event->id]);
		}

		if ($event && is_numeric($event->id)) {
			$recordId = [$recordId];
			$this->getModel()->getTable('Event', 'Administrator')->publish($recordId, -2, $this->getCurrentUser()->id);
			if (!$this->getModel('Form')->delete($recordId)) {
				$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');

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
		if ($return = $this->input->get('return', null, 'default')) {
			$redirect = base64_decode((string)$return);

			if (($hash = $this->input->getString('urlhash', '')) !== '' && ($hash = $this->input->getString('urlhash', '')) !== '0') {
				$redirect .= '#' . trim($hash, '#');
			}
		}
		$this->setRedirect($redirect, Text::_('COM_DPCALENDAR_DELETE_SUCCESS'));

		return true;
	}

	public function edit($key = 'id', $urlVar = 'e_id')
	{
		$context  = \sprintf('%s.edit.%s', $this->option, $this->context);
		$cid      = $this->input->get('cid', [], 'post');
		$recordId = (\count($cid) > 0 ? $cid[0] : $this->input->getString($urlVar, ''));

		if (!$this->allowEdit([$key => $recordId], $key)) {
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
					false
				)
			);

			return false;
		}

		$event = $this->getModel()->getItem($recordId);
		if ($event instanceof \stdClass) {
			$this->editCalendar = $event->catid;
		}

		if ($event instanceof \stdClass && !is_numeric($recordId)) {
			$values = (array)$this->app->getUserState($context . '.id');

			$values[] = $recordId;
			$values   = array_unique($values);
			$this->app->setUserState($context . '.id', $values);
			$this->app->setUserState($context . '.data', null);

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

		$success            = parent::edit($key, $urlVar);
		$this->editCalendar = '';

		return $success;
	}

	public function getModel($name = 'Form', $prefix = 'Site', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = null): string
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar ?: '');
		$itemId = $this->input->getInt('Itemid', 0);
		$return = $this->getReturnPage();

		$hash = $this->input->getString('urlhash', '');
		if ($hash !== '' && $hash !== '0') {
			$hash = '#' . trim($hash, '#');
		}

		if ($itemId !== 0) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($return !== '' && $return !== '0') {
			$append .= '&return=' . base64_encode($return);
		}

		if ($this->editCalendar !== '' && $this->editCalendar !== '0') {
			$append .= '&calid=' . $this->editCalendar;
		}

		return $append . $hash;
	}

	protected function getReturnPage(): string
	{
		$return = $this->input->get('return', null, 'default');
		$hash   = $this->input->getString('urlhash', '');
		if ($hash !== '' && $hash !== '0') {
			$hash = '#' . trim($hash, '#');
		}

		if (empty($return) || !Uri::isInternal(base64_decode((string)$return))) {
			return Uri::base();
		}

		return Route::_(base64_decode((string)$return), false) . $hash;
	}

	public function move(): void
	{
		$data       = [];
		$data['id'] = $this->input->getString('id', '');
		$success    = false;
		$model      = $this->getModel('Form');
		// Load state, so the event id won't be overwritten on load state
		$model->getState();

		if (!$this->allowSave($data)) {
			throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
		}

		$event = $model->getItem($data['id']);
		if (!$event) {
			throw new \Exception('Event not found');
		}

		// Unset tags helper as it gets converted to array
		if (isset($event->tagsHelper)) {
			unset($event->tagsHelper);
		}

		$data = ArrayHelper::fromObject($event);

		if (!empty($data['tags']) && \array_key_exists('tags', $data['tags'])) {
			$data['tags'] = explode(',', (string)$data['tags']['tags']);
		}

		$start = DPCalendarHelper::getDate($event->start_date, $event->all_day);
		$end   = DPCalendarHelper::getDate($event->end_date, $event->all_day);

		$minutes = $this->input->getInt('minutes', 0) . ' minute';
		if (!str_contains($minutes, '-')) {
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
			$tmp = $this->app->triggerEvent('onEventSave', [$data]);
			foreach ($tmp as $newEventId) {
				if ($newEventId === '') {
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

		if ($success) {
			$event = $model->getItem($data['id']);

			if ($event && $event->start_date == $data['start_date'] && $event->end_date == $data['end_date']) {
				$displayData = [
					'app'          => $this->app,
					'router'       => new Router(),
					'layoutHelper' => $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Layout', 'Administrator'),
					'translator'   => new Translator(),
					'input'        => $this->input,
					'params'       => $this->app->getMenu() ? ($this->app->getMenu()->getActive() ? $this->app->getMenu()->getActive()->getParams() : new Registry()) : new Registry()
				];
				$displayData['event'] = $event;
				$description          = trim((string)$displayData['layoutHelper']->renderLayout('event.tooltip', $displayData));
				$description          = DPCalendarHelper::fixImageLinks($description);

				DPCalendarHelper::sendMessage(
					Text::_('JLIB_APPLICATION_SAVE_SUCCESS'),
					false,
					['url' => RouteHelper::getEventRoute($data['id'], $data['catid']), 'description' => $description]
				);

				return;
			}


			DPCalendarHelper::sendMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), true);

			return;
		}

		DPCalendarHelper::sendMessage($model->getError(), true);
	}

	public function saveajax(?string $key = null, ?string $urlVar = 'e_id'): void
	{
		$success = $this->save($key, $urlVar ?? 'e_id');

		DPCalendarHelper::sendMessage($success ? '' : $this->message, $success, ['id' => $this->app->getUserState('dpcalendar.event.id')]);
	}

	public function save($key = null, $urlVar = 'e_id')
	{
		if ($this->input->getInt($urlVar, 0) !== 0) {
			$this->context = 'form';
		}

		$params = $this->app instanceof SiteApplication ? $this->app->getParams() : ComponentHelper::getParams('com_dpcalendar');

		$data = $this->input->post->get('jform', [], 'array');

		if (empty($data['start_date_time']) && empty($data['end_date_time'])) {
			$data['all_day'] = '1';
		}

		if (!\array_key_exists('all_day', $data)) {
			$data['all_day'] = 0;
		}

		if (!\array_key_exists('color', $data)) {
			$data['color'] = '';
		}

		if (!\array_key_exists('payment_provider', $data)) {
			$data['payment_provider'] = '';
		}

		if (!\array_key_exists('capacity', $data)) {
			$data['capacity'] = $params->get('event_form_capacity', '0');
		}

		$dateFormat = $params->get('event_form_date_format', 'd.m.Y');
		$timeFormat = $params->get('event_form_time_format', 'H:i');
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

		if (!empty($data['exdates'])) {
			foreach ($data['exdates'] as $key => $date) {
				$date['date']          = DPCalendarHelper::getDateFromString($date['date'], null, true)->toSql(false);
				$data['exdates'][$key] = $date;
			}
		}

		if (!empty($data['location_ids'])) {
			$data['location_ids'] = (array)$data['location_ids'];
			foreach ($data['location_ids'] as $index => $locationId) {
				if (is_numeric($locationId) || !$locationId) {
					continue;
				}

				[$title, $coordinates] = explode(' [', (string)$locationId);

				$location = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation(trim($coordinates, ']'), true, $title);
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
				if (\array_key_exists($field, $data)) {
					unset($data[$field]);
				}
			}

			// Unset also the capacity
			if (\array_key_exists('capacity', $data)) {
				unset($data['capacity']);
			}
		}

		$this->input->post->set('jform', $data);

		$result   = false;
		$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($data['catid']);
		if ($calendar instanceof ExternalCalendarInterface) {
			PluginHelper::importPlugin('dpcalendar');
			$data['id'] = $this->input->getString($urlVar, '');

			// Unset the uid when empty so it will correctly be created for a new event
			if ($data['uid'] === '0') {
				unset($data['uid']);
			}

			$this->app->setUserState('com_dpcalendar.edit.event.data', $data);

			$model     = $this->getModel('Form');
			$form      = $model->getForm($data, true);
			$validData = $model->validate($form, $data);

			if (!\is_array($validData)) {
				// @phpstan-ignore-next-line
				foreach ($model->getErrors() as $error) {
					$this->setMessage($error instanceof \Exception ? $error->getMessage() : $error, 'error');
				}
				$this->setRedirect(RouteHelper::getFormRoute($this->app->getUserState('dpcalendar.event.id'), $this->getReturnPage()));

				return false;
			}

			if (isset($validData['all_day']) && $validData['all_day'] == 1) {
				$validData['start_date'] = DPCalendarHelper::getDate($validData['start_date'])->toSql(true);
				$validData['end_date']   = DPCalendarHelper::getDate($validData['end_date'])->toSql(true);
			}

			$native = $calendar->getParams()->get('native', false);

			// If the calendar is native, then we are editing an event in advanced cache mode
			if ($native && !empty($data['xreference'])) {
				$validData['id'] = $data['xreference'];
			}

			try {
				$tmp = $this->app->triggerEvent('onEventSave', [$validData]);
			} catch (\InvalidArgumentException $e) {
				$this->setMessage($e->getMessage(), 'error');

				$this->setRedirect(RouteHelper::getFormRoute($this->app->getUserState('dpcalendar.event.id'), $this->getReturnPage()));

				return false;
			}

			$this->setMessage(Text::_('COM_DPCALENDAR_SAVE_SUCCESS'));

			foreach ($tmp as $newEventId) {
				if ($newEventId === '') {
					continue;
				}

				$this->app->setUserState('dpcalendar.event.id', $newEventId);

				// If the id is numeric wee need to save it in the database too
				if ($native) {
					$validData['xreference'] = $newEventId;
					$this->input->post->set('jform', $validData);
					$result = parent::save($key, $urlVar);
				} else {
					$result = true;
					$return = $this->input->getBase64('return');
					if (!empty($urlVar) && !empty($return) && (isset($data['id']) && ($data['id'] !== '' && $data['id'] !== '0'))) {
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
			$canChangeState = $calendar instanceof ExternalCalendarInterface
				|| $this->getCurrentUser()->authorise('core.edit.state', 'com_dpcalendar.category.' . $data['catid']);
			if ($this->getTask() === 'save') {
				$this->app->setUserState('com_dpcalendar.edit.event.data', null);
				$return = $this->getReturnPage();

				$params = $this->getModel('Event', 'Administrator', ['ignore_request' => false])->getState('params', new Registry());
				if ($redirect = $params->get('event_form_redirect')) {
					$article = $this->app->bootComponent('content')->getMVCFactory()->createTable('Article', 'Administrator');
					$article->load($redirect);

					if (!empty($article->id) && !empty($article->catid)) {
						$return = Route::_('index.php?option=com_content&view=article&id=' . $article->id . '&catid=' . $article->catid);
					}
				}

				if ($return === Uri::base() && $canChangeState) {
					$return = RouteHelper::getEventRoute($this->app->getUserState('dpcalendar.event.id'), $data['catid']);
				}
				$this->setRedirect($return);
			}
			if ($this->getTask() == 'apply' || $this->getTask() == 'save2copy') {
				$return = $this->getReturnPage();
				if ($canChangeState) {
					$return = RouteHelper::getFormRoute($this->app->getUserState('dpcalendar.event.id'), $this->getReturnPage());
				}
				$this->setRedirect($return);
			}
			if ($this->getTask() == 'save2new') {
				$this->app->setUserState('com_dpcalendar.edit.event.data', null);
				$this->setRedirect(RouteHelper::getFormRoute('0', $this->getReturnPage()));
			}
		} elseif ($this->redirect === '' || $this->redirect === '0') {
			$this->setRedirect(
				RouteHelper::getEventRoute($this->app->getUserState('dpcalendar.event.id'), $data['catid'])
			);
		}

		return $result;
	}

	public function invite(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = $this->input->post->get('jform', [], 'array');
		$this->getModel('Form')->invite((int)$data['event_id'], $data['users'] ?? [], $data['groups'] ?? []);

		$this->setRedirect(base64_decode($this->input->getBase64('return')), Text::_('COM_DPCALENDAR_SENT_INVITATION'));
	}

	public function mailtickets(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = $this->input->post->get('jform', [], 'array');
		$this->getModel('Form')->mailtickets((int)$data['event_id'], $data['subject'], $data['body'], $data['tickets']);

		$this->setRedirect(
			$this->getReturnPage(),
			Text::_('COM_DPCALENDAR_CONTROLLER_EVENT_MAILTICKETS_SENT')
		);
	}

	public function mailticketsuser(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = $this->input->post->get('jform', [], 'array');
		$this->getModel('Form')->mailtickets($data['event_id'], $data['subject'], $data['body'], [-1]);

		$this->setRedirect($this->getReturnPage());
	}

	public function similar(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$formData = $this->input->get('jform', [], 'array');

		if (empty($formData['title'])) {
			DPCalendarHelper::sendMessage('');
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
		$model->setState('filter.search', '+' . str_replace(' ', " +", strtolower((string)$formData['title'])));

		if (!isset($formData['id']) || !$formData['id']) {
			$formData['id'] = $this->input->get('id', 0);
		}

		$data = [];
		foreach ($model->getItems() as $e) {
			if ($formData['id'] && ($e->id == $formData['id'] || $e->original_id == $formData['id'])) {
				continue;
			}

			$item          = new \stdClass();
			$item->value   = $e->id;
			$item->title   = $e->title;
			$item->details = '[' . DPCalendarHelper::getDateStringFromEvent($e) . '] ' .
				strip_tags((string)HTMLHelper::_('string.truncate', $e->description, 100));

			$data[] = $item;
		}

		DPCalendarHelper::sendMessage('', false, $data);
	}

	public function checkin(): bool
	{
		// Check for request forgeries.
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$model = $this->getModel('Form');
		$event = $model->getItem($this->input->getInt('e_id', 0));
		if ($event === false || empty($event->id) || empty($event->catid)) {
			throw new \Exception('No event found');
		}

		$message = Text::sprintf('COM_DPCALENDAR_N_ITEMS_CHECKED_IN_1', 1);
		$type    = null;

		if ($model->checkin([$event->id]) === false) {
			// Checkin failed
			$message = Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError());
			$type    = 'error';
		}

		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			DPCalendarHelper::sendMessage($message, $type !== null);
			return true;
		}

		$this->setRedirect(RouteHelper::getEventRoute($event->id, $event->catid), $message, $type);

		return $type === null;
	}

	public function reloadfromevent(?string $key = null, ?string $urlVar = 'e_id'): void
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = $this->getModel('Event')->getItem($this->input->getInt('template_event_id', 0));

		if (!$data) {
			parent::reload($key, $urlVar);
			return;
		}

		$formData = $this->input->post->get('jform', [], 'array');

		$data->id = empty($formData['id']) ? 0 : $formData['id'];

		// Reset the color when equal to calendar
		$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($data->catid);
		if ($calendar instanceof CalendarInterface && $data->color === $calendar->getColor()) {
			$data->color = '';
		}

		$this->input->set('jform', (array)$data);
		$this->input->post->set('jform', (array)$data);

		parent::reload($key, $urlVar);
	}

	public function reload($key = null, $urlVar = 'e_id'): void
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

		parent::reload($key, $urlVar);
	}

	public function newlocation(): void
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$location = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($this->input->getString('lookup', ''), false, $this->input->getString('lookup_title', ''));

		$data = [];
		if ($location->title) {
			$data = [
				'id'      => $location->id,
				'display' => $location->title . ' [' . $location->latitude . ',' . $location->longitude . ']'
			];
		}
		DPCalendarHelper::sendMessage('', $data === [], $data);
	}
}
