<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modellist');
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

class DPCalendarModelCpanel extends JModelLegacy
{
	public function getEvents($start, $ordering = 'a.start_date', $direction = 'asc')
	{
		$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
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
		$model     = JModelLegacy::getInstance('Calendar', 'DPCalendarModel', ['ignore_request' => true]);
		$calendars = $model->getAllItems();

		JPluginHelper::importPlugin('dpcalendar');
		$tmp = JFactory::getApplication()->triggerEvent('onCalendarsFetch');
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
		$date = \DPCalendar\Helper\DPCalendarHelper::getDate();

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

	public function refreshUpdateSite()
	{
		JLoader::import('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_dpcalendar');

		$dlid = trim($params->get('downloadid', ''));
		if (!$dlid) {
			return;
		}

		// If I have a valid Download ID I will need to use a non-blank extra_query in Joomla! 3.2+
		$extraQuery = null;
		if (preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid)) {
			$extraQuery = 'dlid=' . $dlid;
		}

		// Create the update site definition we want to store to the database
		$updateSite = ['enabled' => 1, 'last_check_timestamp' => 0, 'extra_query' => $extraQuery];

		$db = $this->getDbo();

		// Get the extension ID to ourselves
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('package'))
			->where($db->qn('element') . ' = ' . $db->q('pkg_dpcalendar'));
		$db->setQuery($query);

		$extensionId = $db->loadResult();
		if (empty($extensionId)) {
			return;
		}

		// Get the update sites for our extension
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extensionId));
		$db->setQuery($query);

		$updateSiteIDs = $db->loadColumn(0);
		if (!count($updateSiteIDs)) {
			return;
		}

		// Loop through all update sites
		foreach ($updateSiteIDs as $id) {
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__update_sites'))
				->where($db->qn('update_site_id') . ' = ' . $db->q($id));
			$db->setQuery($query);

			$site = $db->loadObject();
			if ($site->extra_query == $updateSite['extra_query']) {
				continue;
			}

			$updateSite['update_site_id'] = $id;
			$newSite                      = (object)$updateSite;
			$db->updateObject('#__update_sites', $newSite, 'update_site_id', true);
		}
	}
}
