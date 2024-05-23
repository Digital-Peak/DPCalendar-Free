<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Plugin;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendar;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Extension\DPCalendarComponent;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Table\ExtcalendarTable;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;

/**
 * This is the base class for the DPCalendar advanced sync plugins.
 */
abstract class SyncPlugin extends DPCalendarPlugin implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	/**
	 * Getting the sync token to determine if a full sync needs to be done.
	 */
	protected function getSyncToken(ExternalCalendarInterface $calendar): string
	{
		$uri = str_replace('webcal://', 'https://', (string)$calendar->getParams()->get('uri'));

		if ($uri === '' || $uri === '0') {
			return (string)random_int(0, mt_getrandmax());
		}

		$internal = !filter_var($uri, FILTER_VALIDATE_URL);
		if ($internal && !str_starts_with($uri, '/')) {
			$uri = JPATH_ROOT . '/' . $uri;
		}

		if ($internal) {
			return (string)(filemtime($uri) ?: random_int(0, mt_getrandmax()));
		}

		$http     = HttpFactory::getHttp();
		$response = $http->head($uri);

		if (array_key_exists('ETag', $response->headers)) {
			return $response->headers['ETag'];
		}

		if (array_key_exists('Last-Modified', $response->headers)) {
			return $response->headers['Last-Modified'];
		}

		return (string)random_int(0, mt_getrandmax());
	}

	/**
	 * Syncs the events of the given calendar.
	 * If the force flag is set, then the caching will be ignored.
	 */
	private function sync(ExternalCalendar $calendar, bool $force = false): void
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return;
		}

		$component = $app->bootComponent('dpcalendar');
		if (!$component instanceof DPCalendarComponent) {
			return;
		}

		$calendarId = str_replace($this->identifier . '-', '', $calendar->getId());
		$db         = $this->getDatabase();

		// Defining the last sync date
		$syncDate = $calendar->getSyncDate();
		if ($syncDate !== null && $syncDate !== '' && $syncDate !== '0') {
			$syncDate = DPCalendarHelper::getDate($syncDate);
		}

		// If the last sync is younger than the maximum cache time, return
		if (!$force && $syncDate && ($syncDate->format('U') + $this->params->get('cache_time', 900) >= DPCalendarHelper::getDate()->format('U'))) {
			return;
		}

		// Remove the script time limit.
		@set_time_limit(0);

		// Update the extcalendar table with the new sync information
		$extCalendarTable = $component->getMVCFactory()->createTable('Extcalendar', 'Administrator');
		$extCalendarTable->load(
			[
				'plugin' => str_replace('dpcalendar_', '', $this->_name),
				'id'     => str_replace($this->identifier . '-', '', $calendar->getId())
			]
		);

		if (!$extCalendarTable->id) {
			return;
		}

		$extCalendarTable->sync_date = DPCalendarHelper::getDate()->toSql();
		$extCalendarTable->store();

		$this->extCalendarsCache = null;

		$syncToken = 1;
		if ($calendar->getSyncToken() !== null) {
			$syncToken = $this->getSyncToken($calendar);
			if ($syncToken === $calendar->getSyncToken()) {
				return;
			}
		}

		// Fetching the events to sync
		$syncDateStart = DPCalendarHelper::getDate();
		$syncDateStart->modify($this->params->get('sync_start', '-3 year'));

		// Defining the parameters
		$options = new Registry();
		$options->set('expand', false);

		$syncEnd = DPCalendarHelper::getDate();
		$syncEnd->modify($this->params->get('sync_end', '+3 year'));

		// If there are deleted events in the external calendar system we will detect them when publish down is set
		$db->setQuery('update #__dpcalendar_events set publish_down = now() where catid = ' . $db->quote($calendar->getId()));
		$db->execute();

		$foundEvents     = [];
		$processedEvents = [];
		while (true) {
			// Fetching in steps to safe memory
			$syncDateEnd = clone $syncDateStart;
			$syncDateEnd->modify('+' . $this->params->get('sync_steps', '1 year'));

			$events = $this->fetchEvents($calendarId, $options, $syncDateStart, $syncDateEnd);
			foreach ($events as $event) {
				// Check if we have processed the event already, mainly on recurring events
				if (array_key_exists($event->id, $processedEvents)) {
					continue;
				}

				$processedEvents[$event->id] = $event;

				// Saving the id as reference
				$event->id    = null;
				$event->alias = null;

				// Find an existing event with the same keys
				$table = $component->getMVCFactory()->createTable('Event', 'Administrator');

				$keys = ['catid' => $calendar->getId(), 'uid' => $event->uid];
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
				if ($syncDate && $event->modified && $syncDate->format('U') >= DPCalendarHelper::getDate($event->modified)->format('U')) {
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
				$model = $component->getMVCFactory()->createModel('Event', 'Administrator');
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

		if ($foundEvents !== []) {
			$db->setQuery(
				'update #__dpcalendar_events set publish_down = null where id in (' . implode(',', $foundEvents) . ') or original_id in (' . implode(',', $foundEvents) . ')'
			);
			$db->execute();
		}

		// Delete the events which are externally deleted
		$db->setQuery('delete from #__dpcalendar_events where catid = ' . $db->quote($calendar->getId()) . ' and publish_down is not null');
		$db->execute();

		$extCalendarTable->sync_date  = DPCalendarHelper::getDate()->toSql();
		$extCalendarTable->sync_token = (string)$syncToken;
		$extCalendarTable->store();
	}

	public function onEventsFetch(string $calendarId, Date $startDate = null, Date $endDate = null, Registry $options = null): array
	{
		if ($this->params->get('cache', 1) == 2) {
			return [];
		}

		return parent::onEventsFetch($calendarId, $startDate, $endDate, $options);
	}

	/**
	 * Function to force a sync.
	 */
	public function onEventsSync(?string $plugin = null, ?array $ids = []): void
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
			if ($ids && !in_array(str_replace($this->identifier . '-', '', (string)$calendar->getId()), $ids)) {
				continue;
			}
			$this->sync($calendar, true);
		}
	}

	public function onCalendarAfterDelete(ExtcalendarTable $calendar): void
	{
		if ('dpcalendar_' . $calendar->plugin != $this->_name) {
			return;
		}

		// Clean the Joomla cache
		$cache = $this->getCacheControllerFactory()->createCacheController('callback', ['defaultgroup' => 'plg_dpcalendar_' . $calendar->plugin]);
		if (!$cache->clean()) {
			return;
		}

		$db = $this->getDatabase();
		$db->setQuery('delete from #__dpcalendar_events where catid = ' . $db->quote($this->identifier . '-' . $calendar->id));
		$db->execute();
	}
}
