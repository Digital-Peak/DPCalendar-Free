<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;

class TaxratesModel extends ListModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'id',
				'a.id',
				'title',
				'a.title',
				'state',
				'a.state',
				'rate',
				'a.rate',
				'created',
				'a.created',
				'created_by',
				'a.created_by',
				'ordering',
				'a.ordering'
			];
		}

		parent::__construct($config, $factory);
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

		$app = Factory::getApplication();
		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));

		// List state information.
		parent::populateState('a.title', 'asc');
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.rate');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		$taxrates = parent::getItems();

		foreach ($taxrates as $taxRate) {
			if (!is_string($taxRate->countries)) {
				continue;
			}
			$taxRate->countries = json_decode($taxRate->countries);

			$taxRate->rate = sprintf('%g', $taxRate->rate);
		}

		return $taxrates;
	}

	protected function getListQuery()
	{
		// Create a new query object
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);
		$this->getCurrentUser();

		// Select the required fields from the table
		$query->select($this->getState('list.select', 'a.*'));
		$query->from('#__dpcalendar_taxrates AS a');

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
		} elseif ($published === '') {
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
			if (stripos((string)$search, 'ids:') === 0) {
				$ids = explode(',', substr((string)$search, 4));
				$ids = ArrayHelper::toInteger($ids);
				$query->where('a.id in (' . implode(',', $ids) . ')');
			} elseif (stripos((string)$search, 'id:') === 0) {
				$query->where('a.id = ' . (int)substr((string)$search, 3));
			} else {
				$search = $db->quote('%' . $db->escape($search, true) . '%');

				$query->where('(a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search . ')');
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
