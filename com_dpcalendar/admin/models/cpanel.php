<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;

class DPCalendarModelCpanel extends BaseDatabaseModel
{
	public function getEvents($start, $ordering = 'a.start_date', $direction = 'asc')
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.limit', 3);
		$model->setState('category.recursive', true);
		$model->setState('filter.ongoing', 1);
		$model->setState('filter.expand', true);
		$model->setState('filter.state', [1, 3]);
		$model->setState('filter.publish_date', true);
		$model->setState('list.start-date', $start);
		$model->setState('list.ordering', $ordering);
		$model->setState('list.direction', $direction);

		return $model->getItems();
	}

	public function getTotalEvents()
	{
		$query = $this->_db->getQuery(true);
		$query->select('count(id) as total');
		$query->from('#__dpcalendar_events');
		$this->_db->setQuery($query);

		return $this->_db->loadResult();
	}

	public function getCalendars()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model     = BaseDatabaseModel::getInstance('Calendar', 'DPCalendarModel', ['ignore_request' => true]);
		$calendars = $model->getAllItems();

		PluginHelper::importPlugin('dpcalendar');
		$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
		if (!empty($tmp)) {
			foreach ($tmp as $tmpCalendars) {
				$calendars = array_merge($calendars, $tmpCalendars);
			}
		}

		return $calendars;
	}

	public function getTotalBookings()
	{
		$data = [];
		$date = DPCalendarHelper::getDate();

		$query = $this->_db->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->_db->quote($date->format('Y') . '-01-01'));
		$query->where('state = 1');
		$this->_db->setQuery($query);

		$data['year'] = $this->_db->loadAssoc();

		$query = $this->_db->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->_db->quote($date->format('Y-m') . '-01'));
		$query->where('state = 1');
		$this->_db->setQuery($query);

		$data['month'] = $this->_db->loadAssoc();

		$date->modify('last monday');
		$query = $this->_db->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->_db->quote($date->format('Y-m-d')));
		$query->where('state = 1');
		$this->_db->setQuery($query);

		$data['week'] = $this->_db->loadAssoc();

		$query = $this->_db->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('state != 1');
		$this->_db->setQuery($query);

		$data['notactive'] = $this->_db->loadAssoc();

		return $data;
	}

	public function getTotalTaxRates()
	{
		$query = $this->_db->getQuery(true);
		$query->select('count(id) as total');
		$query->from('#__dpcalendar_taxrates');
		$this->_db->setQuery($query);

		return $this->_db->loadResult();
	}
}
