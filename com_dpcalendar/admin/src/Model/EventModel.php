<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerFactoryAwareInterface;
use Joomla\CMS\Mail\MailerFactoryAwareTrait;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class EventModel extends AdminModel implements MailerFactoryAwareInterface, UserFactoryAwareInterface
{
	use MailerFactoryAwareTrait;
	use UserFactoryAwareTrait;
	use VersionableModelTrait;

	public $typeAlias      = 'com_dpcalendar.event';
	protected $text_prefix = 'COM_DPCALENDAR';
	protected $name        = 'event';

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

		$this->setState($this->getName() . '.id', Factory::getApplication()->getInput()->getInt('e_id', 0));

		$app = Factory::getApplication();
		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));
	}

	protected function canDelete($record)
	{
		if (!empty($record->catid) && !empty($record->state) && !empty($record->created_by)) {
			if ($record->state != -2 && !Factory::getApplication()->isClient('api')) {
				return false;
			}

			$calendar = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($record->catid);
			if ($calendar instanceof CalendarInterface && ($calendar->canDelete() || ($calendar->canEditOwn() && $record->created_by == $this->getCurrentUser()->id))) {
				return true;
			}
		}

		return parent::canDelete($record);
	}

	protected function canEditState($record)
	{
		$user = $this->getCurrentUser();

		if (!empty($record->catid)) {
			return $user->authorise('core.edit.state', 'com_dpcalendar.category.' . (int)$record->catid);
		}

		return parent::canEditState($record);
	}

	public function getTable($type = 'Event', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		// Load plugins for form manipulation
		PluginHelper::importPlugin('dpcalendar');

		// Get the form.
		$form    = $this->loadForm('com_dpcalendar.event', 'event', ['control' => 'jform', 'load_data' => $loadData]);
		$eventId = $this->getState('event.id', 0);

		// Determine correct permissions to check.
		if ($eventId) {
			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit');
		} else {
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.create');
		}

		$item = $this->getItem($data['id'] ?? null);

		// Item can be empty on save as copy but catid is needed for calendar permissions
		// for edit state when global ones are disabled
		if ($item instanceof \stdClass && empty($item->catid) && !empty($data['catid'])) {
			$item->catid = $data['catid'];
		}

		// Modify the form based on access controls.
		if ($item instanceof \stdClass && !$this->canEditState($item)) {
			// Disable fields for display.
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');

			// Disable fields while saving
			$form->setFieldAttribute('state', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
		}

		if ($item instanceof \stdClass) {
			$form->setFieldAttribute('start_date', 'all_day', $item->all_day);
			$form->setFieldAttribute('end_date', 'all_day', $item->all_day);
		}

		if (DPCalendarHelper::isFree()) {
			foreach (DPCalendarHelper::$DISABLED_FREE_FIELDS as $field) {
				// Disable fields for display
				$form->setFieldAttribute($field, 'disabled', 'true');

				// Disable fields while saving
				$form->setFieldAttribute($field, 'filter', 'unset');
			}
			$form->setFieldAttribute('capacity', 'disabled', 'true');
		}

		if (!DPCalendarHelper::isCaptchaNeeded()) {
			$form->removeField('captcha');
		}

		$params = $this->getParams();

		$form->setFieldAttribute('catid', 'calendar_filter', implode(',', $params->get('event_form_calendars', [])));
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

			if ($colorValues !== '' && $colorValues !== '0') {
				$colorValues = substr($colorValues, 0, \strlen($colorValues) - 1);
			}

			$form->setFieldAttribute('color', 'colors', $colorValues);
		}

		// Cleanup the series fields on an instance
		if ($eventId && $item && $item->original_id > 0) {
			$form->removeField('booking_series');
			$form->removeField('events_discount');

			// Only the whole series can be booked so remove the booking options from the instance
			if ($item->booking_series == 1) {
				foreach ($form->getFieldset('booking') as $field) {
					$form->removeField(DPCalendarHelper::getFieldName($field));
				}
			}
		}

		// Dirty hack as it breaks on none english languages
		if (Factory::getApplication()->input->get('task') === 'reloadfromevent') {
			$form->setFieldAttribute('created', 'translateformat', 'false');
			$form->setFieldAttribute('modified', 'translateformat', 'false');
		}

		return $form;
	}

	protected function loadFormData()
	{
		$app = Factory::getApplication();

		// Check the session for previously entered form data
		// Ignore the data when ignore_request variable is set
		$data = $app->getInput()->getInt('ignore_request', 0) === 1 || !$app instanceof CMSWebApplicationInterface ?
			 [] : $app->getUserState('com_dpcalendar.edit.event.data', []);

		if (empty($data)) {
			$data    = $this->getItem() ?: new \stdClass();
			$eventId = $this->getState('event.id');

			// Prime some default values.
			if ($eventId == '0' && $app instanceof CMSWebApplicationInterface) {
				$catId       = $app->getUserState('com_dpcalendar.events.filter.calendars');
				$data->catid = $app->getInput()->getCmd('catid', \is_array($catId) ? reset($catId) : $catId);

				$requestParams = $app->getInput()->get('jform', [], 'array');
				if (!$data->catid && \array_key_exists('catid', $requestParams)) {
					$data->catid = $requestParams['catid'];
				}
			}
		}

		if (\is_array($data)) {
			$data = (object)$data;
		}

		foreach ($this->getDefaultValues($data) as $key => $value) {
			$data->{$key} = $value;
		}

		if (!empty($data->start_date) && !empty($data->start_date_time)
			&& !empty($data->end_date) && !empty($data->end_date_time) && isset($data->all_day)) {
			try {
				// We got the data from a session, normalizing it
				$data->start_date = DPCalendarHelper::getDateFromString(
					$data->start_date,
					$data->start_date_time,
					$data->all_day
				)->toSql();
				$data->end_date = DPCalendarHelper::getDateFromString(
					$data->end_date,
					$data->end_date_time,
					$data->all_day
				)->toSql();
			} catch (\Exception) {
				// Silently ignore the error
			}
		}

		// Scheduling end is set by the rrule
		$data->scheduling_end_date = null;

		if (!$data->id && !isset($data->capacity)) {
			$data->capacity = 0;
		}

		if ((!isset($data->location_ids) || !$data->location_ids) && isset($data->locations) && $data->locations && $data instanceof \stdClass) {
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

		if (!empty($data->payment_provider) && \is_string($data->payment_provider)) {
			$data->payment_provider = explode(',', $data->payment_provider);
		}

		if (!empty($data->booking_assign_user_groups) && \is_string($data->booking_assign_user_groups)) {
			$data->booking_assign_user_groups = explode(',', $data->booking_assign_user_groups);
		}

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	public function getItem($pk = null)
	{
		$pk   = (empty($pk)) ? $this->getState($this->getName() . '.id') : $pk;
		$item = null;
		if (!empty($pk) && !is_numeric($pk)) {
			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent('onEventFetch', [$pk]);
			if (!empty($tmp)) {
				$item = $tmp[0];
			}
		} else {
			$item = parent::getItem($pk);
			if ($item != null) {
				// Convert the params field to an array.
				$registry = new Registry();
				if (!empty($item->metadata)) {
					$registry->loadString($item->metadata);
				}
				$item->metadata = $registry->toArray();

				// Convert the images field to an array.
				$registry = new Registry();
				if (!empty($item->images)) {
					$registry->loadString($item->images);
				}
				$item->images = $registry->toArray();

				if ($item->id > 0) {
					$this->getDatabase()->setQuery('select location_id from #__dpcalendar_events_location where event_id = ' . (int)$item->id);
					$locations = $this->getDatabase()->loadObjectList();
					if (!empty($locations)) {
						$item->location_ids = [];
						foreach ($locations as $location) {
							$item->location_ids[] = $location->location_id;
						}
					}

					$this->getDatabase()->setQuery('select user_id from #__dpcalendar_events_hosts where event_id = ' . (int)$item->id);
					$hosts = $this->getDatabase()->loadObjectList();
					if (!empty($hosts)) {
						$item->host_ids = [];
						foreach ($hosts as $host) {
							$item->host_ids[] = $host->user_id;
						}
					}

					$item->tags = new TagsHelper();
					$item->tags->getTagIds($item->id, 'com_dpcalendar.event');
				}
				$item->tickets = $this->getTickets($item->id ?? '');

				if ($item->rooms && \is_string($item->rooms)) {
					$item->rooms = explode(',', $item->rooms);
				}

				if ($item->prices && \is_string($item->prices)) {
					$item->prices = json_decode($item->prices);
				}
			}
		}

		return $item;
	}

	public function save($data)
	{
		$locationIds = [];
		if (isset($data['location_ids'])) {
			$locationIds = array_unique($data['location_ids'] ?: []);
			unset($data['location_ids']);
		}
		$hostIds = [];
		if (isset($data['host_ids'])) {
			$hostIds = array_unique($data['host_ids'] ?: []);
			unset($data['host_ids']);
		}

		$oldEventIds = [];
		if (isset($data['id']) && $data['id']) {
			$this->getDatabase()->setQuery('select id from #__dpcalendar_events where original_id = ' . (int)$data['id']);
			$rows = $this->getDatabase()->loadObjectList();
			foreach ($rows as $oldEvent) {
				$oldEventIds[$oldEvent->id] = $oldEvent->id;
			}
		}

		if (!empty($data['all_day']) && $data['all_day'] == 1 && !isset($data['date_range_correct'])) {
			$data['start_date'] = DPCalendarHelper::getDate($data['start_date'], $data['all_day'])->toSql(true);
			$data['end_date']   = DPCalendarHelper::getDate($data['end_date'], $data['all_day'])->toSql(true);
		}

		if (isset($data['exdates']) && \is_array($data['exdates'])) {
			$data['exdates'] = $data['exdates'] !== [] ? json_encode($data['exdates']) : '';
		}

		if (isset($data['images']) && \is_array($data['images'])) {
			$registry = new Registry();
			$registry->loadArray($data['images']);
			$data['images'] = (string)$registry;
		}

		if (isset($data['earlybird_discount']) && \is_array($data['earlybird_discount'])) {
			$data['earlybird_discount'] = json_encode($data['earlybird_discount']);
		}

		if (isset($data['user_discount']) && \is_array($data['user_discount'])) {
			$data['user_discount'] = json_encode($data['user_discount']);
		}

		if (isset($data['events_discount']) && \is_array($data['events_discount'])) {
			$data['events_discount'] = json_encode($data['events_discount']);
		}

		if (isset($data['tickets_discount']) && \is_array($data['tickets_discount'])) {
			$data['tickets_discount'] = json_encode($data['tickets_discount']);
		}

		if (isset($data['prices']) && \is_array($data['prices'])) {
			$data['prices'] = json_encode($data['prices']);
		}

		if (!empty($data['prices']) && \is_string($data['prices'])) {
			$data['prices'] = array_filter((array)json_decode($data['prices']), fn ($p): bool => $p->value || $p->label || $p->description) !== [] ? $data['prices'] : '';
		}

		if (isset($data['booking_options']) && \is_array($data['booking_options'])) {
			$data['booking_options'] = $data['booking_options'] !== [] ? json_encode($data['booking_options']) : '';
		}

		if (isset($data['schedule']) && \is_array($data['schedule'])) {
			$data['schedule'] = $data['schedule'] !== [] ? json_encode($data['schedule']) : '';
		}

		if (isset($data['payment_provider']) && \is_array($data['payment_provider'])) {
			$data['payment_provider'] = $data['payment_provider'] !== [] ? implode(',', $data['payment_provider']) : '';
		}

		if (isset($data['booking_assign_user_groups']) && \is_array($data['booking_assign_user_groups'])) {
			$data['booking_assign_user_groups'] = $data['booking_assign_user_groups'] !== [] ? implode(',', $data['booking_assign_user_groups']) : '';
		}

		// Only apply the default values on create
		if (empty($data['id'])) {
			$data = array_merge($data, $this->getDefaultValues((object)$data));
		}

		if (DPCalendarHelper::isFree()) {
			$data['capacity'] = 0;
		}

		$success = parent::save($data);
		if (!$success) {
			return $success;
		}

		$id    = $this->getState($this->getName() . '.id');
		$event = $this->getItem($id);
		if ($event === false) {
			throw new \Exception('Event is not created');
		}

		$app = Factory::getApplication();
		if ($app instanceof CMSWebApplicationInterface) {
			$app->setUserState('dpcalendar.event.id', $id);
		}

		$this->getDatabase()->setQuery('select id, modified from #__dpcalendar_events where id = ' . $id . ' or original_id = ' . $id);
		$rows = $this->getDatabase()->loadObjectList();

		$fieldModel = $this->bootComponent('fields')->getMVCFactory()->createModel('Field', 'Administrator', ['ignore_request' => true]);

		// Loading the fields
		$fields = FieldsHelper::getFields('com_dpcalendar.event', $event);

		$locationValues = '';
		$hostsValues    = '';
		$allIds         = $oldEventIds;
		foreach ($rows as $tmp) {
			$allIds[(int)$tmp->id] = (int)$tmp->id;
			foreach ($locationIds as $location) {
				$locationValues .= '(' . (int)$tmp->id . ',' . (int)$location . '),';
			}
			foreach ($hostIds as $host) {
				$hostsValues .= '(' . (int)$tmp->id . ',' . (int)$host . '),';
			}

			if (\array_key_exists($tmp->id, $oldEventIds)) {
				unset($oldEventIds[$tmp->id]);
			}

			// Check if the event is the main event
			if (!$fields || $tmp->id == $event->id) {
				continue;
			}

			// When modified events should not be updated and the modified date is different than the original event, ignore it
			if (\array_key_exists('update_modified', $data)
				&& (int)$data['update_modified'] === 0
				&& $tmp->modified
				&& $tmp->modified !== $event->modified) {
				continue;
			}

			// Save the values on the child events
			foreach ($fields as $field) {
				$value = $field->value;
				if (isset($data['com_fields']) && \array_key_exists($field->name, $data['com_fields'])) {
					$value = $data['com_fields'][$field->name];
				}

				// The media field needs the data encoded
				if ($field->type === 'media' && \is_array($value)) {
					$value = json_encode($value);
				}

				if (\is_array($value) ? $value === [] : (string)$value === '') {
					$value = null;
				}

				$fieldModel->setFieldValue($field->id, $tmp->id, $value);
			}
		}

		$locationValues = trim($locationValues, ',');
		$hostsValues    = trim($hostsValues, ',');

		if ($fields) {
			// Clear the custom fields for deleted child events
			foreach ($oldEventIds as $childId) {
				foreach ($fields as $field) {
					$fieldModel->setFieldValue($field->id, $childId, '');
				}
			}
		}

		// Delete the location associations for the events which do not exist anymore
		if (!$this->getState($this->getName() . '.new')) {
			$this->getDatabase()->setQuery('delete from #__dpcalendar_events_location where event_id in (' . implode(',', $allIds) . ')');
			$this->getDatabase()->execute();
		}

		// Insert the new associations
		if ($locationValues !== '' && $locationValues !== '0') {
			$this->getDatabase()->setQuery('insert into #__dpcalendar_events_location (event_id, location_id) values ' . $locationValues);
			$this->getDatabase()->execute();
		}

		// Delete the hosts associations for the events which do not exist anymore
		if (!$this->getState($this->getName() . '.new')) {
			$this->getDatabase()->setQuery('delete from #__dpcalendar_events_hosts where event_id in (' . implode(',', $allIds) . ')');
			$this->getDatabase()->execute();
		}

		// Insert the new associations
		if ($hostsValues !== '' && $hostsValues !== '0') {
			$this->getDatabase()->setQuery('insert into #__dpcalendar_events_hosts (event_id, user_id) values ' . $hostsValues);
			$this->getDatabase()->execute();
		}

		if (!empty($event->location_ids) || $locationIds) {
			$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
			$model->setState('list.limit', 100);
			$model->setState('filter.search', 'ids:' . implode(',', $event->location_ids ?? $locationIds));
			$event->locations = $model->getItems();
		}

		$event->jcfields = $fields;
		$this->sendMail($this->getState($this->getName() . '.new') ? 'create' : 'edit', [$event]);

		// Notify the ticket holders
		if (\array_key_exists('notify_changes', $data) && $data['notify_changes']) {
			$langs   = [$app->getLanguage()->getTag() => $app->getLanguage()];
			$tickets = $this->getTickets($event->id);
			foreach ($tickets as $ticket) {
				$language = $app->getLanguage()->getTag();
				if ($ticket->user_id) {
					$language = $this->getUserFactory()->loadUserById($ticket->user_id)->getParam('language') ?: $language;

					if (!\array_key_exists($language, $langs)) {
						// @phpstan-ignore-next-line
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
						'ticketLink' => RouteHelper::getTicketRoute($ticket, true),
						'sitename'   => Factory::getApplication()->get('sitename'),
						'user'       => $this->getCurrentUser()->name
					]
				);

				$mailer = $this->getMailerFactory()->createMailer();
				$mailer->setSubject($subject);
				$mailer->setBody($body);
				$mailer->addRecipient($ticket->email);
				if ($mailer instanceof Mail) {
					$mailer->IsHTML(true);
				}
				$app->triggerEvent('onDPCalendarBeforeSendMail', ['com_dpcalendar.event.save.notify.tickets', $mailer, $ticket]);
				$mailer->Send();
				$app->triggerEvent('onDPCalendarAfterSendMail', ['com_dpcalendar.event.save.notify.tickets', $mailer, $ticket]);
			}
		}

		return $success;
	}

	protected function prepareTable($table)
	{
		if (empty($table->state) && $table->state != '0' && $this->canEditState($table)) {
			$table->state = 1;
		}

		$data = Factory::getApplication()->getInput()->post->get('jform', [], 'array');
		if (\array_key_exists('update_modified', $data)) {
			$table->_update_modified = $data['update_modified'];
		}
	}

	protected function batchTag($value, $pks, $contexts)
	{
		$return = parent::batchTag($value, $pks, $contexts);
		if (!$return) {
			return $return;
		}

		$user  = $this->getCurrentUser();
		$table = $this->getTable();
		foreach ($pks as $pk) {
			if ($user->authorise('core.edit', $contexts[$pk])) {
				$table->reset();
				$table->load($pk);

				// If we are a recurring event, then save the tags on the children too
				if ($table->original_id == '-1') {
					$newTags = new TagsHelper();
					$newTags = $newTags->getItemTags('com_dpcalendar.event', (int)$table->id);
					$newTags = array_map(static fn ($t) => $t->id, $newTags);

					$table->populateTags($newTags);
				}
			}
		}

		return $return;
	}

	protected function batchColor(string $value, array $pks, array $contexts): bool
	{
		return $this->performBatch('color', $value, $pks, $contexts);
	}

	protected function batchAccessContent(string $value, array $pks, array $contexts): bool
	{
		return $this->performBatch('access_content', $value, $pks, $contexts);
	}

	protected function batchCapacity(string $value, array $pks, array $contexts): bool
	{
		return $this->performBatch('capacity', $value, $pks, $contexts);
	}

	private function performBatch(string $property, string $value, array $pks, array $contexts): bool
	{
		$this->initBatch();

		foreach ($pks as $pk) {
			if ($this->getCurrentUser()->authorise('core.edit', $contexts[$pk])) {
				$this->table->reset();
				$this->table->load($pk);
				$this->table->$property = $value;

				if (!$this->table->store()) {
					throw new \Exception($this->table->getError());
				}
			} else {
				throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
			}
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	public function featured(array $pks, int $value = 0): bool
	{
		// Sanitize the ids.
		$pks = ArrayHelper::toInteger($pks);

		if ($pks === []) {
			throw new \Exception(Text::_('COM_DPCALENDAR_NO_ITEM_SELECTED'));
		}

		$this->getDatabase()->setQuery('UPDATE #__dpcalendar_events SET featured = ' . $value . ' WHERE id IN (' . implode(',', $pks) . ')');
		$this->getDatabase()->execute();

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

	public function getTickets(string $eventId): array
	{
		if ($eventId === '' || $eventId === '0' || DPCalendarHelper::isFree()) {
			return [];
		}

		$ticketsModel = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Tickets', 'Administrator');
		$ticketsModel->getState();
		$ticketsModel->setState('filter.event_id', $eventId);
		$ticketsModel->setState('list.limit', 10000);

		return $ticketsModel->getItems();
	}

	private function getDefaultValues(\stdClass $item): array
	{
		$params = $this->getParams();
		$data   = [];

		// Set the default values from the params
		if (empty($item->catid)) {
			$data['catid'] = $params->get('event_form_calid');
		}
		if ($params->get('event_form_show_end_time') != '' && (!isset($item->show_end_time) || $item->show_end_time === null)) {
			$data['show_end_time'] = $params->get('event_form_show_end_time');
		}
		if ($params->get('event_form_all_day') != '' && (!isset($item->all_day) || $item->all_day === null)) {
			$data['all_day'] = $params->get('event_form_all_day');
		}
		if (!isset($item->color) || $item->color === null) {
			$data['color'] = $params->get('event_form_color');
		}
		if (!isset($item->url) || $item->url === null) {
			$data['url'] = $params->get('event_form_url');
		}
		if (!isset($item->description) || $item->description === null) {
			$data['description'] = $params->get('event_form_description');
		}
		if ((!isset($item->capacity) || $item->capacity === null) && $params->get('event_form_capacity', 0) > 0) {
			$data['capacity'] = $params->get('event_form_capacity');
		}
		if (!isset($item->max_tickets) || $item->max_tickets === null) {
			$data['max_tickets'] = $params->get('event_form_max_tickets');
		}
		if (!isset($item->booking_opening_date) || $item->booking_opening_date === null) {
			$data['booking_opening_date'] = $params->get('event_form_booking_opening_date');
		}
		if (!isset($item->booking_closing_date) || $item->booking_closing_date === null) {
			$data['booking_closing_date'] = $params->get('event_form_booking_closing_date');
		}
		if (!isset($item->booking_cancel_closing_date) || $item->booking_cancel_closing_date === null) {
			$data['booking_cancel_closing_date'] = $params->get('event_form_booking_cancel_closing_date');
		}
		if (!isset($item->booking_series) || $item->booking_series === null) {
			$data['booking_series'] = $params->get('event_form_booking_series');
		}
		if (!isset($item->booking_waiting_list) || $item->booking_waiting_list === null) {
			$data['booking_waiting_list'] = $params->get('event_form_booking_waiting_list');
		}
		if (!isset($item->payment_provider) || $item->payment_provider === null) {
			$data['payment_provider'] = $params->get('event_form_payment_provider');
		}
		if (!isset($item->terms) || $item->terms === null) {
			$data['terms'] = $params->get('event_form_terms');
		}
		if (!isset($item->booking_information) || $item->booking_information === null) {
			$data['booking_information'] = $params->get('event_form_booking_information');
		}
		if (!isset($item->access) || $item->access === null) {
			$data['access'] = $params->get('event_form_access');
		}
		if (!isset($item->access_content) || $item->access_content === null) {
			$data['access_content'] = $params->get('event_form_access_content');
		}
		if (!isset($item->featured) || $item->featured === null) {
			$data['featured'] = $params->get('event_form_featured');
		}
		if (!isset($item->location_ids) || $item->location_ids === null) {
			$data['location_ids'] = $params->get('event_form_location_ids', []);
		}
		if (!isset($item->language) || $item->language === null) {
			$data['language'] = $params->get('event_form_language');
		}
		if (!isset($item->metakey) || $item->metakey === null) {
			$data['metakey'] = $params->get('menu-meta_keywords');
		}
		if (!isset($item->metadesc) || $item->metadesc === null) {
			$data['metadesc'] = $params->get('menu-meta_description');
		}

		return $data;
	}

	protected function getParams(): Registry
	{
		$params = $this->getState('params');

		if (!$params) {
			$app = Factory::getApplication();

			if ($app instanceof SiteApplication) {
				$params = $app->getParams();
			} else {
				$params = ComponentHelper::getParams('com_dpcalendar');
			}
		}

		return $params;
	}

	private function sendMail(string $action, array $events): void
	{
		// The current user
		$user = $this->getCurrentUser();

		// The event authors
		$authors = [];

		// The event calendars
		$calendarGroups = [];

		// We don't send notifications when an event is external
		foreach ($events as $event) {
			if (!is_numeric($event->catid)) {
				return;
			}

			if (($calendar = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid)) instanceof CalendarInterface) {
				$calendarGroups = array_merge($calendarGroups, $calendar->getParams()->get('notification_groups_' . $action, []));
			}

			if (($user->id != $event->created_by && DPCalendarHelper::getComponentParameter('notification_author', 0) == 1)
				|| DPCalendarHelper::getComponentParameter('notification_author', 0) == 2) {
				$authors[] = $event->created_by;
			}
		}

		// Load the language
		Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		// Create the subject
		$subject = DPCalendarHelper::renderEvents($events, Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_SUBJECT_' . strtoupper($action)));

		// Create the body
		$body = DPCalendarHelper::renderEvents(
			$events,
			Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_' . strtoupper($action) . '_BODY'),
			null,
			[
				'sitename' => Factory::getApplication()->get('sitename'),
				'user'     => $user->name
			]
		);

		// Send the notification to the groups
		DPCalendarHelper::sendMail($subject, $body, 'notification_groups_' . $action, $calendarGroups);

		// Check if authors should get a mail
		if ($authors === [] || !DPCalendarHelper::getComponentParameter('notification_author', 0)) {
			return;
		}

		$authors = array_unique($authors);

		$extraVars = [
			'sitename' => Factory::getApplication()->get('sitename'),
			'user'     => $user->name
		];

		// Create the subject
		$subject = DPCalendarHelper::renderEvents(
			$events,
			Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_SUBJECT_' . strtoupper($action)),
			null,
			$extraVars
		);

		// Create the body
		$body = DPCalendarHelper::renderEvents(
			$events,
			Text::_('COM_DPCALENDAR_NOTIFICATION_EVENT_AUTHOR_' . strtoupper($action) . '_BODY'),
			null,
			$extraVars
		);

		if ($subject === '' || $subject === '0' || ($body === '' || $body === '0')) {
			return;
		}

		$app = Factory::getApplication();

		// Loop over the authors to send the notification
		foreach ($authors as $author) {
			$u = User::getTable();

			// Load the user
			if (!$u->load($author)) {
				continue;
			}

			// Send the mail
			$mailer = $this->getMailerFactory()->createMailer();
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->addRecipient($u->email);
			if ($mailer instanceof Mail) {
				$mailer->IsHTML(true);
			}
			$app->triggerEvent('onDPCalendarBeforeSendMail', ['com_dpcalendar.event.save.author', $mailer, $events]);
			try {
				$mailer->Send();
			} catch (MailDisabledException) {
			}
			$app->triggerEvent('onDPCalendarAfterSendMail', ['com_dpcalendar.event.save.author', $mailer, $events]);
		}
	}
}
