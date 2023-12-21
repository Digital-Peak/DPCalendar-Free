<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

JLoader::import('components.com_dpcalendar.tables.event', JPATH_ADMINISTRATOR);

class DPCalendarModelEvent extends ItemModel
{
	protected $view_item = 'contact';
	protected $_item     = null;
	protected $_context  = 'com_dpcalendar.event';

	protected function populateState()
	{
		$app = Factory::getApplication('site');

		// Load state from the request.
		$pk = $app->input->getString('id');
		$this->setState('event.id', $pk);

		$params = method_exists($app, 'getParams') ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');
		if (!$params->get('event_form_fields_order_')) {
			$params->set(
				'event_form_fields_order_',
				ComponentHelper::getParams('com_dpcalendar')->get('event_form_fields_order_', new stdClass())
			);
		}
		$this->setState('params', $params);
		$this->setState('filter.public', $params->get('event_show_tickets'));

		$user = Factory::getUser();
		if (!$user->authorise('core.edit.state', 'com_dpcalendar') && !$user->authorise('core.edit', 'com_dpcalendar')) {
			$this->setState('filter.state', [1, 3]);
		}

		$this->setState('filter.language', Multilanguage::isEnabled());
	}

	public function &getItem($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : $this->getState('event.id');

		if ($this->_item === null) {
			$this->_item = [];
		}
		$user = Factory::getUser();

		if (!isset($this->_item[$pk])) {
			if (!empty($pk) && !is_numeric($pk)) {
				PluginHelper::importPlugin('dpcalendar');
				$tmp = Factory::getApplication()->triggerEvent('onEventFetch', [$pk]);
				if (!empty($tmp)) {
					$tmp[0]->params   = new Registry($tmp[0]->params);
					$this->_item[$pk] = $tmp[0];
				} else {
					$this->_item[$pk] = false;
				}
			} else {
				try {
					$db     = $this->getDbo();
					$query  = $db->getQuery(true);
					$groups = $user->getAuthorisedViewLevels();

					// Sqlsrv changes
					$case_when = ' CASE WHEN ';
					$case_when .= $query->charLength('a.alias');
					$case_when .= ' THEN ';
					$b_id = $query->castAsChar('a.id');
					$case_when .= $query->concatenate([$b_id, 'a.alias'], ':');
					$case_when .= ' ELSE ';
					$case_when .= $b_id . ' END as slug';

					$case_when1 = ' CASE WHEN ';
					$case_when1 .= $query->charLength('c.alias');
					$case_when1 .= ' THEN ';
					$c_id = $query->castAsChar('c.id');
					$case_when1 .= $query->concatenate([$c_id, 'c.alias'], ':');
					$case_when1 .= ' ELSE ';
					$case_when1 .= $c_id . ' END as catslug';

					$query->select($this->getState('item.select', 'a.*'));
					$query->from('#__dpcalendar_events AS a');

					// Join on category table.
					$query->select('c.access AS category_access');
					$query->join('LEFT', '#__categories AS c on c.id = a.catid');

					// Join locations
					$query->select("GROUP_CONCAT(DISTINCT v.id SEPARATOR ',') location_ids");
					$query->join('LEFT', '#__dpcalendar_events_location AS rel ON a.id = rel.event_id');
					$query->join('LEFT', '#__dpcalendar_locations AS v ON rel.location_id = v.id');

					// Join hosts
					$query->select("GROUP_CONCAT(DISTINCT uh.id SEPARATOR ',') host_ids");
					$query->join('LEFT', '#__dpcalendar_events_hosts AS relu ON a.id = relu.event_id');
					$query->join('LEFT', '#__users AS uh ON relu.user_id = uh.id');

					// Join on series max/min
					$query->select('min(ser.start_date) AS series_min_start_date, max(ser.end_date) AS series_max_end_date');
					$query->join(
						'LEFT',
						'#__dpcalendar_events AS ser on (ser.original_id = a.original_id and a.original_id > 0) or ser.original_id = a.id'
					);

					// Join to tickets to check if the event has waiting list tickets
					$query->select('count(DISTINCT wl.id) as waiting_list_count');
					$query->join('LEFT', '#__dpcalendar_tickets AS wl ON (wl.event_id = a.id or wl.event_id = a.original_id) and wl.state = 8');

					$query->select('u.name AS author');
					$query->join('LEFT', '#__users AS u on u.id = a.created_by');

					$query->select('co.id AS contactid, co.alias as contactalias, co.catid as contactcatid');
					$query->join('LEFT', '#__contact_details AS co on co.user_id = a.created_by
					and (co.published = 1 or co.published is null)
					and (co.language in (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ') OR co.language IS NULL)');

					$query->where('a.id = ' . (int)$pk);

					// Filter by start and end dates
					$nowDate = $db->quote(Factory::getDate()->toSql());

					// Filter by published state.
					$state      = $this->getState('filter.state', []);
					$stateOwner = $this->getState('filter.state_owner') ? ' or a.created_by = ' . $user->id : '';
					if (is_numeric($state)) {
						$state = [$state];
					}

					if ($state) {
						ArrayHelper::toInteger($state);
						$query->where('(a.state in (' . implode(',', $state) . ')' . $stateOwner . ')');
						$query->where('(a.publish_up is null OR a.publish_up <= ' . $nowDate . ')');
						$query->where('(a.publish_down is null OR a.publish_down >= ' . $nowDate . ')');
					}

					// Implement View Level Access
					if (!$user->authorise('core.admin', 'com_dpcalendar')) {
						$query->where('a.access IN (' . implode(',', $groups) . ')');
					}
					$db->setQuery($query);

					$data = $db->loadObject();
					if (empty($data)) {
						throw new Exception(Text::_('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND'), 404);
					}

					BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
					if (!DPCalendarHelper::isFree()) {
						$ticketsModel = BaseDatabaseModel::getInstance('Tickets', 'DPCalendarModel', ['ignore_request' => true]);
						$ticketsModel->getState();
						$ticketsModel->setState('filter.event_id', $data->id);
						$ticketsModel->setState('filter.public', $this->getState('filter.public'));
						$ticketsModel->setState('filter.state', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);
						$ticketsModel->setState('list.limit', 10000);
						$data->tickets = $ticketsModel->getItems();
					}

					$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
					$model->getState();
					$model->setState('list.ordering', 'ordering');
					$model->setState('list.direction', 'asc');
					$data->locations = [];
					if (!empty($data->location_ids)) {
						$model->setState('filter.search', 'ids:' . $data->location_ids);
						$data->locations = $model->getItems();
					}

					$data->hostContacts = [];
					if ($data->host_ids) {
						$query = $this->getDbo()->getQuery(true);
						$query->select('id, catid, alias, name, user_id')->from('#__contact_details')
						->where('(published = 1 or published is null)')
						->where('(language in (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ') OR language IS NULL)')
						->where('user_id in (' . $data->host_ids . ')');
						$this->getDbo()->setQuery($query);

						$data->hostContacts = $db->loadObjectList();
					}

					// Convert parameter fields to objects.
					$registry = new Registry();
					$registry->loadString($data->params ?: '');
					if ($this->getState('params')) {
						$data->params = clone $this->getState('params');
						$data->params->merge($registry);
					} else {
						$data->params = $registry;
					}

					$registry = new Registry();
					$registry->loadString($data->metadata ?: '');
					$data->metadata = $registry;

					$data->price            = $data->price ? json_decode($data->price) : [];
					$data->earlybird        = $data->earlybird ? json_decode($data->earlybird) : [];
					$data->user_discount    = $data->user_discount ? json_decode($data->user_discount) : [];
					$data->booking_options  = $data->booking_options ? json_decode($data->booking_options) : [];
					$data->schedule         = $data->schedule ? json_decode($data->schedule) : [];
					$data->rooms            = $data->rooms ? explode(',', $data->rooms) : [];
					$data->payment_provider = $data->payment_provider ? explode(',', $data->payment_provider) : [];

					$data->roomTitles = [];
					if ($data->locations && $data->rooms) {
						foreach ($data->locations as $location) {
							if (empty($location->rooms)) {
								continue;
							}

							foreach ($data->rooms as $room) {
								[$locationId, $roomId] = explode('-', $room, 2);

								foreach ($location->rooms as $lroom) {
									if ($locationId != $location->id || $roomId != $lroom->id) {
										continue;
									}

									$data->roomTitles[$locationId][$room] = $lroom->title;
								}
							}
						}
					}

					// Ensure min amount is properly set
					if (is_object($data->booking_options)) {
						foreach ($data->booking_options as $option) {
							if (empty($option->min_amount)) {
								$option->min_amount = 0;
							}
						}
					}

					$this->_item[$pk] = $data;
				} catch (Exception $e) {
					$this->setError($e);
					$this->_item[$pk] = false;
				}
			}
		}

		$item = $this->_item[$pk];
		if (!is_object($item) || !$item->catid) {
			return $item;
		}

		// Implement View Level Access
		if (!$user->authorise('core.admin', 'com_dpcalendar') && !in_array($item->access_content, $user->getAuthorisedViewLevels())) {
			$item->title               = Text::_('COM_DPCALENDAR_EVENT_BUSY');
			$item->location            = '';
			$item->locations           = [];
			$item->location_ids        = null;
			$item->rooms               = [];
			$item->url                 = '';
			$item->description         = '';
			$item->images              = null;
			$item->schedule            = [];
			$item->price               = null;
			$item->capacity            = 0;
			$item->capacity_used       = 0;
			$item->booking_information = '';
			$item->booking_options     = null;
		}

		\DPCalendar\Helper\DPCalendarHelper::parseImages($item);
		\DPCalendar\Helper\DPCalendarHelper::parseReadMore($item);

		$item->params->set(
			'access-tickets',
			is_numeric($item->catid) &&
			((!$user->guest && $item->created_by == $user->id)
				|| $user->authorise('dpcalendar.admin.book', 'com_dpcalendar.category.' . $item->catid))
		);
		$item->params->set(
			'access-bookings',
			is_numeric($item->catid) &&
			((!$user->guest && $item->created_by == $user->id)
				|| $user->authorise('dpcalendar.admin.book', 'com_dpcalendar.category.' . $item->catid))
		);
		$item->params->set(
			'send-tickets-mail',
			is_numeric($item->catid) &&
			// Allow to send mails when user is author, host or global admin
			((!$user->guest && ($item->created_by == $user->id || in_array($user->id, explode(',', $item->host_ids ?: ''))))
				|| $user->authorise('dpcalendar.admin.book', 'com_dpcalendar.category.' . $item->catid))
		);

		$calendar = DPCalendarHelper::getCalendar($item->catid);
		$item->params->set('access-edit', $calendar->canEdit || ($calendar->canEditOwn && $item->created_by == $user->id));
		$item->params->set('access-delete', $calendar->canDelete || ($calendar->canEditOwn && $item->created_by == $user->id));
		$item->params->set(
			'access-invite',
			is_numeric($item->catid) &&
			($item->created_by == $user->id || $user->authorise('dpcalendar.invite', 'com_dpcalendar.category.' . $item->catid))
		);

		// Ensure a color is set
		if (empty($item->color)) {
			$item->color = $calendar ? $calendar->color : '3366CC';
		}

		// Check if it is a valid color
		if ((\strlen($item->color) !== 6 && \strlen($item->color) !== 3) || !ctype_xdigit($item->color)) {
			$item->color = '3366CC';
		}

		if (is_string($item->exdates)) {
			$item->exdates = ArrayHelper::getColumn((array)json_decode($item->exdates), 'date');
		}

		return $item;
	}

	public function hit($id = null)
	{
		if (empty($id)) {
			$id = $this->getState('event.id');
		}

		if (!is_numeric($id)) {
			return 0;
		}

		$event = $this->getTable('Event', 'DPCalendarTable');

		return $event->hit($id);
	}

	public function getSeriesEventsModel($event)
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();
		$model->setState('filter.children', $event->original_id == -1 ? $event->id : $event->original_id);
		$model->setState('list.limit', 5);
		$model->setState('filter.expand', true);
		$model->setState('filter.state', [1]);

		$startDate = DPCalendarHelper::getDate($event->start_date);
		// We do not want to have the current event in the series
		$startDate->modify('+1 second');
		$model->setState('list.start-date', $startDate);

		return $model;
	}
}
