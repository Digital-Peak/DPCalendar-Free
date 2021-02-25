<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\CMS\Language\Language;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

JLoader::import('joomla.application.component.modeladmin');
JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

class DPCalendarModelAdminEvent extends JModelAdmin
{
	public $typeAlias = 'com_dpcalendar.event';
	protected $text_prefix = 'COM_DPCALENDAR';
	protected $name = 'event';

	protected $batch_commands = [
		'assetgroup_id'     => 'batchAccess',
		'language_id'       => 'batchLanguage',
		'tag'               => 'batchTag',
		'color_id'          => 'batchColor',
		'access_content_id' => 'batchAccessContent',
		'capacity_id'       => 'batchCapacity'
	];

	protected function populateState()
	{
		parent::populateState();

		$this->setState($this->getName() . '.id', JFactory::getApplication()->input->getInt('e_id'));

		$app = JFactory::getApplication();
		$this->setState('params', method_exists($app, 'getParams') ? $app->getParams() : JComponentHelper::getParams('com_dpcalendar'));
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

	public function getTable($type = 'Event', $prefix = 'DPCalendarTable', $config = [])
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		// Load plugins for form manipulation
		JPluginHelper::importPlugin('dpcalendar');

		// Get the form.
		$form = $this->loadForm('com_dpcalendar.event', 'event', ['control' => 'jform', 'load_data' => $loadData]);
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
			$form->setFieldAttribute('payment_provider', 'disabled', 'true');
			$form->setFieldAttribute('booking_assign_user_groups', 'disabled', 'true');

			// Disable fields while saving.
			$form->setFieldAttribute('rrule', 'filter', 'unset');
			$form->setFieldAttribute('capacity', 'filter', 'unset');
			$form->setFieldAttribute('capacity_used', 'filter', 'unset');
			$form->setFieldAttribute('max_tickets', 'filter', 'unset');
			$form->setFieldAttribute('price', 'filter', 'unset');
			$form->setFieldAttribute('payment_provider', 'filter', 'unset');
			$form->setFieldAttribute('booking_assign_user_groups', 'filter', 'unset');
		}

		if (!DPCalendarHelper::isCaptchaNeeded()) {
			$form->removeField('captcha');
		}

		$params = $this->getParams();

		$form->setFieldAttribute('start_date', 'min_time', $params->get('event_form_min_time'));
		$form->setFieldAttribute('start_date', 'max_time', $params->get('event_form_max_time'));
		$form->setFieldAttribute('end_date', 'min_time', $params->get('event_form_min_time'));
		$form->setFieldAttribute('end_date', 'max_time', $params->get('event_form_max_time'));

		if ($colors = $params->get('event_form_color_options')) {
			$form->setFieldAttribute('color', 'control', 'simple');

			$colorValues = '';
			foreach ($colors as $color) {
				$colorValues .= $color->color_option . ',';
			}

			if ($colorValues) {
				$colorValues = substr($colorValues, 0, strlen($colorValues) - 1);
			}

			$form->setFieldAttribute('color', 'colors', $colorValues);
		}

		if ($eventId && $item && $item->original_id > 0) {
			$form->removeField('booking_series');
			if ($item->booking_series == 1) {
				foreach ($form->getFieldset('booking') as $field) {
					$form->removeField(DPCalendarHelper::getFieldName($field));
				}
			}
		}

		return $form;
	}

	protected function loadFormData()
	{
		$app = JFactory::getApplication();

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_dpcalendar.edit.event.data', []);

		if (empty($data)) {
			$data    = $this->getItem();
			$eventId = $this->getState('event.id');

			// Prime some default values.
			if ($eventId == '0') {
				$data->set('catid', $app->input->getCmd('catid', $app->getUserState('com_dpcalendar.events.filter.category_id')));

				$requestParams = $app->input->get('jform', [], 'array');
				if (!$data->get('catid') && key_exists('catid', $requestParams)) {
					$data->set('catid', $requestParams['catid']);
				}
			}
		}

		if (is_array($data)) {
			$data = new JObject($data);
		}

		if ($data instanceof stdClass) {
			$data = new JObject($data);
		}

		$data->setProperties($this->getDefaultValues($data));

		if ($data->get(('start_date_time'))) {
			try {
				// We got the data from a session, normalizing it
				$data->set(
					'start_date',
					DPCalendarHelper::getDateFromString($data->get('start_date'), $data->get('start_date_time'), $data->get('all_day'))->toSql()
				);
				$data->set(
					'end_date',
					DPCalendarHelper::getDateFromString($data->get('end_date'), $data->get('end_date_time'), $data->get('all_day'))->toSql()
				);
			} catch (Exception $e) {
				// Silently ignore the error
			}
		}

		// Scheduling end is set by the rrule
		$data->set('scheduling_end_date', null);

		if (!$data->get('id') && !isset($data->capacity)) {
			$data->set('capacity', 0);
		}

		if ((!isset($data->location_ids) || !$data->location_ids) && isset($data->locations) && $data->locations) {
			$data->location_ids = [];
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
			$newData = [];
			foreach ($obj->value as $index => $value) {
				$newData['price' . ($index + 1)] = [
					'value'       => $value,
					'label'       => $obj->label[$index],
					'description' => $obj->description[$index]
				];
			}
			$data->price = json_encode($newData);
		}
		if (isset($data->earlybird) && is_string($data->earlybird) && $data->earlybird) {
			$obj     = json_decode($data->earlybird);
			$newData = [];
			foreach ($obj->value as $index => $value) {
				$newData['price' . ($index + 1)] = [
					'value'       => $value,
					'type'        => $obj->type[$index],
					'date'        => $obj->date[$index],
					'label'       => $obj->label[$index],
					'description' => $obj->description[$index]
				];
			}
			$data->earlybird = json_encode($newData);
		}
		if (isset($data->user_discount) && is_string($data->user_discount) && $data->user_discount) {
			$obj     = json_decode($data->user_discount);
			$newData = [];
			foreach ($obj->value as $index => $value) {
				$newData['price' . ($index + 1)] = [
					'value'           => $value,
					'type'            => $obj->type[$index],
					'date'            => $obj->date[$index],
					'label'           => $obj->label[$index],
					'description'     => $obj->description[$index],
					'discount_groups' => $obj->discount_groups[$index]
				];
			}
			$data->user_discount = json_encode($newData);
		}

		if (!empty($data->payment_provider) && is_string($data->payment_provider)) {
			$data->payment_provider = explode(',', $data->payment_provider);
		}

		if (!empty($data->booking_assign_user_groups) && is_string($data->booking_assign_user_groups)) {
			$data->booking_assign_user_groups = explode(',', $data->booking_assign_user_groups);
		}

		return $data;
	}

	public function getItem($pk = null)
	{
		$pk   = (!empty($pk)) ? $pk : $this->getState($this->getName() . '.id');
		$item = null;
		if (!empty($pk) && !is_numeric($pk)) {
			JPluginHelper::importPlugin('dpcalendar');
			$tmp = JFactory::getApplication()->triggerEvent('onEventFetch', [$pk]);
			if (!empty($tmp)) {
				$item = $tmp[0];
			}
		} else {
			$item = parent::getItem($pk);
			if ($item != null) {
				// Convert the params field to an array.
				$registry = new JRegistry();
				if (!empty($item->metadata)) {
					$registry->loadString($item->metadata);
				}
				$item->metadata = $registry->toArray();

				// Convert the images field to an array.
				$registry = new JRegistry();
				if (!empty($item->images)) {
					$registry->loadString($item->images);
				}
				$item->images = $registry->toArray();

				if ($item->id > 0) {
					$this->_db->setQuery('select location_id from #__dpcalendar_events_location where event_id = ' . (int)$item->id);
					$locations = $this->_db->loadObjectList();
					if (!empty($locations)) {
						$item->location_ids = [];
						foreach ($locations as $location) {
							$item->location_ids[] = $location->location_id;
						}
					}

					$item->tags = new JHelperTags();
					$item->tags->getTagIds($item->id, 'com_dpcalendar.event');
				}
				$item->tickets = $this->getTickets($item->id);

				if ($item->rooms && is_string($item->rooms)) {
					$item->rooms = explode(',', $item->rooms);
				}
			}
		}

		return $item;
	}

	public function save($data)
	{
		$locationIds = [];
		if (isset($data['location_ids'])) {
			$locationIds = array_unique($data['location_ids']);
			unset($data['location_ids']);
		}

		$oldEventIds = [];
		if (isset($data['id']) && $data['id']) {
			$this->getDbo()->setQuery('select id from #__dpcalendar_events where original_id = ' . (int)$data['id']);
			$rows = $this->getDbo()->loadObjectList();
			foreach ($rows as $oldEvent) {
				$oldEventIds[$oldEvent->id] = $oldEvent->id;
			}
		}

		if ($data['all_day'] == 1 && !isset($data['date_range_correct'])) {
			$data['start_date'] = DPCalendarHelper::getDate($data['start_date'], $data['all_day'])->toSql(true);
			$data['end_date']   = DPCalendarHelper::getDate($data['end_date'], $data['all_day'])->toSql(true);
		}

		if (isset($data['images']) && is_array($data['images'])) {
			$registry = new Registry();
			$registry->loadArray($data['images']);
			$data['images'] = (string)$registry;
		}

		$app = JFactory::getApplication();

		// Migrate subform data to old repeatable format
		if (isset($data['price']) && is_array($data['price'])) {
			$obj              = new stdClass();
			$obj->value       = [];
			$obj->label       = [];
			$obj->description = [];
			foreach ($data['price'] as $key => $p) {
				$obj->value[]       = $p['value'];
				$obj->label[]       = $p['label'];
				$obj->description[] = $p['description'];
			}
			$data['price'] = json_encode($obj);
		}
		if (isset($data['earlybird']) && is_array($data['earlybird'])) {
			$obj              = new stdClass();
			$obj->value       = [];
			$obj->type        = [];
			$obj->date        = [];
			$obj->label       = [];
			$obj->description = [];
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
			$obj                  = new stdClass();
			$obj->value           = [];
			$obj->type            = [];
			$obj->date            = [];
			$obj->label           = [];
			$obj->description     = [];
			$obj->discount_groups = [];
			foreach ($data['user_discount'] as $key => $p) {
				$obj->value[]           = $p['value'];
				$obj->type[]            = $p['type'];
				$obj->date[]            = $p['date'];
				$obj->label[]           = $p['label'];
				$obj->description[]     = $p['description'];
				$obj->discount_groups[] = $p['discount_groups'];
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

		if (isset($data['booking_options']) && is_array($data['booking_options'])) {
			$data['booking_options'] = $data['booking_options'] ? json_encode($data['booking_options']) : '';
		}

		if (isset($data['schedule']) && is_array($data['schedule'])) {
			$data['schedule'] = $data['schedule'] ? json_encode($data['schedule']) : '';
		}

		if (isset($data['payment_provider']) && is_array($data['payment_provider'])) {
			$data['payment_provider'] = $data['payment_provider'] ? implode(',', $data['payment_provider']) : '';
		}

		if (isset($data['booking_assign_user_groups']) && is_array($data['booking_assign_user_groups'])) {
			$data['booking_assign_user_groups'] = $data['booking_assign_user_groups'] ? implode(',', $data['booking_assign_user_groups']) : '';
		}

		// Only apply the default values on create
		if (empty($data['id'])) {
			$data = array_merge($data, $this->getDefaultValues(new JObject($data)));
		}

		$success = parent::save($data);

		if (!$success) {
			return $success;
		}

		$id    = $this->getState($this->getName() . '.id');
		$event = $this->getItem($id);
		$app->setUserState('dpcalendar.event.id', $id);

		$this->getDbo()->setQuery('select id from #__dpcalendar_events where id = ' . $id . ' or original_id = ' . $id);
		$rows   = $this->getDbo()->loadObjectList();
		$values = '';

		$fieldModel = JModelLegacy::getInstance('Field', 'FieldsModel', ['ignore_request' => true]);

		// Loading the fields
		$fields = FieldsHelper::getFields('com_dpcalendar.event', $event);

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

		// Delete the location associations for the events which do not exist anymore
		if (!$this->getState($this->getName() . '.new')) {
			$this->getDbo()->setQuery('delete from #__dpcalendar_events_location where event_id in (' . implode(',', $allIds) . ')');
			$this->getDbo()->execute();
		}

		// Insert the new associations
		if (!empty($values)) {
			$this->getDbo()->setQuery('insert into #__dpcalendar_events_location (event_id, location_id) values ' . $values);
			$this->getDbo()->execute();
		}

		if (!empty($event->location_ids)) {
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
			$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
			$model->setState('list.limit', 100);
			$model->setState('filter.search', 'ids:' . implode(',', $event->location_ids));
			$event->locations = $model->getItems();
		}

		$this->sendMail($this->getState($this->getName() . '.new') ? 'create' : 'edit', [$event]);

		// Notify the ticket holders
		if (key_exists('notify_changes', $data) && $data['notify_changes']) {
			$langs   = [$app->getLanguage()->getTag() => $app->getLanguage()];
			$tickets = $this->getTickets($event->id);
			foreach ($tickets as $ticket) {
				$language = $app->getLanguage()->getTag();
				if ($ticket->user_id) {
					$language = JFactory::getUser($ticket->user_id)->getParam('language') ?: $language;

					if (!array_key_exists($language, $langs)) {
						$l = Language::getInstance($language, $app->get('debug_lang'));
						$l->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
						$langs[$l->getTag()] = $l;
					}
				}

				$subject = DPCalendarHelper::renderEvents([$event], $langs[$language]->_('COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT_EDIT'));

				$body = DPCalendarHelper::renderEvents(
					[$event],
					$langs[$language]->_('COM_DPCALENDAR_NOTIFICATION_EVENT_EDIT_TICKETS_BODY'),
					null,
					[
						'ticketLink' => DPCalendarHelperRoute::getTicketRoute($ticket, true),
						'sitename'   => JFactory::getApplication()->get('sitename'),
						'user'       => JFactory::getUser()->name
					]
				);

				$mailer = JFactory::getMailer();
				$mailer->setSubject($subject);
				$mailer->setBody($body);
				$mailer->IsHTML(true);
				$mailer->addRecipient($ticket->email);
				$mailer->Send();
			}
		}

		return $success;
	}

	protected function prepareTable($table)
	{
		if (!isset($table->state) && $this->canEditState($table)) {
			$table->state = 1;
		}
	}

	protected function batchTag($value, $pks, $contexts)
	{
		$return = parent::batchTag($value, $pks, $contexts);
		if (!$return) {
			return $return;
		}

		$user  = JFactory::getUser();
		$table = $this->getTable();
		foreach ($pks as $pk) {
			if ($user->authorise('core.edit', $contexts[$pk])) {
				$table->reset();
				$table->load($pk);

				// If we are a recurring event, then save the tags on the children too
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

		return $return;
	}

	protected function batchColor($value, $pks, $contexts)
	{
		return $this->performBatch('color', $value, $pks, $contexts);
	}

	protected function batchAccessContent($value, $pks, $contexts)
	{
		return $this->performBatch('access_content', $value, $pks, $contexts);
	}

	protected function batchCapacity($value, $pks, $contexts)
	{
		return $this->performBatch('capacity', $value, $pks, $contexts);
	}

	private function performBatch(string $property, $value, $pks, $contexts)
	{
		$this->initBatch();

		foreach ($pks as $pk) {
			if ($this->user->authorise('core.edit', $contexts[$pk])) {
				$this->table->reset();
				$this->table->load($pk);
				$this->table->$property = $value;

				if (!empty($this->type)) {
					$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
				}

				if (!$this->table->store()) {
					$this->setError($this->table->getError());

					return false;
				}
			} else {
				$this->setError(\JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));

				return false;
			}
		}

		// Clean the cache
		$this->cleanCache();

		return true;
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
			$this->getDbo()->setQuery('UPDATE #__dpcalendar_events SET featured = ' . (int)$value . ' WHERE id IN (' . implode(',', $pks) . ')');
			$this->getDbo()->execute();
		} catch (Exception $e) {
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	public function publish(&$pks, $value = 1)
	{
		$success = parent::publish($pks, $value);

		if ($success) {
			$events = [];
			foreach ($pks as $pk) {
				$event    = $this->getItem($pk);
				$events[] = $event;
			}

			$this->sendMail('edit', $events);
		}

		return $success;
	}


	public function delete(&$pks)
	{
		$pks    = (array)$pks;
		$events = [];
		foreach ($pks as $pk) {
			$event    = $this->getItem($pk);
			$events[] = $event;
		}

		$success = parent::delete($pks);

		if ($success) {
			$this->sendMail('delete', $events);
		}

		return $success;
	}

	public function getTickets($eventId)
	{
		if (empty($eventId) || DPCalendarHelper::isFree()) {
			return [];
		}
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$ticketsModel = JModelLegacy::getInstance('Tickets', 'DPCalendarModel');
		$ticketsModel->getState();
		$ticketsModel->setState('filter.event_id', $eventId);
		$ticketsModel->setState('list.limit', 10000);

		return $ticketsModel->getItems();
	}

	private function getDefaultValues(JObject $item)
	{
		$params = $this->getParams();
		$data   = [];

		// Set the default values from the params
		if (!$item->get('catid')) {
			$data['catid'] = $params->get('event_form_calid');
		}
		if ($params->get('event_form_show_end_time') != '' && $item->get('show_end_time') === null) {
			$data['show_end_time'] = $params->get('event_form_show_end_time');
		}
		if ($params->get('event_form_all_day') != '' && $item->get('all_day') === null) {
			$data['all_day'] = $params->get('event_form_all_day');
		}
		if (!$item->get('color')) {
			$data['color'] = $params->get('event_form_color');
		}
		if (!$item->get('url')) {
			$data['url'] = $params->get('event_form_url');
		}
		if (!$item->get('description')) {
			$data['description'] = $params->get('event_form_description');
		}
		if (!$item->get('capacity') && $params->get('event_form_capacity') > 0) {
			$data['capacity'] = $params->get('event_form_capacity');
		}
		if (!$item->get('payment_provider')) {
			$data['payment_provider'] = $params->get('event_form_payment_provider');
		}
		if (!$item->get('terms')) {
			$data['terms'] = $params->get('event_form_terms');
		}
		if (!$item->get('access')) {
			$data['access'] = $params->get('event_form_access');
		}
		if (!$item->get('access_content')) {
			$data['access_content'] = $params->get('event_form_access_content');
		}
		if (!$item->get('featured')) {
			$data['featured'] = $params->get('event_form_featured');
		}
		if (!$item->get('location_ids')) {
			$data['location_ids'] = $params->get('event_form_location_ids', []);
		}
		if (!$item->get('language')) {
			$data['language'] = $params->get('event_form_language');
		}
		if (!$item->get('metakey')) {
			$data['metakey'] = $params->get('menu-meta_keywords');
		}
		if (!$item->get('metadesc')) {
			$data['metadesc'] = $params->get('menu-meta_description');
		}

		return $data;
	}

	protected function getParams()
	{
		$params = $this->getState('params');

		if (!$params) {
			if (JFactory::getApplication()->isClient('site')) {
				$params = JFactory::getApplication()->getParams();
			} else {
				$params = JComponentHelper::getParams('com_dpcalendar');
			}
		}

		return $params;
	}

	private function sendMail($action, $events)
	{
		// The current user
		$user = JFactory::getUser();

		// The event authors
		$authors = [];

		// The event calendars
		$calendarGroups = [];

		// We don't send notifications when an event is external
		foreach ($events as $event) {
			if (!is_numeric($event->catid)) {
				return;
			}

			if ($calendar = \DPCalendarHelper::getCalendar($event->catid)) {
				$calendarGroups = array_merge($calendarGroups, $calendar->params->get('notification_groups_' . $action, []));
			}

			if ($user->id != $event->created_by) {
				$authors[] = $event->created_by;
			}
		}

		// Load the language
		JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		// Create the subject
		$subject = DPCalendarHelper::renderEvents($events, JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT_' . strtoupper($action)));

		// Create the body
		$body = DPCalendarHelper::renderEvents(
			$events,
			JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_' . strtoupper($action) . '_BODY'),
			null,
			[
				'sitename' => JFactory::getApplication()->get('sitename'),
				'user'     => $user->name
			]
		);

		// Send the notification to the groups
		DPCalendarHelper::sendMail($subject, $body, 'notification_groups_' . $action, $calendarGroups);

		// Check if authors should get a mail
		if (!$authors || !DPCalendarHelper::getComponentParameter('notification_author')) {
			return;
		}

		$authors = array_unique($authors);

		$extraVars = [
			'sitename' => JFactory::getApplication()->get('sitename'),
			'user'     => $user->name
		];

		// Create the subject
		$subject = DPCalendarHelper::renderEvents(
			$events,
			JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_SUBJECT_' . strtoupper($action)),
			null,
			$extraVars
		);

		// Create the body
		$body = DPCalendarHelper::renderEvents(
			$events,
			JText::_('COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_' . strtoupper($action) . '_BODY'),
			null,
			$extraVars
		);

		// Loop over the authors to send the notification
		foreach ($authors as $author) {
			$u = \JUser::getTable();

			// Load the user
			if (!$u->load($author)) {
				continue;
			}

			// Send the mail
			$mailer = \JFactory::getMailer();
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->IsHTML(true);
			$mailer->addRecipient($u->email);
			$mailer->Send();
		}
	}
}
