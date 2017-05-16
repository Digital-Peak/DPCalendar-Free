<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

JLoader::import('joomla.application.component.modeladmin');

class DPCalendarModelAdminEvent extends JModelAdmin
{

	protected $text_prefix = 'COM_DPCALENDAR';
	protected $name = 'event';
	private $eventHandler = null;

	/**
	 * This is a temp holder of the event instance which gets deleted.
	 * For some reason, Joomla deletes all instance variables after the delete operation.
	 * This variable will hold the event for notifications only.
	 *
	 * @var JTable
	 */
	private $tmpDeleteEvent = null;

	public function __construct($config = array())
	{
		parent::__construct($config);
		$dispatcher         = JEventDispatcher::getInstance();
		$this->eventHandler = new DPCalendarModelAdminEventHandler($dispatcher, $this);
	}

	protected function populateState()
	{
		parent::populateState();

		$this->setState($this->getName() . '.id', JFactory::getApplication()->input->getInt('e_id'));
	}

	protected function canDelete($record)
	{
		if (!empty($record->id)) {
			if ($record->state != -2) {
				return false;
			}
			$user     = JFactory::getUser();
			$calendar = DPCalendarHelper::getCalendar($record->catid);

			if ($calendar->canDelete || ($calendar->canEditOwn && $record->created_by == JFactory::getUser()->id)) {
				return true;
			} else {
				return parent::canDelete($record);
			}
		}
	}

	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		if (!empty($record->catid)) {
			return $user->authorise('core.edit.state', 'com_dpcalendar.category.' . (int)$record->catid);
		} else {
			return parent::canEditState('com_dpcalendar');
		}
	}

	public function getTable($type = 'Event', $prefix = 'DPCalendarTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.event', 'event', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		$eventId = $this->getState('event.id', 0);

		// Determine correct permissions to check.
		if ($eventId) {
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit');
		} else {
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.create');
		}

		$item = $this->getItem();

		// Modify the form based on access controls.
		if (!$this->canEditState($item)) {
			// Disable fields for display.
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');

			// Disable fields while saving
			$form->setFieldAttribute('state', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
		}

		$form->setFieldAttribute('start_date', 'all_day', $item->all_day);
		$form->setFieldAttribute('end_date', 'all_day', $item->all_day);

		if (DPCalendarHelper::isFree()) {
			// Disable fields for display.
			$form->setFieldAttribute('rrule', 'disabled', 'true');
			$form->setFieldAttribute('capacity', 'disabled', 'true');
			$form->setFieldAttribute('capacity_used', 'disabled', 'true');
			$form->setFieldAttribute('max_tickets', 'disabled', 'true');
			$form->setFieldAttribute('price', 'disabled', 'true');
			$form->setFieldAttribute('plugintype', 'disabled', 'true');

			// Disable fields while saving.
			$form->setFieldAttribute('rrule', 'filter', 'unset');
			$form->setFieldAttribute('capacity', 'filter', 'unset');
			$form->setFieldAttribute('capacity_used', 'filter', 'unset');
			$form->setFieldAttribute('max_tickets', 'filter', 'unset');
			$form->setFieldAttribute('price', 'filter', 'unset');
			$form->setFieldAttribute('plugintype', 'filter', 'unset');
		}

		if (!DPCalendarHelper::isCaptchaNeeded()) {
			$form->removeField('captcha');
		}

		return $form;
	}

	protected function loadFormData()
	{
		$app = JFactory::getApplication();

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_dpcalendar.edit.event.data', array());

		if (empty($data)) {
			$data    = $this->getItem();
			$eventId = $this->getState('event.id');

			// Prime some default values.
			if ($eventId == '0') {
				$data->set('catid', $app->input->getCmd('catid', $app->getUserState('com_dpcalendar.events.filter.category_id')));
			}
		}

		if (is_array($data)) {
			$data = new JObject($data);
		}

		if ($data->get(('start_date_time'))) {
			try {
				// We got the data from a session, normalizing it
				$data->set('start_date',
					DPCalendarHelper::getDateFromString($data->get('start_date'), $data->get('start_date_time'), $data->get('all_day'))->toSql());
				$data->set('end_date',
					DPCalendarHelper::getDateFromString($data->get('end_date'), $data->get('end_date_time'), $data->get('all_day'))->toSql());
			} catch (Exception $e) {
				// Silently ignore the error
			}
		}

		if (!$data->get('id')) {
			$data->set('capacity', 0);
		}

		if ((!isset($data->location_ids) || !$data->location_ids) && isset($data->location_ids) && $data->location_ids) {
			$data->location_ids = array();
			foreach ($data->locations as $location) {
				$data->location_ids[] = $location->id;
			}
		}

		// Forms can't handle registry objects on load
		if (isset($data->metadata) && $data->metadata instanceof Registry) {
			$data->metadata = $data->metadata->toArray();
		}

		$this->preprocessData('com_dpcalendar.event', $data);

		// Migrate subform data to old repeatable format
		if (isset($data->price) && is_string($data->price) && $data->price) {
			$obj     = json_decode($data->price);
			$newData = array();
			foreach ($obj->value as $index => $value) {
				$newData['price' . ($index + 1)] = array(
					'value'       => $value,
					'label'       => $obj->label[$index],
					'description' => $obj->description[$index]
				);
			}
			$data->price = json_encode($newData);
		}
		if (isset($data->earlybird) && is_string($data->earlybird) && $data->earlybird) {
			$obj     = json_decode($data->earlybird);
			$newData = array();
			foreach ($obj->value as $index => $value) {
				$newData['price' . ($index + 1)] = array(
					'value'       => $value,
					'type'        => $obj->type[$index],
					'date'        => $obj->date[$index],
					'label'       => $obj->label[$index],
					'description' => $obj->description[$index]
				);
			}
			$data->earlybird = json_encode($newData);
		}
		if (isset($data->user_discount) && is_string($data->user_discount) && $data->user_discount) {
			$obj     = json_decode($data->user_discount);
			$newData = array();
			foreach ($obj->value as $index => $value) {
				$newData['price' . ($index + 1)] = array(
					'value'       => $value,
					'type'        => $obj->type[$index],
					'date'        => $obj->date[$index],
					'label'       => $obj->label[$index],
					'description' => $obj->description[$index]
				);
			}
			$data->user_discount = json_encode($newData);
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		$pk   = (!empty($pk)) ? $pk : $this->getState($this->getName() . '.id');
		$item = null;
		if (!empty($pk) && !is_numeric($pk)) {
			JPluginHelper::importPlugin('dpcalendar');
			$tmp = JEventDispatcher::getInstance()->trigger('onEventFetch', array($pk));
			if (!empty($tmp)) {
				$item = $tmp[0];
			}
		} else {
			$item = parent::getItem($pk);
			if ($item != null) {
				// Convert the params field to an array.
				$registry = new JRegistry();
				$registry->loadString($item->metadata);
				$item->metadata = $registry->toArray();

				// Convert the images field to an array.
				$registry = new JRegistry();
				if (isset($item->images)) {
					$registry->loadString($item->images);
				}
				$item->images = $registry->toArray();

				if ($item->id > 0) {
					$this->_db->setQuery('select location_id from #__dpcalendar_events_location where event_id = ' . (int)$item->id);
					$locations = $this->_db->loadObjectList();
					if (!empty($locations)) {
						$item->location_ids = array();
						foreach ($locations as $location) {
							$item->location_ids[] = $location->location_id;
						}
					}

					$item->tags = new JHelperTags();
					$item->tags->getTagIds($item->id, 'com_dpcalendar.event');
				}
				$item->tickets = $this->getTickets($item->id);
			}
		}

		return $item;
	}

	public function save($data)
	{
		$locationIds = array();
		if (isset($data['location_ids'])) {
			$locationIds = $data['location_ids'];
			unset($data['location_ids']);
		}

		$oldEventIds = array();
		if (isset($data['id']) && $data['id']) {
			$this->getDbo()->setQuery('select id from #__dpcalendar_events where original_id = ' . (int)$data['id']);
			$rows = $this->getDbo()->loadObjectList();
			foreach ($rows as $oldEvent) {
				$oldEventIds[$oldEvent->id] = $oldEvent->id;
			}
		}
		$this->setState('dpcalendar.event.oldEventIds', serialize($oldEventIds));
		$this->setState('dpcalendar.event.locationids', $locationIds);
		$this->setState('dpcalendar.event.data', $data);

		if ($data['all_day'] == 1 && !isset($data['date_range_correct'])) {
			$data['start_date'] = DPCalendarHelper::getDate($data['start_date'], $data['all_day'])->toSql(true);
			$data['end_date']   = DPCalendarHelper::getDate($data['end_date'], $data['all_day'])->toSql(true);
		}

		if (isset($data['images']) && is_array($data['images'])) {
			$registry = new JRegistry();
			$registry->loadArray($data['images']);
			$data['images'] = (string)$registry;
		}

		// Alter the title for save as copy
		if (JFactory::getApplication()->input->getVar('task') == 'save2copy') {
			list ($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
			$data['title'] = $title;
			$data['alias'] = $alias;
		}

		// Migrate subform data to old repeatable format
		if (isset($data['price']) && is_array($data['price'])) {
			$obj              = new stdClass();
			$obj->value       = array();
			$obj->label       = array();
			$obj->description = array();
			foreach ($data['price'] as $key => $p) {
				$obj->value[]       = $p['value'];
				$obj->label[]       = $p['label'];
				$obj->description[] = $p['description'];
			}
			$data['price'] = json_encode($obj);
		}
		if (isset($data['earlybird']) && is_array($data['earlybird'])) {
			$obj              = new stdClass();
			$obj->value       = array();
			$obj->type        = array();
			$obj->date        = array();
			$obj->label       = array();
			$obj->description = array();
			foreach ($data['earlybird'] as $key => $p) {
				$obj->value[]       = $p['value'];
				$obj->type[]        = $p['type'];
				$obj->date[]        = $p['date'];
				$obj->label[]       = $p['label'];
				$obj->description[] = $p['description'];
			}
			$data['earlybird'] = json_encode($obj);
		}
		if (isset($data['user_discount']) && is_array($data['user_discount'])) {
			$obj              = new stdClass();
			$obj->value       = array();
			$obj->type        = array();
			$obj->date        = array();
			$obj->label       = array();
			$obj->description = array();
			foreach ($data['user_discount'] as $key => $p) {
				$obj->value[]       = $p['value'];
				$obj->type[]        = $p['type'];
				$obj->date[]        = $p['date'];
				$obj->label[]       = $p['label'];
				$obj->description[] = $p['description'];
			}
			$data['user_discount'] = json_encode($obj);
		}

		if (!empty($data['price']) && is_string($data['price'])) {
			$prices = json_decode($data['price']);

			$hasprice = false;
			foreach ($prices->value as $index => $value) {
				if ($value || $prices->label[$index] || $prices->description[$index]) {
					$hasprice = true;
					break;
				}
			}

			if (!$hasprice) {
				$data['price'] = '';
			}
		}

		return parent::save($data);
	}

	protected function prepareTable($table)
	{
		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		$table->alias = JApplication::stringURLSafe($table->alias);

		if (empty($table->alias)) {
			$table->alias = JApplication::stringURLSafe($table->title);
		}

		if (!isset($table->state) && $this->canEditState($table)) {
			$table->state = 1;
		}
	}

	public function batch($commands, $pks, $contexts)
	{
		$result = parent::batch($commands, $pks, $contexts);

		if (!empty($commands['color_id'])) {
			$user  = JFactory::getUser();
			$table = $this->getTable();
			foreach ($pks as $pk) {
				if ($user->authorise('core.edit', $contexts[$pk])) {
					$table->reset();
					$table->load($pk);
					$table->color = $commands['color_id'];

					if (!$table->store()) {
						$this->setError($table->getError());

						return false;
					}
				} else {
					$this->setError(JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));

					return false;
				}
			}

			$this->cleanCache();

			return true;
		}

		return $result;
	}

	protected function batchTag($value, $pks, $contexts)
	{
		$return = parent::batchTag($value, $pks, $contexts);

		if ($return) {
			$user  = JFactory::getUser();
			$table = $this->getTable();
			foreach ($pks as $pk) {
				if ($user->authorise('core.edit', $contexts[$pk])) {
					$table->reset();
					$table->load($pk);

					// If we are a recurring event, then save the tags on the
					// children too
					if ($table->original_id == '-1') {
						$newTags = new JHelperTags();
						$newTags = $newTags->getItemTags('com_dpcalendar.event', $table->id);
						$newTags = array_map(function ($t) {
							return $t->id;
						}, $newTags);

						$table->populateTags($newTags);
					}
				}
			}
		}

		return $return;
	}

	public function featured($pks, $value = 0)
	{
		// Sanitize the ids.
		$pks = (array)$pks;
		ArrayHelper::toInteger($pks);

		if (empty($pks)) {
			$this->setError(JText::_('COM_DPCALENDAR_NO_ITEM_SELECTED'));

			return false;
		}

		try {
			$db = $this->getDbo();

			$db->setQuery('UPDATE #__dpcalendar_events SET featured = ' . (int)$value . ' WHERE id IN (' . implode(',', $pks) . ')');
			$db->execute();
		} catch (Exception $e) {
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	public function detach()
	{
		JEventDispatcher::getInstance()->detach($this->eventHandler);
	}

	public function getTickets($eventId)
	{
		if (empty($eventId) || DPCalendarHelper::isFree()) {
			return array();
		}
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$ticketsModel = JModelLegacy::getInstance('Tickets', 'DPCalendarModel');
		$ticketsModel->getState();
		$ticketsModel->setState('filter.event_id', $eventId);
		$ticketsModel->setState('list.limit', 10000);

		return $ticketsModel->getItems();
	}
}

class DPCalendarModelAdminEventHandler extends JEvent
{

	private $model = null;

	public function __construct(&$subject, $model)
	{
		parent::__construct($subject);

		$this->model = $model;
	}

	public function onContentBeforeSave($context, $event, $isNew)
	{
		if ($context != 'com_dpcalendar.event' && $context != 'com_dpcalendar.form') {
			return;
		}

		JPluginHelper::importPlugin('dpcalendar');
		if ($isNew) {
			return JDispatcher::getInstance()->trigger('onEventBeforeCreate', array(&$event));
		} else {
			return JDispatcher::getInstance()->trigger('onEventBeforeSave', array(&$event));
		}
	}

	public function onContentAfterSave($context, $event, $isNew, $data)
	{
		if ($context != 'com_dpcalendar.event' && $context != 'com_dpcalendar.form') {
			return;
		}

		$id = (int)$event->id;

		JFactory::getApplication()->setUserState('dpcalendar.event.id', $id);

		$oldEventIds = unserialize($this->model->getState('dpcalendar.event.oldEventIds'));
		$locationIds = $this->model->getState('dpcalendar.event.locationids');

		$db = JFactory::getDbo();
		$db->setQuery('select id from #__dpcalendar_events where id = ' . $id . ' or original_id = ' . $id);
		$rows   = $db->loadObjectList();
		$values = '';

		$fieldModel = JModelLegacy::getInstance('Field', 'FieldsModel', array('ignore_request' => true));

		// Loading the fields
		$fields = FieldsHelper::getFields($context, $event);

		$allIds = $oldEventIds;
		foreach ($rows as $tmp) {
			$allIds[(int)$tmp->id] = (int)$tmp->id;
			if ($locationIds) {
				foreach ($locationIds as $location) {
					$values .= '(' . (int)$tmp->id . ',' . (int)$location . '),';
				}
			}

			if (key_exists($tmp->id, $oldEventIds)) {
				unset($oldEventIds[$tmp->id]);
			}

			// Save the values on the child events
			if ($fieldModel && $fields && $tmp->id != $event->id) {
				foreach ($fields as $field) {
					$value = $field->value;
					if (isset($data['com_fields']) && key_exists($field->name, $data['com_fields'])) {
						$value = $data['com_fields'][$field->name];
					}
					$fieldModel->setFieldValue($field->id, $tmp->id, $value);
				}
			}
		}
		$values = trim($values, ',');

		if ($fieldModel && $fields) {
			// Clear the custom fields for deleted child events
			foreach ($oldEventIds as $childId) {
				foreach ($fields as $field) {
					$fieldModel->setFieldValue($field->id, $childId, null);
				}
			}
		}

		// Delete the location associations for the events which do not exist
		// anymore
		if (!$isNew) {
			$db->setQuery('delete from #__dpcalendar_events_location where event_id in (' . implode(',', $allIds) . ')');
			$db->query();
		}

		// Insert the new associations
		if (!empty($values)) {
			$db->setQuery('insert into #__dpcalendar_events_location (event_id, location_id) values ' . $values);
			$db->query();
		}

		$this->sendMail($isNew ? 'create' : 'edit', array($event));

		// Notify the ticket holders
		$data = $this->model->getState('dpcalendar.event.data');
		if (key_exists('notify_changes', $data) && $data['notify_changes']) {
			$tickets = $this->model->getTickets($event->id);
			foreach ($tickets as $ticket) {
				$subject = DPCalendarHelper::renderEvents(array($event), JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT'));

				$body = DPCalendarHelper::renderEvents(
					array($event),
					JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_EDIT_TICKETS_BODY'),
					null,
					array(
						'ticketLink' => DPCalendarHelperRoute::getTicketRoute($ticket, true),
						'sitename'   => JFactory::getConfig()->get('sitename'),
						'user'       => JFactory::getUser()->name
					)
				);

				$mailer = JFactory::getMailer();
				$mailer->setSubject($subject);
				$mailer->setBody($body);
				$mailer->IsHTML(true);
				$mailer->addRecipient($ticket->email);
				$mailer->Send();
			}
		}

		JPluginHelper::importPlugin('dpcalendar');
		if ($isNew) {
			return JDispatcher::getInstance()->trigger('onEventAfterCreate', array(&$event));
		} else {
			return JDispatcher::getInstance()->trigger('onEventAfterSave', array(&$event));
		}
	}

	public function onContentBeforeDelete($context, $event)
	{
		if ($context != 'com_dpcalendar.event' && $context != 'com_dpcalendar.form') {
			return;
		}

		$this->tmpDeleteEvent = clone $event;

		JPluginHelper::importPlugin('dpcalendar');

		return JDispatcher::getInstance()->trigger('onEventBeforeDelete', array($event));
	}

	public function onContentAfterDelete($context, $event)
	{
		if ($context != 'com_dpcalendar.event' && $context != 'com_dpcalendar.form') {
			return;
		}

		$this->sendMail('delete', array($this->tmpDeleteEvent && $this->tmpDeleteEvent->id == $event->id ? $this->tmpDeleteEvent : $event));
		$this->tmpDeleteEvent = null;

		JPluginHelper::importPlugin('dpcalendar');

		return JDispatcher::getInstance()->trigger('onEventAfterDelete', array($event));
	}

	public function onContentChangeState($context, $pks, $value)
	{
		if ($context != 'com_dpcalendar.event' && $context != 'com_dpcalendar.form') {
			return;
		}

		$events = array();

		$model = new DPCalendarModelAdminEvent();
		foreach ($pks as $pk) {
			$event    = $model->getItem($pk);
			$events[] = $event;

			JDispatcher::getInstance()->trigger('onEventAfterSave', array($event));
		}

		$this->sendMail('edit', $events);
	}

	private function sendMail($action, $events)
	{
		// We don't send notifications when an event is external
		foreach ($events as $event) {
			if (!is_numeric($event->catid)) {
				return;
			}
		}
		JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_dpcalendar');

		$subject = DPCalendarHelper::renderEvents($events, JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT_' . strtoupper($action)));

		$body = DPCalendarHelper::renderEvents(
			$events,
			JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_' . strtoupper($action) . '_BODY'),
			null,
			array(
				'sitename' => JFactory::getConfig()->get('sitename'),
				'user'     => JFactory::getUser()->name
			)
		);

		DPCalendarHelper::sendMail($subject, $body, 'notification_groups_' . $action);
	}
}
