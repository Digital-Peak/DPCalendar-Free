<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Sabre\CalDAV\Backend;

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\InternalCalendar;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Table\EventTable;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;
use Sabre\CalDAV\Backend\PDO;
use Sabre\CalDAV\Plugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Property\ICalendar\DateTime;
use Sabre\VObject\Reader;

Log::addLogger(['text_file' => 'com_dpcalendar.caldav.backend.php'], Log::WARNING, ['com_dpcalendar-caldav-backend']);

class DPCalendar extends PDO
{
	use DatabaseAwareTrait;

	public function __construct(\PDO $pdo, private CMSApplicationInterface $app)
	{
		parent::__construct($pdo);
	}

	public function getCalendarsForUser($principalUri)
	{
		$calendars = parent::getCalendarsForUser($principalUri);

		$user = $this->app->getIdentity();
		if (!$user instanceof User) {
			return $calendars;
		}

		// The calendar instance to get the calendars from
		$cal = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar('root');

		// Check if we are a guest
		if ($user->guest !== 0 && $cat = $this->app->bootComponent('dpcalendar')->getCategory(['access' => false])->get('root')) {
			// Get the calendar and ignoring the access flag, this is needed on authentication
			$cal = new InternalCalendar($cat, $user);
		}

		if (!$cal instanceof CalendarInterface) {
			return $calendars;
		}

		foreach ($cal->getChildren(true) as $calendar) {
			$writePermission = $user->authorise('core.edit', 'com_dpcalendar.category.' . $calendar->getId()) &&
				$user->authorise('core.delete', 'com_dpcalendar.category.' . $calendar->getId());

			$params      = new Registry($calendar->getParams());
			$calendars[] = [
				'id'                                                          => 'dp-' . $calendar->getId(),
				'uri'                                                         => 'dp-' . $calendar->getId(),
				'principaluri'                                                => $principalUri,
				'{' . Plugin::NS_CALENDARSERVER . '}getctag'                  => $params->get('etag', 1),
				'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet([
					'VEVENT',
					'VTODO'
				]),
				'{DAV:}displayname'                                   => $calendar->getTitle(),
				'{urn:ietf:params:xml:ns:caldav}calendar-description' => $calendar->getDescription(),
				'{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => '',
				'{http://apple.com/ns/ical/}calendar-order'           => 1,
				'{http://apple.com/ns/ical/}calendar-color'           => $params->get('color', '3366CC')
			];
		}

		return $calendars;
	}

	public function getMultipleCalendarObjects($calendarId, array $uris)
	{
		if (!\is_string($calendarId) || !str_contains($calendarId, 'dp-')) {
			return parent::getMultipleCalendarObjects($calendarId, $uris);
		}

		$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
		$model->setState('category.id', str_replace('dp-', '', $calendarId));
		$model->setState('category.recursive', false);
		$model->setState('list.limit', 10000);
		$model->setState('filter.ongoing', true);
		$model->setState('filter.state', 1);
		$model->setState('filter.language', $this->app->getLanguage()->getTag());
		$model->setState('filter.publish_date', true);
		$model->setState('list.start-date', '0');
		$model->setState('list.end-date', DPCalendarHelper::getDate(self::MAX_DATE)->format('U'));
		$model->setState('list.ordering', 'start_date');
		$model->setState('list.direction', 'asc');
		$model->setState('filter.expand', false);

		$data = [];
		foreach ($model->getItems() as $event) {
			if (\array_key_exists($event->uid, $data) || $event->original_id > 0) {
				continue;
			}
			$data[$event->uid] = $this->toSabreArray($event);
		}
		Log::add('Getting multiple calendar objects ' . implode(',', $uris) . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

		return $data;
	}

	public function getCalendarObject($calendarId, $objectUri)
	{
		if (!\is_string($calendarId) || !str_contains($calendarId, 'dp-')) {
			return parent::getCalendarObject([$calendarId, ''], $objectUri);
		}

		$event = $this->getTable();
		$event->load(['uid' => $objectUri]);

		// If we hit an instance, load the original event
		if ($event->original_id > 0) {
			$event->load(['id' => $event->original_id]);
		}

		if (empty($event->id)) {
			return parent::getCalendarObject([$calendarId, ''], $objectUri);
		}

		// The event needs to be loaded through the model to get
		// locations, tags, etc.
		$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Site', ['ignore_request' => true]);
		$event = $model->getItem($event->id);
		Log::add('Getting calendar object ' . $objectUri . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

		return $this->toSabreArray($event ?: new \stdClass());
	}

	public function getCalendarObjects($calendarId)
	{
		if (!\is_string($calendarId) || !str_contains($calendarId, 'dp-')) {
			return parent::getCalendarObjects($calendarId);
		}

		$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
		$model->setState('category.id', str_replace('dp-', '', $calendarId));
		$model->setState('category.recursive', false);
		$model->setState('list.limit', 10000);
		$model->setState('filter.ongoing', true);
		$model->setState('filter.state', 1);
		$model->setState('filter.language', $this->app->getLanguage()->getTag());
		$model->setState('filter.publish_date', true);
		$model->setState('list.start-date', '0');
		$model->setState('list.end-date', DPCalendarHelper::getDate(self::MAX_DATE)->format('U'));
		$model->setState('list.ordering', 'start_date');
		$model->setState('list.direction', 'asc');
		$model->setState('filter.expand', false);

		$data = [];
		foreach ($model->getItems() as $event) {
			if (\array_key_exists($event->uid, $data) || $event->original_id > 0) {
				continue;
			}
			$data[$event->uid] = $this->toSabreArray($event);
		}

		return $data;
	}

	public function calendarQuery($calendarId, array $filters)
	{
		if (!\is_string($calendarId) || !str_contains($calendarId, 'dp-')) {
			return parent::calendarQuery($calendarId, $filters);
		}

		$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.limit', 1000);
		$model->setState('category.id', str_replace('dp-', '', $calendarId));
		$model->setState('category.recursive', true);
		$model->setState('filter.ongoing', 1);
		$model->setState('filter.state', 1);

		if ((is_countable($filters['comp-filters']) ? \count($filters['comp-filters']) : 0) > 0 && !$filters['comp-filters'][0]['is-not-defined']) {
			$componentType = $filters['comp-filters'][0]['name'];

			if ($componentType == 'VEVENT' && !empty($filters['comp-filters'][0]['time-range'])) {
				$timeRange = $filters['comp-filters'][0]['time-range'];
				if (\is_array($timeRange) && \array_key_exists('start', $timeRange) && !empty($timeRange['start'])) {
					$model->setState('list.start-date', $timeRange['start']->getTimeStamp());
				}
				if (\is_array($timeRange) && \array_key_exists('end', $timeRange) && !empty($timeRange['end'])) {
					$model->setState('list.end-date', $timeRange['end']->getTimeStamp());
				}
			}
			if ($componentType == 'VEVENT'
				&& !empty($filters['comp-filters'][0]['prop-filters'])
				&& $filters['comp-filters'][0]['prop-filters'][0]['name'] == 'UID'
				&& !empty($filters['comp-filters'][0]['prop-filters'][0]['text-match'])
				&& !empty($filters['comp-filters'][0]['prop-filters'][0]['text-match']['value'])) {
				$model->setState('filter.search', 'uid:' . $filters['comp-filters'][0]['prop-filters'][0]['text-match']['value']);
				$model->setState('list.start-date', 0);
			}
		}

		$data = [];
		foreach ($model->getItems() as $event) {
			if (!$this->validateFilterForObject(['uri' => $event->uid, 'calendarid' => $calendarId], $filters)) {
				continue;
			}
			$data[$event->uid] = $event->uid;
		}

		return $data;
	}

	public function createCalendarObject($calendarId, $objectUri, $calendarData)
	{
		if (!\is_string($calendarId) || !str_contains($calendarId, 'dp-')) {
			return parent::createCalendarObject($calendarId, $objectUri, $calendarData);
		}

		Log::add('Creating calendar object ' . $objectUri . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

		$model    = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator');
		$calendar = $model->getCalendar(str_replace('dp-', '', $calendarId));
		if (!$calendar instanceof CalendarInterface || !$calendar->canCreate()) {
			Log::add('No permission to create ' . $objectUri . ' on calendar ' . $calendarId, Log::WARNING, 'com_dpcalendar-caldav-backend');
			throw new Forbidden();
		}

		$event = $this->getTable();
		$cal   = Reader::read($calendarData);
		if (empty($cal->VEVENT)) {
			return parent::updateCalendarObject($calendarId, $objectUri, $calendarData);
		}

		/** @var VEvent $vEvent */
		$vEvent = $cal->VEVENT;

		$event->alias = ApplicationHelper::stringURLSafe($vEvent->SUMMARY !== null ? $vEvent->SUMMARY->getValue() : '');
		$event->catid = str_replace('dp-', '', $calendarId);
		$event->state = 1;
		$event->uid   = $objectUri;

		$this->merge($event, $vEvent);
		$model->increaseEtag($event->catid);

		return null;
	}

	public function updateCalendarObject($calendarId, $objectUri, $calendarData)
	{
		if (!\is_string($calendarId) || !str_contains($calendarId, 'dp-')) {
			return parent::updateCalendarObject($calendarId, $objectUri, $calendarData);
		}

		Log::add('Updating calendar object ' . $objectUri . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

		$calendar = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar(str_replace('dp-', '', $calendarId));
		if (!$calendar instanceof CalendarInterface || !$calendar->canEdit()) {
			Log::add('No permission to update ' . $objectUri . ' on calendar ' . $calendarId, Log::WARNING, 'com_dpcalendar-caldav-backend');
			throw new Forbidden();
		}

		$event = $this->getTable();
		$event->load(['uid' => $objectUri]);

		$obj = Reader::read($calendarData);
		if (empty($obj->VEVENT)) {
			return parent::updateCalendarObject($calendarId, $objectUri, $calendarData);
		}

		/** @var VEvent $vEvent */
		$vEvent = $obj->VEVENT;

		if ($event->original_id == '0') {
			$this->merge($event, $vEvent);
			$this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->increaseEtag($event->catid);

			return null;
		}

		foreach ($vEvent as $vEvent) {
			if ($vEvent->RRULE && $vEvent->RRULE->getValue()) {
				$this->merge($event, $vEvent);
				// We need to sleep here that modified instances have another modified date, nasty
				sleep(1);
			}
		}

		$db = $this->getDatabase();
		$db->setQuery('select * from #__dpcalendar_events where original_id = ' . (int)$event->id);

		$children = $db->loadObjectList();

		foreach ($vEvent as $vEvent) {
			if (empty($vEvent->{'RECURRENCE-ID'})) {
				continue;
			}

			$startDate = (string)$vEvent->{'RECURRENCE-ID'}->getValue();
			foreach ($children as $child) {
				if ($child->recurrence_id == $startDate) {
					$this->merge($child, $vEvent);
					break;
				}
			}
		}

		$this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->increaseEtag($event->catid);

		return null;
	}

	public function deleteCalendarObject($calendarId, $objectUri): void
	{
		$user = $this->app->getIdentity();
		if (!$user instanceof User) {
			parent::deleteCalendarObject($calendarId, $objectUri);

			return;
		}

		if (\is_string($calendarId) && str_contains($calendarId, 'dp-')) {
			Log::add('Deleting calendar object ' . $objectUri . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

			$calendar = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar(str_replace('dp-', '', $calendarId));
			if (!$calendar instanceof CalendarInterface || (!$calendar->canDelete() && !$calendar->canEditOwn())) {
				Log::add('No permission to delete ' . $objectUri . ' on calendar ' . $calendarId, Log::WARNING, 'com_dpcalendar-caldav-backend');
				throw new Forbidden();
			}

			$event = $this->getTable();
			$event->load(['uid' => $objectUri]);

			if (!$calendar->canDelete() && $event->created_by != $user->id) {
				Log::add('No permission to delete ' . $objectUri . ' on calendar ' . $calendarId . ' because not the owner', Log::WARNING, 'com_dpcalendar-caldav-backend');
				throw new Forbidden();
			}

			if ($event->checked_out != 0 && $event->checked_out == $user->id) {
				Log::add('Event ' . $objectUri . ' on calendar ' . $calendarId . ' is checked out', Log::WARNING, 'com_dpcalendar-caldav-backend');
				throw new Forbidden();
			}

			$event->state = -2;
			$event->store();
			$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel(
				'Form',
				'Site',
				['event_before_delete' => 'nooperationtocatch', 'event_after_delete' => 'nooperationtocatch']
			);
			$id = [$event->id];
			$model->delete($id);

			if ($model->getError()) {
				throw new BadRequest('Error happened deleting the event: ' . $model->getError());
			}

			$this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->increaseEtag(str_replace('dp-', '', $calendarId));

			return;
		}

		parent::deleteCalendarObject($calendarId, $objectUri);
	}

	public function updateCalendar($calendarId, PropPatch $propPatch): void
	{
		if (\is_string($calendarId) && !str_contains($calendarId, 'dp-')) {
			Log::add('Update calendar ' . $calendarId . 'with propatch', Log::INFO, 'com_dpcalendar-caldav-backend');
			$this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->increaseEtag(str_replace('dp-', '', $calendarId));

			return;
		}

		parent::updateCalendar($calendarId, $propPatch);
	}

	private function getTable(): EventTable
	{
		return $this->app->bootComponent('dpcalendar')->getMVCFactory()->createTable('Event', 'Administrator');
	}

	private function merge(\stdClass $dpEvent, VEvent $vEvent): void
	{
		if (!$vEvent->DTSTART instanceof DateTime) {
			return;
		}

		$dpEvent->title = empty($vEvent->SUMMARY) ? '(no title)' : $vEvent->SUMMARY->getValue();

		if (!empty($vEvent->DESCRIPTION) && $vEvent->DESCRIPTION !== null) {
			$dpEvent->description = $vEvent->DESCRIPTION->getValue();
		}
		$dpEvent->all_day = \strlen($vEvent->DTSTART->getValue()) > 10 ? 0 : 1;

		$start = $vEvent->DTSTART->getDateTime() ;
		$start = $dpEvent->all_day !== 0 ? $start->setTime(0, 0, 0) : $start->setTimezone(new \DateTimeZone('UTC'));

		$dpEvent->start_date = $start->format($this->getDatabase()->getDateFormat());

		$end = $vEvent->DTEND instanceof DateTime ? $vEvent->DTEND->getDateTime() : new \DateTime();
		if ($dpEvent->all_day !== 0) {
			$end = $end->setTime(0, 0, 0);
			$end = $end->modify('-1 day');
		} else {
			$end = $end->setTimezone(new \DateTimeZone('UTC'));
		}
		$dpEvent->end_date = $end->format($this->getDatabase()->getDateFormat());

		/*
		 * Most CalDAV clients do not support this attribute, means it will
		 * revert the description when updating a native DPCalendar event.
		 * if (!empty($vEvent->{'X-ALT-DESC'}) && $vEvent->{'X-ALT-DESC'}->getValue()) {
		 *     $dpEvent->description = $vEvent->{'X-ALT-DESC'}->getValue();
		 * }
		 */
		if (!empty($vEvent->{'LAST-MODIFIED'}) && $vEvent->{'LAST-MODIFIED'}->getDateTime()) {
			$dpEvent->modified = $vEvent->{'LAST-MODIFIED'}->getDateTime()->format('Y-m-d H:i:s');
		}
		if (!empty($vEvent->{'X-COLOR'}) && $vEvent->{'X-COLOR'}->getValue()) {
			$dpEvent->color = $vEvent->{'X-COLOR'}->getValue();
		}
		if (!empty($vEvent->{'X-URL'}) && $vEvent->{'X-URL'}->getValue()) {
			$dpEvent->url = $vEvent->{'X-URL'}->getValue();
		}
		if (!empty($vEvent->RRULE) && $vEvent->RRULE->getValue()) {
			$dpEvent->rrule = $vEvent->RRULE->getValue();
		}
		if (!empty($vEvent->LOCATION) && $vEvent->LOCATION !== null && $vEvent->LOCATION->getValue()) {
			$locationString = $vEvent->LOCATION->getValue();

			// The ical creator escapes , and ; so we need to turn them back
			$locationString = str_replace('\,', ',', (string)$locationString);
			$locationString = str_replace('\;', ';', $locationString);

			$location = null;
			if (property_exists($vEvent, 'GEO') && $vEvent->GEO !== null && $vEvent->GEO->getValue()) {
				$parts = explode(';', (string)$vEvent->GEO->getValue());
				if (\count($parts) == 2) {
					$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
					$model->getState();
					$model->setState('list.limit', 1);
					$model->setState('filter.latitude', $parts[0]);
					$model->setState('filter.longitude', $parts[1]);

					$locations = $model->getItems();
					if (!empty($locations)) {
						$location = reset($locations);
					}
				}
			}

			if (!$location) {
				$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
				$model->getState();
				$model->setState('list.limit', 10000);

				$locations = $model->getItems();
				foreach ($locations as $l) {
					if ($l->title == $locationString || $l->alias == $locationString || $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format($l) == $locationString) {
						$location = $l;
						break;
					}
				}
				if (!$location) {
					$location = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($locationString);
				}
			}
			if ($location) {
				$dpEvent->location_ids = [$location->id];
			}
		}

		$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel(
			'Form',
			'Site',
			['event_before_save' => 'nooperationtocatch', 'event_after_save' => 'nooperationtocatch']
		);
		$model->getState();

		// Unset capacity, otherwise it will default to null which is unlimited, but we want the default value
		$data             = get_object_vars($dpEvent);
		$data['capacity'] = DPCalendarHelper::getComponentParameter('event_form_capacity', '0');

		$model->save($data);

		if ($model->getError()) {
			throw new BadRequest('Error happened storing the event: ' . $model->getError());
		}
	}

	private function toSabreArray(\stdClass $event): array
	{
		$ical = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ical', 'Administrator')->createIcalFromEvents([$event]);

		return [
			'id'           => $event->id,
			'uri'          => $event->uid,
			'lastmodified' => DPCalendarHelper::getDate($event->modified)->format('U'),
			'calendarid'   => 'dp-' . $event->catid,
			'size'         => \strlen((string)$ical),
			'etag'         => '"' . md5((string)$ical) . '"',
			'calendardata' => $ical
		];
	}
}
