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
		$query = $this->getDbo()->getQuery(true);
		$query->select('count(id) as total');
		$query->from('#__dpcalendar_events');

		$this->getDbo()->setQuery($query);

		return $this->getDbo()->loadResult();
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

		$query = $this->getDbo()->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->getDbo()->quote($date->format('Y') . '-01-01'));
		$query->where('state = 1');

		$this->getDbo()->setQuery($query);

		$data['year'] = $this->getDbo()->loadAssoc();

		$query = $this->getDbo()->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->getDbo()->quote($date->format('Y-m') . '-01'));
		$query->where('state = 1');

		$this->getDbo()->setQuery($query);

		$data['month'] = $this->getDbo()->loadAssoc();

		$date->modify('last monday');
		$query = $this->getDbo()->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->getDbo()->quote($date->format('Y-m-d')));
		$query->where('state = 1');

		$this->getDbo()->setQuery($query);

		$data['week'] = $this->getDbo()->loadAssoc();

		$query = $this->getDbo()->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('state != 1');

		$this->getDbo()->setQuery($query);

		$data['notactive'] = $this->getDbo()->loadAssoc();

		return $data;
	}

	public function getTotalTaxRates()
	{
		$query = $this->getDbo()->getQuery(true);
		$query->select('count(id) as total');
		$query->from('#__dpcalendar_taxrates');

		$this->getDbo()->setQuery($query);

		return $this->getDbo()->loadResult();
	}
}
