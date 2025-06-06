<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class EventModel extends ItemModel
{
	protected $_item;
	protected $_context = 'com_dpcalendar.event';

	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = $app->getInput()->getString('id', '');
		$this->setState('event.id', $pk);

		$params = $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');
		if (!$params->get('event_form_fields_order_')) {
			$params->set(
				'event_form_fields_order_',
				ComponentHelper::getParams('com_dpcalendar')->get('event_form_fields_order_', new \stdClass())
			);
		}
		$this->setState('params', $params);
		$this->setState('filter.public', $params->get('event_show_tickets'));

		$user = $this->getCurrentUser();
		if (!$user->authorise('core.edit.state', 'com_dpcalendar') && !$user->authorise('core.edit', 'com_dpcalendar')) {
			$this->setState('filter.state', [1, 3]);
		}

		$this->setState('filter.language', Multilanguage::isEnabled());
	}

	/**
	 * @param   mixed  $pk  The id of the primary key or an array of fields
	 *
	 * @return  \stdClass|false  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		$pk = (string)($pk ?: $this->getState('event.id'));

		if ($this->_item === null) {
			$this->_item = [];
		}

		$user = $this->getCurrentUser();

		if (!isset($this->_item[$pk])) {
			if ($pk !== '' && $pk !== '0' && !is_numeric($pk)) {
				PluginHelper::importPlugin('dpcalendar');
				$tmp = Factory::getApplication()->triggerEvent('onEventFetch', [$pk]);
				if (!empty($tmp)) {
					$tmp[0]->params   = new Registry($tmp[0]->params);
					$this->_item[$pk] = $tmp[0];
				} else {
					$this->_item[$pk] = false;
				}
			} else {
				$db     = $this->getDatabase();
				$query  = $db->getQuery(true);
				$groups = $user->getAuthorisedViewLevels();

				// Sqlsrv changes
				$case_when = ' CASE WHEN ';
				$case_when .= $query->charLength('a.alias');
				$case_when .= ' THEN ';
				$b_id = $query->castAs('CHAR', 'a.id');
				$case_when .= $query->concatenate([$b_id, 'a.alias'], ':');
				$case_when .= ' ELSE ';
				$case_when .= $b_id . ' END as slug';

				$case_when1 = ' CASE WHEN ';
				$case_when1 .= $query->charLength('c.alias');
				$case_when1 .= ' THEN ';
				$c_id = $query->castAs('CHAR', 'c.id');
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

				// Join over the original
				$query->select('o.title as original_title, o.rrule as original_rrule');
				$query->join('LEFT', '#__dpcalendar_events AS o ON o.id = a.original_id');

				// Join to tickets to check if the event has waiting list tickets
				$query->select('count(DISTINCT wl.id) as waiting_list_count');
				$query->join('LEFT', '#__dpcalendar_tickets AS wl ON (wl.event_id = a.id or wl.event_id = a.original_id) and wl.state = 8');

				$query->select('u.name AS author');
				$query->join('LEFT', '#__users AS u on u.id = a.created_by');

				$query->select('co.id AS contactid, co.alias as contactalias, co.catid as contactcatid');
				$query->join('LEFT', '#__contact_details AS co on co.user_id = a.created_by
					and (co.published = 1 or co.published is null)
					and (co.language in (' . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ',' . $db->quote('*') . ') OR co.language IS NULL)');

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
					$state = ArrayHelper::toInteger($state);
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
				if (!$data instanceof \stdClass) {
					throw new \Exception(Text::_('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND'), 404);
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

				if (!empty($data->id) && !DPCalendarHelper::isFree()) {
					$ticketsModel = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Tickets', 'Administrator', ['ignore_request' => true]);
					$ticketsModel->getState();
					$ticketsModel->setState('filter.event_id', $data->id);
					$ticketsModel->setState('filter.public', $this->getState('filter.public'));
					$ticketsModel->setState('filter.state', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);
					$ticketsModel->setState('list.limit', 10000);

					$data->tickets = $ticketsModel->getItems();
				}

				$data->locations = [];
				if (!empty($data->location_ids)) {
					$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
					$model->getState();
					$model->setState('list.ordering', 'ordering');
					$model->setState('list.direction', 'asc');
					$model->setState('filter.search', 'ids:' . $data->location_ids);
					$data->locations = $model->getItems();
				}

				$data->hostContacts = [];
				if ($data->host_ids) {
					$query = $this->getDatabase()->getQuery(true);
					$query->select('id, catid, alias, name, user_id')->from('#__contact_details')
					->where('(published = 1 or published is null)')
					->where('(language in (' . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ',' . $db->quote('*') . ') OR language IS NULL)')
					->where('user_id in (' . $data->host_ids . ')');
					$this->getDatabase()->setQuery($query);

					$data->hostContacts = $db->loadObjectList();
				}

				$registry = new Registry();
				$registry->loadString($data->metadata ?: '');
				$data->metadata = $registry;

				$data->prices             = $data->prices ? json_decode((string)$data->prices) : null;
				$data->earlybird_discount = $data->earlybird_discount ? json_decode((string)$data->earlybird_discount) : [];
				$data->user_discount      = $data->user_discount ? json_decode((string)$data->user_discount) : [];
				$data->events_discount    = $data->events_discount ? json_decode((string)$data->events_discount) : [];
				$data->tickets_discount   = $data->tickets_discount ? json_decode((string)$data->tickets_discount) : [];
				$data->booking_options    = $data->booking_options ? json_decode((string)$data->booking_options) : null;
				$data->schedule           = $data->schedule ? json_decode((string)$data->schedule) : [];
				$data->rooms              = $data->rooms ? explode(',', (string)$data->rooms) : [];
				$data->payment_provider   = $data->payment_provider ? explode(',', (string)$data->payment_provider) : [];

				$data->roomTitles = [];
				if ($data->locations && $data->rooms) {
					/** @var \stdClass $location */
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
				if (!empty($data->booking_options)) {
					foreach ((array)$data->booking_options as $option) {
						if (empty($option->min_amount)) {
							$option->min_amount = 0;
						}
					}
				}

				$this->_item[$pk] = $data;
			}
		}

		$item = $this->_item[$pk];
		if (!$item instanceof \stdClass || !$item->catid) {
			return $item;
		}

		// Implement View Level Access
		if (!$user->authorise('core.admin', 'com_dpcalendar') && !\in_array($item->access_content, $user->getAuthorisedViewLevels())) {
			$item->title               = Text::_('COM_DPCALENDAR_EVENT_BUSY');
			$item->location            = '';
			$item->locations           = [];
			$item->location_ids        = null;
			$item->rooms               = [];
			$item->url                 = '';
			$item->description         = '';
			$item->images              = null;
			$item->schedule            = [];
			$item->prices              = null;
			$item->capacity            = 0;
			$item->capacity_used       = 0;
			$item->booking_information = '';
			$item->booking_options     = null;
		}

		DPCalendarHelper::parseImages($item);
		DPCalendarHelper::parseReadMore($item);

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
			((!$user->guest && ($item->created_by == $user->id || \in_array($user->id, explode(',', (string)($item->host_ids ?: '')))))
				|| $user->authorise('dpcalendar.admin.book', 'com_dpcalendar.category.' . $item->catid))
		);

		$calendar = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($item->catid);
		$item->params->set('access-edit', $calendar instanceof CalendarInterface && ($calendar->canEdit() || ($calendar->canEditOwn() && $item->created_by == $user->id)));
		$item->params->set('access-delete', $calendar instanceof CalendarInterface && ($calendar->canDelete() || ($calendar->canEditOwn() && $item->created_by == $user->id)));
		$item->params->set(
			'access-invite',
			is_numeric($item->catid) &&
			($item->created_by == $user->id || $user->authorise('dpcalendar.invite', 'com_dpcalendar.category.' . $item->catid))
		);

		$item->color = str_replace('#', '', $item->color ?? '');
		if (\is_array($item->color)) {
			$item->color = implode('', $item->color);
		}

		// Ensure a color is set
		if ($item->color === '' || $item->color === '0') {
			$item->color = $calendar instanceof CalendarInterface ? str_replace('#', '', $calendar->getColor()) : '3366CC';
		}

		// Check if it is a valid color
		if ((\strlen($item->color) !== 6 && \strlen($item->color) !== 3) || !ctype_xdigit($item->color)) {
			$item->color = '3366CC';
		}

		if (\is_string($item->exdates)) {
			$item->exdates = ArrayHelper::getColumn((array)json_decode($item->exdates), 'date');
		}

		return $item;
	}

	public function hit(?string $id = null): bool
	{
		if ($id === null || $id === '' || $id === '0') {
			$id = $this->getState('event.id');
		}

		if (!is_numeric($id)) {
			return false;
		}

		$event = $this->getTable('Event', 'Administrator');

		return $event->hit($id);
	}

	public function getSeriesEventsModel(\stdClass $event): EventsModel
	{
		$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
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
