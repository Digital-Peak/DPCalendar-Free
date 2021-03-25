<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

class DPCalendarModelCoupons extends JModelList
{
	public function __construct($config = [])
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'id',
				'a.id',
				'checked_out',
				'a.checked_out',
				'code',
				'a.code',
				'state',
				'a.title',
				'title',
				'a.state',
				'created',
				'a.created',
				'created_by',
				'a.created_by',
				'ordering',
				'a.ordering'
			];
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $published);
		$authorId = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
		$this->setState('filter.author_id', $authorId);

		$app = JFactory::getApplication();
		$this->setState('params', method_exists($app, 'getParams') ? $app->getParams() : JComponentHelper::getParams('com_dpcalendar'));

		// List state information
		parent::populateState('a.id', 'asc');
	}

	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.code');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		// Create a new query object
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$user  = JFactory::getUser();

		// Select the required fields from the table
		$query->select($this->getState('list.select', 'a.*'));
		$query->from($db->quoteName('#__dpcalendar_coupons') . ' AS a');

		// Join over the users for the checked out user
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

		// Join over the users for the author
		$query->select('ua.name AS author_name');
		$query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int)$published);
		} else if ($published === '') {
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by author
		$authorId = $this->getState('filter.author_id');
		if (is_numeric($authorId)) {
			$type = $this->getState('filter.author_id.include', true) ? '= ' : '<>';
			$query->where('a.created_by ' . $type . (int)$authorId);
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
				$search = $db->quote('%' . $db->escape($search, true) . '%');

				$query->where('(a.title LIKE ' . $search . ' or a.code LIKE ' . $search . ')');
			}
		}

		// Filter on the author.
		if ($createdBy = $this->getState('filter.created_by')) {
			$query->where('a.created_by = ' . $db->quote($createdBy));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');
		if (!empty($orderCol)) {
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}
}
