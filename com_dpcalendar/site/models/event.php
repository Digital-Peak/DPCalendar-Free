<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

JLoader::import('components.com_dpcalendar.tables.event', JPATH_ADMINISTRATOR);

class DPCalendarModelEvent extends JModelItem
{
	protected $view_item = 'contact';
	protected $_item = null;
	protected $_context = 'com_dpcalendar.event';

	protected function populateState()
	{
		$app = JFactory::getApplication('site');

		// Load state from the request.
		$pk = $app->input->getVar('id');
		$this->setState('event.id', $pk);

		// Load the parameters.
		$params = $app->isClient('administrator') ? JComponentHelper::getParams('com_dpcalendar') : $app->getParams();
		$this->setState('params', $params);
		$this->setState('filter.public', $params->get('event_show_tickets'));

		$user = JFactory::getUser();
		if (!$user->authorise('core.edit.state', 'com_dpcalendar') && !$user->authorise('core.edit', 'com_dpcalendar')) {
			$this->setState('filter.published', [1, 3]);
		}

		$this->setState('filter.language', JLanguageMultilang::isEnabled());
	}

	public function &getItem($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : $this->getState('event.id');

		if ($this->_item === null) {
			$this->_item = [];
		}
		$user = JFactory::getUser();

		if (!isset($this->_item[$pk])) {
			if (!empty($pk) && !is_numeric($pk)) {
				JPluginHelper::importPlugin('dpcalendar');
				$tmp = JFactory::getApplication()->triggerEvent('onEventFetch', [$pk]);
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
					$b_id      = $query->castAsChar('a.id');
					$case_when .= $query->concatenate([$b_id, 'a.alias'], ':');
					$case_when .= ' ELSE ';
					$case_when .= $b_id . ' END as slug';

					$case_when1 = ' CASE WHEN ';
					$case_when1 .= $query->charLength('c.alias');
					$case_when1 .= ' THEN ';
					$c_id       = $query->castAsChar('c.id');
					$case_when1 .= $query->concatenate([$c_id, 'c.alias'], ':');
					$case_when1 .= ' ELSE ';
					$case_when1 .= $c_id . ' END as catslug';

					$query->select($this->getState('item.select', 'a.*'));
					$query->from('#__dpcalendar_events AS a');

					// Join on category table.
					$query->select('c.access AS category_access');
					$query->join('LEFT', '#__categories AS c on c.id = a.catid');

					// Join locations
					$query->select("GROUP_CONCAT(v.id SEPARATOR ', ') location_ids");
					$query->join('LEFT', '#__dpcalendar_events_location AS rel ON a.id = rel.event_id');
					$query->join('LEFT', '#__dpcalendar_locations AS v ON rel.location_id = v.id');

					// Join on series max/min
					$query->select('min(ser.start_date) AS series_min_start_date, max(ser.end_date) AS series_max_end_date');
					$query->join(
						'LEFT',
						'#__dpcalendar_events AS ser on (ser.original_id = a.original_id and a.original_id > 0) or ser.original_id = a.id'
					);

					$query->select('u.name AS author');
					$query->join('LEFT', '#__users AS u on u.id = a.created_by');

					$query->select('co.id AS contactid, co.alias as contactalias, co.catid as contactcatid');
					$query->join('LEFT', '#__contact_details AS co on co.user_id = a.created_by
					and (co.published = 1 or co.published is null)
					and (co.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ') OR co.language IS NULL)');

					$query->where('a.id = ' . (int)$pk);

					// Filter by start and end dates.
					$nullDate = $db->quote($db->getNullDate());
					$nowDate  = $db->quote(JFactory::getDate()->toSql());

					// Filter by published state.
					$state = $this->getState('filter.published', []);
					if (is_numeric($state)) {
						$state = [$state];
					}

					if ($state) {
						ArrayHelper::toInteger($state);
						$query->where('a.state in (' . implode(',', $state) . ')');
						$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
						$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
					}

					// Implement View Level Access
					if (!$user->authorise('core.admin', 'com_dpcalendar')) {
						$query->where('a.access IN (' . implode(',', $groups) . ')');
					}
					$db->setQuery($query);

					$data = $db->loadObject();

					if (empty($data)) {
						throw new Exception(JText::_('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND'), 404);
					}

					JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
					if (!DPCalendarHelper::isFree()) {
						$ticketsModel = JModelLegacy::getInstance('Tickets', 'DPCalendarModel', ['ignore_request' => true]);
						$ticketsModel->getState();
						$ticketsModel->setState('filter.event_id', $data->id);
						$ticketsModel->setState('filter.public', $this->getState('filter.public'));
						$ticketsModel->setState('list.limit', 10000);
						$data->tickets = $ticketsModel->getItems();
					}

					$model = JModelLegacy::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
					$model->getState();
					$model->setState('list.ordering', 'ordering');
					$model->setState('list.direction', 'asc');
					$data->locations = [];
					if (!empty($data->location_ids)) {
						$model->setState('filter.search', 'ids:' . $data->location_ids);
						$data->locations = $model->getItems();
					}

					// Convert parameter fields to objects.
					$registry = new Registry();
					$registry->loadString($data->params);
					if ($this->getState('params')) {
						$data->params = clone $this->getState('params');
						$data->params->merge($registry);
					} else {
						$data->params = $registry;
					}

					$registry = new Registry();
					$registry->loadString($data->metadata);
					$data->metadata = $registry;

					$data->price           = json_decode($data->price);
					$data->earlybird       = json_decode($data->earlybird);
					$data->user_discount   = json_decode($data->user_discount);
					$data->booking_options = $data->booking_options ? json_decode($data->booking_options) : [];
					$data->schedule        = $data->schedule ? json_decode($data->schedule) : [];
					$data->rooms           = $data->rooms ? explode(',', $data->rooms) : [];
					$data->plugintype      = $data->plugintype ? explode(',', $data->plugintype) : [];

					\DPCalendar\Helper\DPCalendarHelper::parseImages($data);

					$this->_item[$pk] = $data;
				} catch (Exception $e) {
					$this->setError($e);
					$this->_item[$pk] = false;
				}
			}
		}

		$item = $this->_item[$pk];
		if (is_object($item) && $item->catid) {
			// Implement View Level Access
			if (!$user->authorise('core.admin', 'com_dpcalendar') && !in_array($item->access_content, $user->getAuthorisedViewLevels())) {
				$item->title       = JText::_('COM_DPCALENDAR_EVENT_BUSY');
				$item->location    = '';
				$item->locations   = null;
				$item->url         = '';
				$item->description = '';
			}

			$item->params->set(
				'access-tickets',
				is_numeric($item->catid) && ((!$user->guest && $item->created_by == $user->id) || $user->authorise('core.admin', 'com_dpcalendar'))
			);
			$item->params->set(
				'access-bookings',
				is_numeric($item->catid) && ((!$user->guest && $item->created_by == $user->id) || $user->authorise('core.admin', 'com_dpcalendar'))
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
			if (!$item->color) {
				$item->color = $calendar->color;
			}
		}

		return $this->_item[$pk];
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
		\JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = \JModelLegacy::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
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
