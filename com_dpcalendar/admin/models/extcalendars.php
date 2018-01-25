<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modellist');

class DPCalendarModelExtcalendars extends JModelList
{

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'title',
				'a.title',
				'alias',
				'a.alias',
				'state',
				'a.state',
				'created',
				'a.created',
				'created_by',
				'a.created_by',
				'ordering',
				'a.ordering',
				'language',
				'a.language',
				'publish_up',
				'a.publish_up',
				'publish_down',
				'a.publish_down'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);

		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$plugin = $this->getUserStateFromRequest($this->context . '.filter.plugin', 'dpplugin', '');
		$this->setState('filter.plugin', $plugin);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_dpcalendar');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.ordering', 'asc');
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');
		$id .= ':' . $this->getState('filter.language');

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		$calendars = parent::getItems();
		if (!$calendars) {
			return $calendars;
		}

		foreach ($calendars as $calendar) {
			$calendar->params = new \Joomla\Registry\Registry(json_decode($calendar->params));

			if ($pw = $calendar->params->get('password')) {
				$calendar->params->set('password', \DPCalendar\Helper\DPCalendarHelper::deobfuscate($pw));
			}
		}

		return $calendars;
	}

	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$user  = JFactory::getUser();

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from($db->quoteName('#__dpcalendar_extcalendars') . ' AS a');

		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

		// Join over the language
		$query->select('l.title AS language_title');
		$query->join('LEFT', $db->quoteName('#__languages') . ' AS l ON l.lang_code = a.language');

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int)$published);
		} elseif ($published === '') {
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'ids:') === 0) {
				$ids = explode(',', substr($search, 4));
				ArrayHelper::toInteger($ids);
				$query->where('a.id in (' . implode(',', $ids) . ')');
			} else if (stripos($search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr($search, 3));
			} else {
				$search = $db->Quote('%' . $db->escape($search, true) . '%');

				$query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
			}
		}

		if (!$user->authorise('core.admin', 'com_dpcalendar')) {
			$query->where('a.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
		}

		// Filter on the plugin.
		if ($plugin = $this->getState('filter.plugin')) {
			$query->where('a.plugin = ' . $db->quote($plugin));
		}

		// Filter on the language.
		if ($language = $this->getState('filter.language')) {
			$query->where('a.language = ' . $db->quote($language));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');
		if (!empty($orderCol)) {
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		// Echo nl2br(str_replace('#__', 'a_', $query));die;
		return $query;
	}
}
