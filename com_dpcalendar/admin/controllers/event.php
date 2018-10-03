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

	protected function allowAdd($data = array())
	{
		// Initialise variables.
		$user       = JFactory::getUser();
		$categoryId = ArrayHelper::getValue($data, 'catid', $this->input->getInt('filter_category_id'), 'int');
		$allow      = null;

		if ($categoryId) {
			// If the category has been passed in the URL check it.
			$allow = $user->authorise('core.create', $this->option . '.category.' . $categoryId);
		}

		if ($allow === null) {
			// In the absense of better information, revert to the component
			// permissions.
			return parent::allowAdd($data);
		} else {
			return $allow;
		}
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
		$recordId = (int)isset($data[$key]) ? $data[$key] : 0;
		$event    = null;

		if ($recordId) {
			$event = $this->getModel()->getItem($recordId);
		}

		if ($event != null) {
			$calendar = DPCalendarHelper::getCalendar($event->catid);

			return $calendar->canEdit || ($calendar->canEditOwn && $event->created_by == JFactory::getUser()->id);
		} else {
			// Since there is no asset tracking, revert to the component
			// permissions.
			return parent::allowEdit($data, $key);
		}
	}

	public function save($key = null, $urlVar = 'e_id')
	{
		$this->transformDatesToSql();

		$data = $this->input->post->get('jform', array(), 'array');

		if (!key_exists('color', $data)) {
			$data['color'] = '';
		}

		$this->input->post->set('jform', $data);

		$result = false;
		if (!is_numeric($data['catid'])) {
			JPluginHelper::importPlugin('dpcalendar');
			$data['id'] = $this->input->getInt($urlVar, null);

			$model     = $this->getModel();
			$form      = $model->getForm($data, false);
			$validData = $model->validate($form, $data);

			if ($validData['all_day'] == 1) {
				$validData['start_date'] = DPCalendarHelper::getDate($validData['start_date'])->toSql(true);
				$validData['end_date']   = DPCalendarHelper::getDate($validData['end_date'])->toSql(true);
			}

			$tmp = JFactory::getApplication()->triggerEvent('onEventSave', array($validData));
			foreach ($tmp as $newEventId) {
				if ($newEventId === false) {
					continue;
				}
				$result = true;
				switch ($this->getTask()) {
					case 'apply':
						$this->setRedirect(
							JRoute::_(
								'index.php?option=' . $this->option . '&view=' .
								$this->view_item . $this->getRedirectToItemAppend($newEventId, $urlVar),
								false
							)
						);
						break;
					case 'save2new':
						$this->setRedirect(
							JRoute::_(
								'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend(null, $urlVar),
								false
							)
						);
						break;
					default:
						$this->setRedirect(
							JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false));
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
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=events' . $this->getRedirectToListAppend(), false));

		return parent::batch($this->getModel());
	}

	public function reload($key = null, $urlVar = 'e_id')
	{
		$this->transformDatesToSql();

		return parent::reload($key, $urlVar);
	}

	public function getModel($name = 'AdminEvent', $prefix = 'DPCalendarModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function similar()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$formData = $this->input->get('jform', array(), 'array');

		if (empty($formData['title'])) {
			DPCalendarHelper::sendMessage(null);
		}

		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = $this->getModel('Events');
		$model->getState();
		$model->setState('list.limit', 5);
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
			$item->details = strip_tags(JHtml::_('string.truncate', $e->description, 100));

			$data[] = $item;
		}

		DPCalendarHelper::sendMessage(null, false, $data);
	}

	public function reloadfromevent($key = null, $urlVar = 'e_id')
	{
		// Check for request forgeries.
		\JSession::checkToken() or jexit(\JText::_('JINVALID_TOKEN'));

		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$data = $this->getModel('Event')->getItem($this->input->getInt('template_event_id'));

		if (!$data) {
			return parent::reload($key, $urlVar);
		}

		$formData = $this->input->post->get('jform', array(), 'array');

		$data->id = !empty($formData['id']) ? $formData['id'] : 0;

		$this->input->set('jform', (array)$data);
		$this->input->post->set('jform', (array)$data);

		return parent::reload($key, $urlVar);
	}

	private function transformDatesToSql()
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
	}
}
