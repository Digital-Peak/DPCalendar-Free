<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Registry\Registry;

JLoader::import('joomla.application.component.modelform');
JLoader::import('components.com_dpcalendar.tables.event', JPATH_ADMINISTRATOR);

class DPCalendarModelEvent extends JModelForm
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
		if ((!$user->authorise('core.edit.state', 'com_dpcalendar')) && (!$user->authorise('core.edit', 'com_dpcalendar'))) {
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}

		$this->setState('filter.language', JLanguageMultilang::isEnabled());
	}

	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.event', 'event', array('control' => 'jform', 'load_data' => true));
		if (empty($form)) {
			return false;
		}

		$id     = $this->getState('event.id');
		$params = $this->getState('params');
		$event  = $this->_item[$id];
		$params->merge($event->params);

		return $form;
	}

	protected function loadFormData()
	{
		$data = (array)JFactory::getApplication()->getUserState('com_dpcalendar.event.data', array());

		return $data;
	}

	public function &getItem($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : $this->getState('event.id');

		if ($this->_item === null) {
			$this->_item = array();
		}
		$user = JFactory::getUser();

		if (!isset($this->_item[$pk])) {
			if (!empty($pk) && !is_numeric($pk)) {
				JPluginHelper::importPlugin('dpcalendar');
				$tmp = JFactory::getApplication()->triggerEvent('onEventFetch', array($pk));
				if (!empty($tmp)) {
					$tmp[0]->params   = new Registry();
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
					$case_when .= $query->concatenate(array($b_id, 'a.alias'), ':');
					$case_when .= ' ELSE ';
					$case_when .= $b_id . ' END as slug';

					$case_when1 = ' CASE WHEN ';
					$case_when1 .= $query->charLength('c.alias');
					$case_when1 .= ' THEN ';
					$c_id       = $query->castAsChar('c.id');
					$case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
					$case_when1 .= ' ELSE ';
					$case_when1 .= $c_id . ' END as catslug';

					$query->select($this->getState('item.select', 'a.*'));
					$query->from('#__dpcalendar_events AS a');

					// Join on category table.
					$query->select('c.access AS category_access');
					$query->join('LEFT', '#__categories AS c on c.id = a.catid');

					$query->select('u.name AS author');
					$query->join('LEFT', '#__users AS u on u.id = a.created_by');

					// Get contact id
					$subQuery = $db->getQuery(true)
						->select('MAX(contact.id) AS id')
						->from('#__contact_details AS contact')
						->where('contact.published = 1')
						->where('contact.user_id = a.created_by');

					// Filter by language
					if ($this->getState('filter.language')) {
						$subQuery->where(
							'(contact.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') .
							') OR contact.language IS NULL)');
					}
					$query->select('(' . $subQuery . ') as contactid');

					$query->where('a.id = ' . (int)$pk);

					// Filter by start and end dates.
					$nullDate = $db->Quote($db->getNullDate());
					$nowDate  = $db->Quote(JFactory::getDate()->toSql());

					// Filter by published state.
					$published = $this->getState('filter.published');
					$archived  = $this->getState('filter.archived');
					if (is_numeric($published)) {
						$query->where('(a.state = ' . (int)$published . ' OR a.state =' . (int)$archived . ')');
						$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
						$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
					}

					// Implement View Level Access
					if (!$user->authorise('core.admin', 'com_dpcalendar')) {
						$query->where('a.access IN (' . implode(',', $groups) . ')');
					}

					$db->setQuery($query);

					$row  = $db->loadAssoc();
					$data = $this->getTable('Event', 'DPCalendarTable');
					if ($row) {
						$data->bind($row);
						$data->setProperties($row);
					}

					if ($error = $db->getErrorMsg()) {
						throw new Exception($error);
					}

					if (empty($data)) {
						throw new Exception(JText::_('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND'), 404);
					}

					// Check for published state if filter set.
					if (((is_numeric($published)) || (is_numeric($archived))) && (($data->state != $published) && ($data->state != $archived))) {
						JError::raiseError(404, JText::_('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND'));
					}

					if (!DPCalendarHelper::isFree()) {
						JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
						$ticketsModel = JModelLegacy::getInstance('Tickets', 'DPCalendarModel');
						$ticketsModel->getState();
						$ticketsModel->setState('filter.event_id', $data->id);
						$ticketsModel->setState('filter.public', $this->getState('filter.public'));
						$ticketsModel->setState('list.limit', 10000);
						$data->tickets = $ticketsModel->getItems();
					}

					$locationQuery = $db->getQuery(true);
					$locationQuery->select('a.*');
					$locationQuery->from('#__dpcalendar_locations AS a');

					$locationQuery->join('RIGHT',
						'#__dpcalendar_events_location AS rel on rel.event_id = ' . (int)$pk . ' and rel.location_id = a.id');
					$locationQuery->where('state = 1');
					$locationQuery->order('ordering asc');
					$db->setQuery($locationQuery);
					$data->locations = $db->loadObjectList();
					foreach ($data->locations as $location) {
						$location->rooms = json_decode($location->rooms);
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

					$data->price         = json_decode($data->price);
					$data->earlybird     = json_decode($data->earlybird);
					$data->user_discount = json_decode($data->user_discount);
					$data->rooms         = explode(',', $data->rooms);

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
			$item->params->set('access-invite',
				is_numeric($item->catid) &&
				($item->created_by == $user->id || $user->authorise('dpcalendar.invite', 'com_dpcalendar.category.' . $item->catid)));

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
}
