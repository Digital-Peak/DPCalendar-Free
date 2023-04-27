<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Plugin;

defined('_JEXEC') or die();

use DPCalendarHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

/**
 * This is the base class for the DPCalendar advanced sync plugins.
 */
abstract class SyncPlugin extends DPCalendarPlugin
{
	/**
	 * Getting the sync token to determine if a full sync needs to be done.
	 *
	 * @param \stdClass $calendar
	 *
	 * @return boolean
	 */
	protected function getSyncToken($calendar)
	{
		$uri = str_replace('webcal://', 'https://', $calendar->params->get('uri'));

		if (!$uri) {
			return rand();
		}

		$internal = !filter_var($uri, FILTER_VALIDATE_URL);
		if ($internal && strpos($uri, '/') !== 0) {
			$uri = JPATH_ROOT . '/' . $uri;
		}

		if ($internal) {
			return filemtime($uri) ?: rand();
		}

		$http     = \JHttpFactory::getHttp();
		$response = $http->head($uri);

		if (key_exists('ETag', $response->headers)) {
			return $response->headers['ETag'];
		}

		if (key_exists('Last-Modified', $response->headers)) {
			return $response->headers['Last-Modified'];
		}

		return rand();
	}

	/**
	 * Syncs the events of the given calendar.
	 * If the force flag is set, then the caching will be ignored.
	 *
	 * @param \stdClass $calendar
	 * @param boolean   $force
	 */
	private function sync($calendar, $force = false)
	{
		$calendarId = str_replace($this->identifier . '-', '', $calendar->id);
		$db         = Factory::getDbo();

		// Defining the last sync date
		$syncDate = $calendar->sync_date;
		if ($syncDate) {
			$syncDate = DPCalendarHelper::getDate($syncDate);
		}

		// If the last sync is younger than the maximum cache time, return
		if (!$force && $syncDate && ($syncDate->format('U') + $this->params->get('cache_time', 900) >= \DPCalendarHelper::getDate()->format('U'))) {
			return;
		}

		// Remove the script time limit.
		@set_time_limit(0);

		// Update the extcalendar table with the new sync information
		$extCalendarTable = Table::getInstance('Extcalendar', 'DPCalendarTable');
		$extCalendarTable->load(
			[
				'plugin' => str_replace('dpcalendar_', '', $this->_name),
				'id'     => str_replace($this->identifier . '-', '', $calendar->id)
			]
		);

		if (!$extCalendarTable->id) {
			return;
		}

		$extCalendarTable->sync_date = DPCalendarHelper::getDate()->toSql();
		$extCalendarTable->store();

		$this->extCalendarsCache = null;

		$syncToken = 1;
		if ($calendar->sync_token !== null) {
			$syncToken = $this->getSyncToken($calendar);
			if ($syncToken == $calendar->sync_token) {
				return;
			}
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

		// Fetching the events to sync
		$syncDateStart = \DPCalendarHelper::getDate();
		$syncDateStart->modify($this->params->get('sync_start', '-3 year'));

		// Defining the parameters
		$options = new Registry();
		$options->set('expand', false);

		$syncEnd = \DPCalendarHelper::getDate();
		$syncEnd->modify($this->params->get('sync_end', '+3 year'));

		// If there are deleted events in the external calendar system we will detect them when publish down is set
		$db->setQuery('update #__dpcalendar_events set publish_down = now() where catid = ' . $db->q($calendar->id));
		$db->execute();

		$foundEvents     = [];
		$processedEvents = [];
		while (true) {
			// Fetching in steps to safe memory
			$syncDateEnd = clone $syncDateStart;
			$syncDateEnd->modify('+' . $this->params->get('sync_steps', '1 year'));

			$events = $this->fetchEvents($calendarId, $syncDateStart, $syncDateEnd, $options);
			foreach ($events as $index => $event) {
				// Check if we have processed the event already, mainly on recurring events
				if (array_key_exists($event->id, $processedEvents)) {
					continue;
				}

				$processedEvents[$event->id] = $event;

				// Saving the id as reference
				$event->id    = null;
				$event->alias = null;

				// Find an existing event with the same keys
				$table = Table::getInstance('Event', 'DPCalendarTable');

				$keys = ['catid' => $calendar->id, 'uid' => $event->uid];
				if ($event->recurrence_id) {
					// Search the parent
					$table->load($keys);
					$event->original_id = $table->id;
					$table->reset();

					$keys['recurrence_id'] = $event->recurrence_id;
				}
				if ($event->original_id < 1) {
					$keys['original_id'] = $event->original_id;
				}
				$table->load($keys);

				// Check if the event was edited since last sync
				if ($syncDate && $event->modified && $syncDate->format('U') >= \DPCalendarHelper::getDate($event->modified)->format('U')) {
					// Trashed events can be delivered separate
					if ($table->id && $table->state == $event->state) {
						$foundEvents[$table->id] = $table->id;
					}
					continue;
				}

				$event->id           = $table->id;
				$event->publish_down = null;

				$event->location_ids = [];
				foreach ($event->locations as $location) {
					$event->location_ids[$location->id] = $location->id;
				}

				// Save the event
				$model = BaseDatabaseModel::getInstance('AdminEvent', 'DPCalendarModel');
				$model->getState();
				if (!$model->save((array)$event)) {
					$this->log($model->getError());
				}
			}

			$syncDateStart->modify('+' . $this->params->get('sync_steps', '1 year'));
			if ($syncDateEnd->format('U') > $syncEnd->format('U')) {
				break;
			}
		}

		if ($foundEvents) {
			$db->setQuery(
				'update #__dpcalendar_events set publish_down = null' .
				' where id in (' . implode(',', $foundEvents) . ') or original_id in (' . implode(',', $foundEvents) . ')'
			);
			$db->execute();
		}

		// Delete the events which are externally deleted
		$db->setQuery('delete from #__dpcalendar_events where catid = ' . $db->q($calendar->id) . ' and publish_down is not null');
		$db->execute();

		if ($extCalendarTable->id) {
			$extCalendarTable->sync_date  = \DPCalendarHelper::getDate()->toSql();
			$extCalendarTable->sync_token = $syncToken;
			$extCalendarTable->store();
		}
	}

	public function onEventsFetch($calendarId, Date $startDate = null, Date $endDate = null, Registry $options = null)
	{
		if ($this->params->get('cache', 1) == 2) {
			return [];
		}

		return parent::onEventsFetch($calendarId, $startDate, $endDate, $options);
	}

	/**
	 * Function to force a sync.
	 */
	public function onEventsSync($plugin = null, $ids = [])
	{
		// Only do a sync when enabled in the plugin
		if ($this->params->get('cache', 1) != 2) {
			return;
		}

		// If only a specific plugins needs to be synced return
		if ($plugin && str_replace('dpcalendar_', '', $this->_name) != $plugin) {
			return;
		}

		// Loop through the calendars to sync
		foreach ($this->fetchCalendars() as $calendar) {
			if ($ids && !in_array(str_replace($this->identifier . '-', '', $calendar->id), $ids)) {
				continue;
			}
			$this->sync($calendar, true);
		}
	}

	public function onCalendarAfterDelete($calendar)
	{
		if ('dpcalendar_' . $calendar->plugin != $this->_name) {
			return;
		}

		// Clean the Joomla cache
		$cache = Factory::getCache('plg_dpcalendar_' . $calendar->plugin);
		if (!$cache->clean()) {
			return false;
		}

		$db = Factory::getDbo();
		$db->setQuery('delete from #__dpcalendar_events where catid = ' . $db->q($this->identifier . '-' . $calendar->id));
		$db->execute();
	}
}
