<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Sabre\CalDAV\Backend;

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\Ical;
use DPCalendar\Helper\Location;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Sabre\CalDAV\Backend\PDO;
use Sabre\CalDAV\Plugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\VObject\Reader;

\JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

Log::addLogger(['text_file' => 'com_dpcalendar.caldav.backend.php'], Log::WARNING, 'com_dpcalendar-caldav-backend');

class DPCalendar extends PDO
{
	public function getCalendarsForUser($principalUri)
	{
		$calendars = parent::getCalendarsForUser($principalUri);

		$user = Factory::getUser();

		// The calendar instance to get the calendars from
		$cal = DPCalendarHelper::getCalendar('root');

		// Check if we are a guest
		if ($user->guest) {
			// Get the calendar and ignoring the access flag, this is needed on authentication
			$cal = Categories::getInstance('DPCalendar', ['access' => false])->get('root');
		}

		foreach ($cal->getChildren(true) as $calendar) {
			$writePermission = $user->authorise('core.edit', 'com_dpcalendar.category.' . $calendar->id) &&
				$user->authorise('core.delete', 'com_dpcalendar.category.' . $calendar->id);

			$params      = new Registry($calendar->params);
			$calendars[] = [
				'id'                                                          => 'dp-' . $calendar->id,
				'uri'                                                         => 'dp-' . $calendar->id,
				'principaluri'                                                => $principalUri,
				'{' . Plugin::NS_CALENDARSERVER . '}getctag'                  => $params->get('etag', 1),
				'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet([
					'VEVENT',
					'VTODO'
				]),
				'{DAV:}displayname'                                           => $calendar->title,
				'{urn:ietf:params:xml:ns:caldav}calendar-description'         => $calendar->description,
				'{urn:ietf:params:xml:ns:caldav}calendar-timezone'            => '',
				'{http://apple.com/ns/ical/}calendar-order'                   => 1,
				'{http://apple.com/ns/ical/}calendar-color'                   => $params->get('color', '3366CC')
			];
		}

		return $calendars;
	}

	public function getMultipleCalendarObjects($calendarId, array $uris)
	{
		if (!is_string($calendarId) || strpos($calendarId, 'dp-') === false) {
			return parent::getMultipleCalendarObjects($calendarId, $uris);
		}

		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('category.id', str_replace('dp-', '', $calendarId));
		$model->setState('category.recursive', false);
		$model->setState('list.limit', 10000);
		$model->setState('filter.ongoing', true);
		$model->setState('filter.state', 1);
		$model->setState('filter.language', Factory::getLanguage()->getTag());
		$model->setState('filter.publish_date', true);
		$model->setState('list.start-date', '0');
		$model->setState('list.end-date', DPCalendarHelper::getDate(self::MAX_DATE)->format('U'));
		$model->setState('list.ordering', 'start_date');
		$model->setState('list.direction', 'asc');
		$model->setState('filter.expand', false);

		$data = [];
		foreach ($model->getItems() as $event) {
			if (key_exists($event->uid, $data) || $event->original_id > 0) {
				continue;
			}
			$data[$event->uid] = $this->toSabreArray($event);
		}
		Log::add('Getting multiple calendar objects ' . implode(',', $uris) . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

		return $data;
	}

	public function getCalendarObject($calendarId, $objectUri)
	{
		if (!is_string($calendarId) || strpos($calendarId, 'dp-') === false) {
			return parent::getCalendarObject($calendarId, $objectUri);
		}

		$event = $this->getTable();
		$event->load(['uid' => $objectUri]);

		// If we hit an instance, load the original event
		if ($event->original_id > 0) {
			$event->load(['id' => $event->original_id]);
		}

		if (!empty($event->id)) {
			// The event needs to be loaded through the model to get
			// locations, tags, etc.
			$model = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel', ['ignore_request' => true]);
			$event = $model->getItem($event->id);
			Log::add('Getting calendar object ' . $objectUri . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

			return $this->toSabreArray($event);
		}
	}

	public function getCalendarObjects($calendarId)
	{
		if (!is_string($calendarId) || strpos($calendarId, 'dp-') === false) {
			return parent::getCalendarObjects($calendarId);
		}

		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('category.id', str_replace('dp-', '', $calendarId));
		$model->setState('category.recursive', false);
		$model->setState('list.limit', 10000);
		$model->setState('filter.ongoing', true);
		$model->setState('filter.state', 1);
		$model->setState('filter.language', Factory::getLanguage()->getTag());
		$model->setState('filter.publish_date', true);
		$model->setState('list.start-date', '0');
		$model->setState('list.end-date', DPCalendarHelper::getDate(self::MAX_DATE)->format('U'));
		$model->setState('list.ordering', 'start_date');
		$model->setState('list.direction', 'asc');
		$model->setState('filter.expand', false);

		$data = [];
		foreach ($model->getItems() as $event) {
			if (key_exists($event->uid, $data) || $event->original_id > 0) {
				continue;
			}
			$data[$event->uid] = $this->toSabreArray($event);
		}

		return $data;
	}

	public function calendarQuery($calendarId, array $filters)
	{
		if (!is_string($calendarId) || strpos($calendarId, 'dp-') === false) {
			return parent::calendarQuery($calendarId, $filters);
		}

		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.limit', 1000);
		$model->setState('category.id', str_replace('dp-', '', $calendarId));
		$model->setState('category.recursive', true);
		$model->setState('filter.ongoing', 1);
		$model->setState('filter.state', 1);

		if (count($filters['comp-filters']) > 0 && !$filters['comp-filters'][0]['is-not-defined']) {
			$componentType = $filters['comp-filters'][0]['name'];

			if ($componentType == 'VEVENT' && !empty($filters['comp-filters'][0]['time-range'])) {
				$timeRange = $filters['comp-filters'][0]['time-range'];
				if (is_array($timeRange) && key_exists('start', $timeRange) && !empty($timeRange['start'])) {
					$model->setState('list.start-date', $timeRange['start']->getTimeStamp());
				}
				if (is_array($timeRange) && key_exists('end', $timeRange) && !empty($timeRange['end'])) {
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
		if (!is_string($calendarId) || strpos($calendarId, 'dp-') === false) {
			return parent::createCalendarObject($calendarId, $objectUri, $calendarData);
		}

		Log::add('Creating calendar object ' . $objectUri . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

		$calendar = DPCalendarHelper::getCalendar(str_replace('dp-', '', $calendarId));
		if (!$calendar || !$calendar->canCreate) {
			Log::add('No permission to create ' . $objectUri . ' on calendar ' . $calendarId, Log::WARNING, 'com_dpcalendar-caldav-backend');
			throw new Forbidden();
		}

		$event        = $this->getTable();
		$vEvent       = Reader::read($calendarData)->VEVENT;
		$event->alias = ApplicationHelper::stringURLSafe($vEvent->SUMMARY->getValue());
		$event->catid = str_replace('dp-', '', $calendarId);
		$event->state = 1;
		$event->uid   = $objectUri;

		$this->merge($event, $vEvent);
		DPCalendarHelper::increaseEtag($event->catid);
	}

	public function updateCalendarObject($calendarId, $objectUri, $calendarData)
	{
		if (!is_string($calendarId) || strpos($calendarId, 'dp-') === false) {
			return parent::updateCalendarObject($calendarId, $objectUri, $calendarData);
		}

		Log::add('Updating calendar object ' . $objectUri . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

		$calendar = DPCalendarHelper::getCalendar(str_replace('dp-', '', $calendarId));
		if (!$calendar || !$calendar->canEdit) {
			Log::add('No permission to update ' . $objectUri . ' on calendar ' . $calendarId, Log::WARNING, 'com_dpcalendar-caldav-backend');
			throw new Forbidden();
		}

		$event = $this->getTable();
		$event->load(['uid' => $objectUri]);
		$obj = Reader::read($calendarData);

		if ($event->original_id == '0') {
			$this->merge($event, $obj->VEVENT);
			DPCalendarHelper::increaseEtag($event->catid);

			return;
		}

		foreach ($obj->VEVENT as $vEvent) {
			if ($vEvent->RRULE && $vEvent->RRULE->getValue()) {
				$this->merge($event, $vEvent);
				// We need to sleep here that modified instances have another modified date, nasty
				sleep(1);
			}
		}

		$db = Factory::getDbo();
		$db->setQuery('select * from #__dpcalendar_events where original_id = ' . $db->quote($event->id));
		$children = $db->loadObjectList('', 'DPCalendarTableEvent');

		foreach ($obj->VEVENT as $vEvent) {
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

		DPCalendarHelper::increaseEtag($event->catid);
	}

	public function deleteCalendarObject($calendarId, $objectUri)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			Log::add('Deleting calendar object ' . $objectUri . ' on calendar ' . $calendarId, Log::INFO, 'com_dpcalendar-caldav-backend');

			$calendar = DPCalendarHelper::getCalendar(str_replace('dp-', '', $calendarId));
			if (!$calendar || (!$calendar->canDelete && !$calendar->canEditOwn)) {
				Log::add('No permission to delete ' . $objectUri . ' on calendar ' . $calendarId, Log::WARNING, 'com_dpcalendar-caldav-backend');
				throw new Forbidden();
			}

			$event = $this->getTable();
			$event->load(['uid' => $objectUri]);

			if (!$calendar->canDelete && $event->created_by != Factory::getUser()->id) {
				Log::add('No permission to delete ' . $objectUri . ' on calendar ' . $calendarId . ' because not the owner', Log::WARNING, 'com_dpcalendar-caldav-backend');
				throw new Forbidden();
			}

			if ($event->checked_out != 0 && $event->checked_out == Factory::getUser()->id) {
				Log::add('Event ' . $objectUri . ' on calendar ' . $calendarId . ' is checked out', Log::WARNING, 'com_dpcalendar-caldav-backend');
				throw new Forbidden();
			}

			$event->state = -2;
			$event->store();
			$model = BaseDatabaseModel::getInstance(
				'Form',
				'DPCalendarModel',
				['event_before_delete' => 'nooperationtocatch', 'event_after_delete' => 'nooperationtocatch']
			);
			$model->delete($event->id);

			if ($model->getError()) {
				throw new BadRequest('Error happened deleting the event: ' . $model->getError());
			}

			DPCalendarHelper::increaseEtag(str_replace('dp-', '', $calendarId));

			return;
		}

		return parent::deleteCalendarObject($calendarId, $objectUri);
	}

	public function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch)
	{
		if (is_string($calendarId) && strpos($calendarId, 'dp-') !== false) {
			Log::add('Update calendar ' . $calendarId . 'with propatch', Log::INFO, 'com_dpcalendar-caldav-backend');
			DPCalendarHelper::increaseEtag(str_replace('dp-', '', $calendarId));

			return;
		}

		return parent::updateCalendar($calendarId, $propPatch);
	}

	private function getTable($type = 'Event')
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');

		return Table::getInstance($type, 'DPCalendarTable');
	}

	private function merge(Table $dpEvent, $vEvent)
	{
		if (isset($vEvent->SUMMARY)) {
			$dpEvent->title = $vEvent->SUMMARY->getValue();
		} else {
			$dpEvent->title = '(no title)';
		}

		if (isset($vEvent->DESCRIPTION)) {
			$dpEvent->description = $vEvent->DESCRIPTION->getValue();
		}
		$dpEvent->all_day = strlen($vEvent->DTSTART->getValue()) > 10 ? 0 : 1;

		$start = $vEvent->DTSTART->getDateTime();
		if ($dpEvent->all_day) {
			$start = $start->setTime(0, 0, 0);
		} else {
			$start = $start->setTimezone(new \DateTimeZone('UTC'));
		}
		$dpEvent->start_date = $start->format(Factory::getDbo()->getDateFormat());

		$end = $vEvent->DTEND->getDateTime();
		if ($dpEvent->all_day) {
			$end = $end->setTime(0, 0, 0);
			$end = $end->modify('-1 day');
		} else {
			$end = $end->setTimezone(new \DateTimeZone('UTC'));
		}
		$dpEvent->end_date = $end->format(Factory::getDbo()->getDateFormat());

		/*
		 * Most CalDAV clients do not support this attribute, means it will
		 * revert the description when updating a native DPCalendar event.
		 * if (isset($vEvent->{'X-ALT-DESC'}) && $vEvent->{'X-ALT-DESC'}->getValue()) {
		 *     $dpEvent->description = $vEvent->{'X-ALT-DESC'}->getValue();
		 * }
		 */
		if (isset($vEvent->{'LAST-MODIFIED'}) && $vEvent->{'LAST-MODIFIED'}->getDateTime()) {
			$dpEvent->modified = $vEvent->{'LAST-MODIFIED'}->getDateTime()->format('Y-m-d H:i:s');
		}
		if (isset($vEvent->{'X-COLOR'}) && $vEvent->{'X-COLOR'}->getValue()) {
			$dpEvent->color = $vEvent->{'X-COLOR'}->getValue();
		}
		if (isset($vEvent->{'X-URL'}) && $vEvent->{'X-URL'}->getValue()) {
			$dpEvent->url = $vEvent->{'X-URL'}->getValue();
		}
		if (isset($vEvent->RRULE) && $vEvent->RRULE->getValue()) {
			$dpEvent->rrule = $vEvent->RRULE->getValue();
		}
		if (isset($vEvent->LOCATION) && $vEvent->LOCATION->getValue()) {
			$locationString = $vEvent->LOCATION->getValue();

			// The ical creator escapes , and ; so we need to turn them back
			$locationString = str_replace('\,', ',', $locationString);
			$locationString = str_replace('\;', ';', $locationString);

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$location = null;
			if (isset($vEvent->GEO) && $vEvent->GEO->getValue()) {
				$parts = explode(';', $vEvent->GEO->getValue());
				if (count($parts) == 2) {
					$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
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
				$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel');
				$model->getState();
				$model->setState('list.limit', 10000);

				$locations = $model->getItems();
				foreach ($locations as $l) {
					if ($l->title == $locationString || $l->alias == $locationString || \DPCalendar\Helper\Location::format($l) == $locationString) {
						$location = $l;
						break;
					}
				}
				if (!$location) {
					$location = Location::get($locationString);
				}
			}
			if ($location) {
				$dpEvent->location_ids = [$location->id];
			}
		}

		$model = BaseDatabaseModel::getInstance(
			'Form',
			'DPCalendarModel',
			['event_before_save' => 'nooperationtocatch', 'event_after_save' => 'nooperationtocatch']
		);
		$model->getState();

		$data = $dpEvent->getProperties();

		// Unset capacity, otherwise it will default to null which is unlimited, but we want the default value
		$data             = $dpEvent->getProperties();
		$data['capacity'] = DPCalendarHelper::getComponentParameter('event_form_capacity', '0');

		$model->save($data);

		if ($model->getError()) {
			throw new BadRequest('Error happened storing the event: ' . $model->getError());
		}
	}

	private function toSabreArray($event)
	{
		$ical = Ical::createIcalFromEvents([$event]);
		$data = [
			'id'           => $event->id,
			'uri'          => $event->uid,
			'lastmodified' => DPCalendarHelper::getDate($event->modified)->format('U'),
			'calendarid'   => 'dp-' . $event->catid,
			'size'         => strlen($ical),
			'etag'         => '"' . md5($ical) . '"',
			'calendardata' => $ical
		];

		return $data;
	}
}
