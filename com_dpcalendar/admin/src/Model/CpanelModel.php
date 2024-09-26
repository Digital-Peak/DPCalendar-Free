<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;

class CpanelModel extends BaseDatabaseModel
{
	public function getEvents(string $start, string $ordering = 'a.start_date', string $direction = 'asc'): array
	{
		$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
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

	public function getTotalEvents(): int
	{
		$query = $this->getDatabase()->getQuery(true);
		$query->select('count(id) as total');
		$query->from('#__dpcalendar_events');

		$this->getDatabase()->setQuery($query);

		return $this->getDatabase()->loadResult() ?: 0;
	}

	public function getCalendars(): array
	{
		$model     = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Site', ['ignore_request' => true]);
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

	public function getTotalBookings(): array
	{
		$data = [];
		$date = DPCalendarHelper::getDate();

		$query = $this->getDatabase()->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->getDatabase()->quote($date->format('Y') . '-01-01'));
		$query->where('state = 1');

		$this->getDatabase()->setQuery($query);

		$data['year'] = $this->getDatabase()->loadAssoc();

		$query = $this->getDatabase()->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->getDatabase()->quote($date->format('Y-m') . '-01'));
		$query->where('state = 1');

		$this->getDatabase()->setQuery($query);

		$data['month'] = $this->getDatabase()->loadAssoc();

		$date->modify('last monday');
		$query = $this->getDatabase()->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('book_date > ' . $this->getDatabase()->quote($date->format('Y-m-d')));
		$query->where('state = 1');

		$this->getDatabase()->setQuery($query);

		$data['week'] = $this->getDatabase()->loadAssoc();

		$query = $this->getDatabase()->getQuery(true);
		$query->select('count(id) as total, sum(price) as price');
		$query->from('#__dpcalendar_bookings');
		$query->where('state != 1');

		$this->getDatabase()->setQuery($query);

		$data['notactive'] = $this->getDatabase()->loadAssoc();

		return $data;
	}

	public function getTotalTaxRates(): int
	{
		$query = $this->getDatabase()->getQuery(true);
		$query->select('count(id) as total');
		$query->from('#__dpcalendar_taxrates');

		$this->getDatabase()->setQuery($query);

		return $this->getDatabase()->loadResult() ?: 0;
	}

	public function getExtensionsWithDifferentVersion(string $version): array
	{
		$query = $this->getDatabase()->getQuery(true);
		$query->select('*');
		$query->from("#__extensions where name like '%dpcalendar%'");

		$this->getDatabase()->setQuery($query);

		$extensions = [];
		foreach ($this->getDatabase()->loadObjectList() as $extension) {
			$manifest = json_decode((string)$extension->manifest_cache) ?: new \stdClass();
			if ($version === $manifest->version) {
				continue;
			}

			$extension->version = $manifest->version;

			$extensions[] = $extension;
		}

		return $extensions;
	}
}
