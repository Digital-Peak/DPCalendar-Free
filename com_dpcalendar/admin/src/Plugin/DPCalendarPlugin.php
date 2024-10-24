<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Plugin;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendar;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Extension\DPCalendarComponent;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\ThinHTTP\ClientFactoryAwareInterface;
use DigitalPeak\ThinHTTP\ClientFactoryAwareTrait;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Cache\CacheControllerFactoryAwareInterface;
use Joomla\CMS\Cache\CacheControllerFactoryAwareTrait;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Event\DispatcherInterface;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Parser\Parser;
use Sabre\VObject\Property\ICalendar\DateTime;
use Sabre\VObject\Reader;

/**
 * This is the base class for the DPCalendar plugins.
 */
abstract class DPCalendarPlugin extends CMSPlugin implements ClientFactoryAwareInterface, CacheControllerFactoryAwareInterface
{
	use ClientFactoryAwareTrait;
	use CacheControllerFactoryAwareTrait;

	protected string $identifier;
	protected bool $cachingEnabled      = true;
	protected $autoloadLanguage         = true;
	protected ?array $extCalendarsCache = null;

	public function __construct(DispatcherInterface $dispatcher, array $config = [])
	{
		parent::__construct($dispatcher, $config);
	}

	public function setConfig(array $config): void
	{
		if (isset($config['params'])) {
			$this->params = new Registry($config['params']);
		}

		if (isset($config['name'])) {
			$this->_name = $config['name'];
		}

		if (isset($config['type'])) {
			$this->_type = $config['type'];
		}
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function fetchEvent(string $eventId, string $calendarId): ?\stdClass
	{
		$calendar = $this->getDbCal($calendarId);
		if (!$calendar instanceof ExternalCalendarInterface) {
			return null;
		}

		$eventId = urldecode($eventId);
		$pos     = strrpos($eventId, '_');
		if ($pos === false) {
			return null;
		}

		$s = substr($eventId, $pos + 1);
		if ($s == 0) {
			$uid = substr($eventId, 0, $pos);

			$content = $this->getContent($calendarId, DPCalendarHelper::getDate('2000-01-01'), null, new Registry());

			$cal = Reader::read($content);

			if (is_iterable($cal->VEVENT)) {
				foreach ($cal->VEVENT as $event) {
					if ((string)$event->UID !== $uid) {
						continue;
					}

					return $this->createEventFromIcal($event, $calendarId, [(string)$event->UID => $event]);
				}
			}
		}

		$start = null;
		if (\strlen($s) === 8) {
			$start = DPCalendarHelper::getDate(
				substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2) . ' 00:00',
				true,
				// Set here the timezone when available so it can be converted back correctly
				$calendar->getParams()->get('timezone')
			);
		} else {
			$start = DPCalendarHelper::getDate(
				substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2) . ' ' . substr($s, 8, 2) . ':' . substr($s, 10, 2),
				false
			);
		}
		// Start date must be inclusive, @see VEvent::isInTimeRange
		$start->modify('-1 second');

		$end = clone $start;
		$end->modify('+1 day');

		$tmpEvent = $this->createEvent($eventId, $calendarId);
		foreach ($this->fetchEvents($calendarId, new Registry(), $start, $end) as $event) {
			if ($event->id == $tmpEvent->id) {
				return $event;
			}
		}

		return null;
	}

	/**
	 * The options can have the following parameters:
	 * - filter: Select only events which match the filter
	 * - limit: The amount of events which should be returned
	 * - expand: If recurring events should be expanded
	 * - location: The event must be around this location based on the givn
	 * radius
	 * - radius: Comes into action when a location is set. Defines how close the
	 * events need to be.
	 * - length-type: The length type in kilometers or miles
	 */
	public function fetchEvents(string $calendarId, Registry $options, ?Date $startDate = null, ?Date $endDate = null): array
	{
		$s = $startDate;
		if ($s instanceof Date) {
			$s = clone $startDate;
		}
		$e = $endDate;
		if ($e instanceof Date) {
			$e = clone $endDate;
		}

		DPCalendarHelper::increaseMemoryLimit(100 * 1024 * 1024);

		// Remove any time limit
		@set_time_limit(0);

		$content = $this->getContent($calendarId, $s, $e, $options);
		if ($content === '' || $content === '0') {
			return [];
		}

		$cal = null;
		try {
			/** @var VCalendar $cal */
			$cal = Reader::read($content, Parser::OPTION_IGNORE_INVALID_LINES);
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());
			$this->log('Content is:' . nl2br(substr($content, 0, 200)));

			return [];
		}

		if ($startDate == null) {
			$startDate = DPCalendarHelper::getDate();
		}
		if ($endDate == null) {
			$endDate = DPCalendarHelper::getDate();
			$endDate->modify('+5 year');
		}
		$data = $cal->VEVENT;
		if (empty($data)) {
			return [];
		}

		$originals = [];
		if (is_iterable($cal->VEVENT)) {
			foreach ($cal->VEVENT as $tmp) {
				$originals[] = clone $tmp;
			}
		}

		try {
			if ($options->get('expand', true)) {
				// Needs a timezone reset as we want to work always in UTC
				$startDate->setTimezone(new \DateTimeZone('UTC'));
				$endDate->setTimezone(new \DateTimeZone('UTC'));
				$cal = $cal->expand($startDate, $endDate);
			}
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());

			return [];
		}

		$data = $cal->VEVENT;
		if (empty($data)) {
			return [];
		}

		$tmp = [];
		foreach ($data as $event) {
			$tmp[] = $event;
		}
		$data = $tmp;

		$events       = [];
		$filter       = strtolower((string)$options->get('filter', ''));
		$limit        = $options->get('limit', null);
		$publishDate  = $options->get('publish_date') ? DPCalendarHelper::getDate()->toSql() : null;
		$startSQLDate = $startDate->toSql();
		$endSQLDate   = $endDate->toSql();
		$order        = strtolower((string)$options->get('order', 'asc'));

		$dbCal = $this->getDbCal($calendarId);
		if (!$dbCal instanceof ExternalCalendarInterface) {
			return $events;
		}

		foreach ($data as $event) {
			if ($filter !== '' && $filter !== '0') {
				$string = StringHelper::strtolower($event->SUMMARY) . ' ' . StringHelper::strtolower($event->DESCRIPTION) . ' ' . StringHelper::strtolower($event->LOCATION);
				if (!DPCalendarHelper::matches($string, $filter)) {
					continue;
				}
			}

			$tmpEvent                 = $this->createEventFromIcal($event, $calendarId, $originals);
			$tmpEvent->access_content = $dbCal->getAccessContent();

			if (!$this->matchLocationFilterEvent($tmpEvent, $options)) {
				continue;
			}

			// Check if now is before publish up date
			if ($publishDate && $tmpEvent->publish_up && $tmpEvent->publish_up > $publishDate) {
				continue;
			}

			// Check if now is after publish down date
			if ($publishDate && $tmpEvent->publish_down && $tmpEvent->publish_down < $publishDate) {
				continue;
			}

			// Ignore event when event start date is after end date
			if (!$options->get('expand', true) && $tmpEvent->start_date > $endSQLDate) {
				continue;
			}

			// Ignore event when event end date is before start date and no recurring series
			if (!$options->get('expand', true) && $tmpEvent->rrule === null && $tmpEvent->end_date < $startSQLDate) {
				continue;
			}

			// Ignore event when event end date is before start date
			if (!$options->get('expand', true) && $tmpEvent->rrule !== null) {
				$rule  = new Rule('', $tmpEvent->start_date, $tmpEvent->end_date);
				$parts = $rule->parseString($tmpEvent->rrule);

				// Parser can't handle both
				if (isset($parts['UNTIL']) && isset($parts['COUNT'])) {
					unset($parts['UNTIL']);
				}

				//Only add the date so we have no tz issues
				if (isset($parts['UNTIL'])) {
					$parts['UNTIL'] = substr((string)$parts['UNTIL'], 0, 8);
				}

				// Load the rrule
				$rule->loadFromArray($parts);

				// Create the transformer
				$transformer = (new ArrayTransformer())->transform($rule);

				// Flag if the rule contains an overlapping instance
				$overlaps = false;

				// Flag to indicate if there is an instance before the range
				$before = false;

				// Loop over the instances of the transformer
				foreach ($transformer->toArray() as $recurrence) {
					// The dates
					$start = $recurrence->getStart();
					$end   = $recurrence->getEnd();

					// Check between the range
					if ($start <= $endDate && $end >= $startDate) {
						$overlaps = true;
						break;
					}

					// Set the before flag
					if ($start < $startDate && $end < $startDate) {
						$before = true;
					}

					// When the instance is after and there was one before, set the overlaps flag
					if ($start > $startDate && $end > $startDate && $before) {
						$overlaps = true;
						break;
					}
				}

				$app = $this->getApplication();
				if (!$overlaps && $transformer->count() >= 731 && $app instanceof CMSApplicationInterface) {
					$app->enqueueMessage('The event "' . $tmpEvent->title . '" could not be imported as the recurrence rule range was too big and not all instances could be checked if they are within the date range. Define the recurring rule closer to the import date range and try again.');
				}

				if (!$overlaps) {
					continue;
				}
			}

			$at        = strpos((string)$tmpEvent->id, '@');
			$delimiter = strrpos((string)$tmpEvent->id, '_');
			if ($at !== false && $delimiter !== false) {
				$tmpEvent->id = substr_replace((string)$tmpEvent->id, '', $at, $delimiter - $at);
			}

			$events[] = $tmpEvent;
		}

		usort(
			$events,
			static function ($event1, $event2) use ($order): int {
				$first  = $event1;
				$second = $event2;
				if (strtolower($order) === 'desc') {
					$first  = $event2;
					$second = $event1;
				}
				return strcmp((string)$first->start_date, (string)$second->start_date);
			}
		);

		if (!empty($limit) && \count($events) >= $limit) {
			return \array_slice($events, 0, $limit);
		}

		return $events;
	}

	protected function fetchCalendars(array $calendarIds = []): array
	{
		$app   = $this->getApplication();
		$model = $app;
		if (!$app instanceof CMSApplicationInterface) {
			return [];
		}

		if ($this->extCalendarsCache === null) {
			$model = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Extcalendars', 'Administrator', ['ignore_request' => true]);
			$model->getState();
			$model->setState('filter.plugin', str_replace('dpcalendar_', '', $this->_name));
			$model->setState('filter.state', 1);
			$model->setState('list.limit', -1);
			$model->setState('list.ordering', 'a.ordering');

			$this->extCalendarsCache = $model->getItems();
		}

		$calendars = [];
		foreach ($this->extCalendarsCache as $calendarObject) {
			if ($calendarIds !== [] && !\in_array($calendarObject->id, $calendarIds)) {
				continue;
			}

			$calendar = $this->createCalendar($calendarObject->id, $calendarObject->title, $calendarObject->description ?: '', $calendarObject->color);

			$calendar->setParams($calendarObject->params);
			$calendar->setAccess($calendarObject->access);
			$calendar->setAccessContent($calendarObject->access_content);
			$calendar->setForceColor($calendarObject->color_force);
			$calendar->setSyncDate($calendarObject->sync_date);
			$calendar->setSyncToken($calendarObject->sync_token);

			$calendar->setIcalUrl($this->getIcalUrl($calendar));

			$calendar->getParams()->set('native', $this->params->get('cache', 1) == 2);

			$calendars[] = $calendar;
		}

		return $calendars;
	}

	protected function getContent(string $calendarId, ?Date $startDate = null, ?Date $endDate = null, ?Registry $options = null): string
	{
		$calendar = $this->getDbCal($calendarId);
		if (!$calendar instanceof ExternalCalendarInterface) {
			return '';
		}

		try {
			$content = $this->fetchContent(
				str_replace('webcal://', 'https://', (string)$calendar->getParams()->get('uri')),
				[
					CURLOPT_SSL_VERIFYHOST => $calendar->getParams()->get('ssl_verify') ? 2 : 0,
					CURLOPT_SSL_VERIFYPEER => $calendar->getParams()->get('ssl_verify') ? 1 : 0
				]
			);

			if (!str_contains($content, 'BEGIN:')) {
				throw new \Exception($content);
			}

			$content = str_replace("BEGIN:VCALENDAR\r\n", '', $content);
			$content = str_replace("BEGIN:VCALENDAR\n", '', $content);
			$content = str_replace("\r\nEND:VCALENDAR", '', $content);
			$content = str_replace("\nEND:VCALENDAR", '', $content);

			return "BEGIN:VCALENDAR\n" . $content . "\nEND:VCALENDAR";
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());

			return '';
		}
	}

	/**
	 * Dummy placeholder for plugins which do not support event editing.
	 */
	public function saveEvent(string $eventId, string $calendarId, array $data): bool
	{
		return false;
	}

	/**
	 * Dummy placeholder for plugins which do not support event deleting.
	 */
	public function deleteEvent(string $eventId, string $calendarId): bool
	{
		return false;
	}

	/**
	 * Dummy placeholder for plugins which do not support event editing.
	 */
	public function prepareForm(string $eventId, string $calendarId, Form $form, mixed $data): void
	{
		$this->cleanupFormForEdit($calendarId, $form, $data);
	}

	public function onEventFetch(string $eventId): ?\stdClass
	{
		if (!str_starts_with($eventId, $this->identifier)) {
			return null;
		}

		$params = $this->params;

		// Sometimes it changes the id
		$eventId = str_replace($this->identifier . ':', $this->identifier . '-', $eventId);
		$id      = explode('-', str_replace($this->identifier . '-', '', (string)$eventId), 2);
		if (\count($id) < 2 || !is_numeric($id[0])) {
			return null;
		}

		/** @var CallbackController $cache */
		$cache = $this->getCacheControllerFactory()->createCacheController('callback', ['defaultgroup' => 'plg_' . $this->_type . '_' . $this->_name]);
		$cache->setCaching($params->get('cache', 1) == '1' && $this->cachingEnabled);
		$cache->setLifeTime($params->get('cache_time', 900) / 60);
		$cache->options['locking'] = false;

		try {
			$event = $cache->get(fn ($eventId, $calendarId): ?\stdClass => $this->fetchEvent($eventId, $calendarId), [$id[1], $id[0]]);
			$cache->gc();

			return $event;
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());

			return $this->fetchEvent($id[1], $id[0]);
		}
	}

	public function onEventsFetch(string $calendarId, ?Date $startDate = null, ?Date $endDate = null, ?Registry $options = null): array
	{
		if ($calendarId && !str_starts_with($calendarId, $this->identifier)) {
			return [];
		}

		$params = $this->params;

		$id = str_replace($this->identifier . '-', '', $calendarId !== '' && $calendarId !== '0' ? $calendarId : '');

		/** @var CallbackController $cache */
		$cache = $this->getCacheControllerFactory()->createCacheController('callback', ['defaultgroup' => 'plg_' . $this->_type . '_' . $this->_name]);
		$cache->setCaching($params->get('cache', 1) == '1' && $this->cachingEnabled);
		$cache->setLifeTime($params->get('cache_time', 900) / 60);
		$cache->options['locking'] = false;

		if (!$options instanceof Registry) {
			$options = new Registry();
		}

		if ($startDate instanceof Date) {
			// If now we cache at least for the minute
			$startDate->setTime((int)$startDate->format('H', true), (int)$startDate->format('i'));
		}

		try {
			// Remove the id, when PHP 8.2.5 is obsolete
			$events = $cache->get(
				fn ($calendarId, Registry $options, ?Date $startDate = null, ?Date $endDate = null): array
					=> $this->fetchEvents($calendarId, $options, $startDate, $endDate),
				[$id, $options, $startDate, $endDate],
				md5($id . $startDate . $endDate . serialize($options))
			);
			$cache->gc();
		} catch (\Exception $exception) {
			$this->log($exception->getMessage());

			$events = $this->fetchEvents($id, $options, $startDate, $endDate);
		}

		return $events;
	}

	public function onCalendarsFetch(mixed $calendarIds = null, ?string $type = null): array
	{
		if ($type !== null && $type !== '' && $type !== '0' && $this->identifier !== $type) {
			return [];
		}

		$ids = [];
		if (!empty($calendarIds)) {
			if (!\is_array($calendarIds)) {
				$calendarIds = [$calendarIds];
			}
			foreach ($calendarIds as $calendarId) {
				if (str_starts_with((string)$calendarId, $this->identifier)) {
					$ids[] = (int)str_replace($this->identifier . '-', '', (string)$calendarId);
				}
			}
			if ($ids === []) {
				return [];
			}
		}

		return $this->fetchCalendars($ids);
	}

	/**
	 * This function is called when an external event is going
	 * to be saved.
	 * This function is dependant when a calendar has canEdit or
	 * canCreate set to true.
	 */
	public function onEventSave(array $data): bool
	{
		if (!str_starts_with((string)$data['catid'], $this->identifier . '-')) {
			return false;
		}

		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return false;
		}

		$calendarId = str_replace($this->identifier . '-', '', (string)$data['catid']);

		$calendar = $this->getDbCal($calendarId);
		if (!$calendar instanceof ExternalCalendarInterface) {
			return false;
		}

		if ((!isset($data['id']) || empty($data['id'])) && !$calendar->canCreate()) {
			// No create permission
			$app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

			return false;
		}

		if (isset($data['id']) && $data['id'] && !$calendar->canEdit()) {
			// No edit permission
			$app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

			return false;
		}

		$newEventId = false;
		if (!isset($data['id']) || empty($data['id'])) {
			$newEventId = $this->saveEvent('', $calendarId, $data);
		} else {
			$eventId = $data['id'];
			$eventId = str_replace($this->identifier . ':', $this->identifier . '-', (string)$eventId);
			$id      = explode('-', str_replace($this->identifier . '-', '', $eventId), 2);
			if (\count($id) < 2) {
				return false;
			}

			$newEventId = $this->saveEvent($id[1], $id[0], $data);
		}
		if ($newEventId != false) {
			/** @var CallbackController $cache */
			$cache = $this->getCacheControllerFactory()->createCacheController('callback', ['defaultgroup' => 'plg_' . $this->_type . '_' . $this->_name]);
			$cache->clean();
		}

		return $newEventId;
	}

	/**
	 * This function is called when an external event is going
	 * to be deleted.
	 * This function is dependant when a calendar has canDelete
	 * set to true.
	 */
	public function onEventDelete(string $eventId): bool
	{
		if (!str_starts_with($eventId, $this->identifier . '-')) {
			return false;
		}

		$eventId = str_replace($this->identifier . ':', $this->identifier . '-', $eventId);
		$id      = explode('-', str_replace($this->identifier . '-', '', $eventId), 2);
		if (\count($id) < 2) {
			return false;
		}

		$app      = $this->getApplication();
		$calendar = $this->getDbCal($id[0]);
		if ($calendar instanceof ExternalCalendarInterface && !$calendar->canDelete() && $app instanceof CMSApplicationInterface) {
			$app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');

			return false;
		}

		$success = $this->deleteEvent($id[1], $id[0]);
		if ($success != false) {
			/** @var CallbackController $cache */
			$cache = $this->getCacheControllerFactory()->createCacheController('callback', ['defaultgroup' => 'plg_' . $this->_type . '_' . $this->_name]);
			$cache->clean();
		}

		return $success;
	}

	public function onDPCalendarDoAction(string $action, string $pluginName, mixed $data = null): mixed
	{
		if (str_replace('dpcalendar_', '', $this->_name) !== $pluginName) {
			return null;
		}

		if (!method_exists($this, $action)) {
			return null;
		}

		return $this->$action($data);
	}

	public function onContentPrepareForm(Form $form, mixed $data): void
	{
		if ($form->getName() != 'com_dpcalendar.event') {
			return;
		}

		$data = (object)$data;

		$catId = null;
		if (!empty($data->catid)) {
			$catId = $data->catid;
		}

		// When there is no calendar, then get it from the form
		$formField = $form->getField('catid');
		if (empty($catId) && $formField instanceof FormField) {
			$catId = $formField->getAttribute('default', null);

			// Do not print errors
			$oldErrorHandling = libxml_use_internal_errors(true);
			try {
				// Decode the input first when calendars have accents in titles
				$decoded = html_entity_decode((string)$formField->__get('input'), ENT_QUOTES, 'utf-8');
				$xml     = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?>' . $decoded);

				// Get the available calendars
				$options = $xml->xpath('//option');

				// Choose the first available calendar
				if (!$catId && $options && $firstChoice = reset($options)) {
					$catId = (string)$firstChoice->attributes()->value;
				}
			} catch (\Exception) {
				// Ignore
			}

			// Cleanup
			libxml_clear_errors();
			libxml_use_internal_errors($oldErrorHandling);
		}

		if (!$catId || !str_starts_with((string)$catId, $this->identifier)) {
			return ;
		}

		$eventId = '';
		if (!empty($data->id)) {
			$id      = str_replace($this->identifier . ':', $this->identifier . '-', (string)$data->id);
			$id      = explode('-', str_replace($this->identifier . '-', '', $id), 2);
			$eventId = \count($id) == 2 ? $id[1] : $eventId;
		}

		$id = str_replace($this->identifier . '-', '', (string)$catId);

		$this->prepareForm($eventId, $id, $form, $data);
	}

	protected function createCalendar(string $id, string $title, string $description, ?string $color = '3366CC'): ExternalCalendar
	{
		$user = null;

		$app = $this->getApplication();
		if ($app instanceof CMSApplicationInterface) {
			$user = $app->getIdentity();
		}

		$calendar = new ExternalCalendar($this->identifier . '-' . $id, $title, $user instanceof User ? $user : new User());
		$calendar->setDescription($description);
		$calendar->setColor($color !== null && $color !== '' && $color !== '0' ? $color : '3366CC');
		$calendar->setPluginName($this->_name);
		$calendar->setSystemName($this->identifier);

		return $calendar;
	}

	protected function createEvent(string $id, string $calendarId): \stdClass
	{
		$calendar                                 = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($this->identifier . '-' . $calendarId);
		$event                                    = new \stdClass();
		$event->id                                = $this->identifier . '-' . $calendarId . '-' . $id;
		$event->alias                             = $id;
		$event->catid                             = $this->identifier . '-' . $calendarId;
		$event->category_access                   = 1;
		$event->category_alias                    = $calendarId;
		$event->category_title                    = $calendar instanceof CalendarInterface ? $calendar->getTitle() : '';
		$event->parent_alias                      = '';
		$event->parent_id                         = 0;
		$event->original_id                       = 0;
		$event->title                             = '';
		$event->rrule                             = null;
		$event->exdates                           = null;
		$event->recurrence_id                     = null;
		$event->start_date                        = '';
		$event->end_date                          = '';
		$event->show_end_time                     = 1;
		$event->all_day                           = 0;
		$event->color                             = '';
		$event->url                               = '';
		$event->prices                            = null;
		$event->locations                         = [];
		$event->rooms                             = [];
		$event->roomTitles                        = [];
		$event->hits                              = 0;
		$event->capacity                          = 0;
		$event->capacity_used                     = 0;
		$event->waiting_list_count                = 0;
		$event->booking_options                   = null;
		$event->booking_waiting_list              = 0;
		$event->booking_series                    = 0;
		$event->booking_opening_date              = null;
		$event->booking_closing_date              = null;
		$event->event_booking_cancel_closing_date = null;
		$event->earlybird_discount                = null;
		$event->user_discount                     = null;
		$event->events_discount                   = null;
		$event->tickets_discount                  = null;
		$event->description                       = '';
		$event->schedule                          = '';
		$event->state                             = 1;
		$event->access                            = 1;
		$event->access_content                    = 1;
		$event->language                          = '*';
		$event->created                           = '';
		$event->created_by                        = 0;
		$event->created_by_alias                  = '';
		$event->host_ids                          = '';
		$event->hostContacts                      = [];
		$event->modified                          = '';
		$event->modified_by                       = 0;
		$event->params                            = '';
		$event->metadesc                          = null;
		$event->metakey                           = null;
		$event->metadata                          = new Registry();
		$event->author                            = null;
		$event->xreference                        = $event->id;
		$event->images                            = null;
		$event->checked_out                       = null;
		$event->publish_up                        = null;
		$event->publish_down                      = null;

		return $event;
	}

	private function createEventFromIcal(VEvent $event, string $calendarId, array $originals): \stdClass
	{
		$start = $event->DTSTART;
		if (!$start instanceof DateTime) {
			return new \stdClass();
		}

		$allDay = !$start->hasTime();
		// Microsoft has a special property to flag all day events
		if (isset($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'})) {
			$allDay = strtolower((string)$event->{'X-MICROSOFT-CDO-ALLDAYEVENT'}) === 'true';
		}

		$startDate = DPCalendarHelper::getDate($start->getDateTime()->format('U'), $allDay);

		$endDate = null;
		if ($event->DURATION != null) {
			$endDate  = clone $startDate;
			$duration = DateTimeParser::parseDuration($event->DURATION, true);
			$endDate->modify(\is_string($duration) ? $duration : '');
			if ($allDay) {
				$endDate->modify('-1 day');
			}
		} elseif (!$event->DTEND) {
			$endDate = clone $startDate;
			$endDate->setTime(23, 59, 59);
		} elseif ($event->DTEND instanceof DateTime) {
			$endDate = DPCalendarHelper::getDate($event->DTEND->getDateTime()->format('U'), $allDay);
			if ($allDay) {
				$endDate->modify('-1 day');
			}
		}

		// Search for the original to get the rrule
		$original = null;
		foreach ($originals as $tmp) {
			if ((string)$tmp->UID === (string)$event->UID && $tmp->{'RECURRENCE-ID'} === null && $tmp->RRULE !== null) {
				$original = $tmp;

				if ($event->{'RECURRENCE-ID'} === null
					&& $start->getDateTime()->format('U') === (string)$original->DTSTART->getDateTime()->format('U')
					&& $event->RRULE === null) {
					$event->add('RECURRENCE-ID', (string)$start);
					// @phpstan-ignore-next-line
					$event->{'RECURRENCE-ID'}->parameters = $start->parameters;
				}
				break;
			}
		}

		// Find the override in the originals
		if (!empty($event->{'RECURRENCE-ID'})) {
			$recurrenceDate = $event->{'RECURRENCE-ID'}->getDateTime()->format('U');
			foreach ($originals as $o) {
				if ($recurrenceDate != $o->DTSTART->getDateTime()->format('U')) {
					continue;
				}
				if ((string)$o->UID !== (string)$event->UID) {
					continue;
				}
				if ($o->RRULE !== null) {
					continue;
				}
				$event = $o;
			}
		}

		$id    = 0;
		$recId = $event->{'RECURRENCE-ID'};
		if ($original !== null && $recId === null) {
			$id = $event->UID . '_0';
		} else {
			$id = $event->UID . '_' . ($allDay ? $startDate->format('Ymd') : $startDate->format('YmdHi'));
		}

		$tmpEvent      = $this->createEvent($id, $calendarId);
		$tmpEvent->uid = (string)$event->UID;
		if (!empty($recId)) {
			$tmpEvent->recurrence_id = (string)$recId;
		}
		$tmpEvent->start_date = $startDate->toSql();
		$tmpEvent->end_date   = $endDate instanceof Date ? $endDate->toSql() : $tmpEvent->start_date;

		$title           = (string)$event->SUMMARY;
		$title           = str_replace('\n', ' ', $title);
		$title           = str_replace('\N', ' ', $title);
		$tmpEvent->title = $this->getDPCalendar()->getMVCFactory()->createModel('Ical', 'Administrator')->icalDecode($title);

		$tmpEvent->alias       = ApplicationHelper::stringURLSafe($tmpEvent->title);
		$tmpEvent->description = $this->getDPCalendar()->getMVCFactory()->createModel('Ical', 'Administrator')->icalDecode((string)$event->DESCRIPTION);

		// When no tags exist, then convert new lines to br's
		if (strip_tags((string)$tmpEvent->description) === $tmpEvent->description) {
			$tmpEvent->description = nl2br($tmpEvent->description);
		}

		$created = $event->CREATED;
		if (!empty($created)) {
			$tmpEvent->created = DPCalendarHelper::getDate($created->getDateTime()->format('U'))->toSql();
		}
		$modified = $event->{'LAST-MODIFIED'};
		if (!empty($modified)) {
			$tmpEvent->modified = DPCalendarHelper::getDate($modified->getDateTime()->format('U'))->toSql();
		}

		$description = (string)$event->{'X-ALT-DESC'};
		if ($description !== '' && $description !== '0') {
			$tmpEvent->description = $this->getDPCalendar()->getMVCFactory()->createModel('Ical', 'Administrator')->icalDecode($description);
		}

		$author = (string)$event->ORGANIZER;
		if ($author !== '' && $author !== '0') {
			$tmpEvent->created_by_alias = str_replace('MAILTO:', '', $author);
		}

		if (isset($event->ATTENDEE)) {
			$tmpEvent->bookings = [];
			foreach ($event->ATTENDEE as $child) {
				$booking        = new \stdClass();
				$booking->name  = '';
				$booking->email = str_replace('MAILTO:', '', (string)$child);
				$booking->id    = md5($booking->email);
				foreach ($child->parameters() as $param) {
					if ($param->name == 'CN') {
						$booking->name = (string)$param;
					}
				}
				// A name is at least required
				if ($booking->name === '') {
					continue;
				}
				if ($booking->name === '0') {
					continue;
				}
				$tmpEvent->bookings[] = $booking;
			}
		}

		$calendar = $this->getDbCal($calendarId);

		// Add none standard properties
		$color = (string)$event->{'x-color'};
		if ($color !== '' && $color !== '0' && $calendar instanceof ExternalCalendarInterface && !$calendar->forceColor()) {
			$tmpEvent->color = $color;
		}

		$url = (string)$event->URL;
		if ($url !== '' && $url !== '0') {
			$tmpEvent->url = $url;
		}

		$url = (string)$event->{'x-url'};
		if ($url !== '' && $url !== '0') {
			$tmpEvent->url = $url;
		}

		$alias = (string)$event->{'x-alias'};
		if ($alias !== '' && $alias !== '0') {
			$tmpEvent->alias = $alias;
		}

		$language = (string)$event->{'x-language'};
		if ($language !== '' && $language !== '0') {
			$tmpEvent->language = $language;
		}

		$publishUp = (string)$event->{'x-publish-up'};
		if ($publishUp !== '' && $publishUp !== '0') {
			$tmpEvent->publish_up = $publishUp;
		}

		$publishDown = (string)$event->{'x-publish-down'};
		if ($publishDown !== '' && $publishDown !== '0') {
			$tmpEvent->publish_down = $publishDown;
		}

		$tmpEvent->images = new \stdClass();
		$tmpImageData     = (string)$event->{'x-image'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_full = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-full'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_full = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-full-alt'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_full_alt = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-full-caption'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_full_caption = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-intro'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_intro = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-intro-alt'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_intro_alt = $tmpImageData;
		}
		$tmpImageData = (string)$event->{'x-image-intro-caption'};
		if ($tmpImageData !== '' && $tmpImageData !== '0') {
			$tmpEvent->images->image_intro_caption = $tmpImageData;
		}

		$showEndTime = $event->{'x-show-end-time'};
		if ($showEndTime) {
			$tmpEvent->show_end_time = (string)$showEndTime === '1' ? 1 : 0;
		}

		if ($event->ATTACH) {
			foreach ($event->ATTACH as $attachment) {
				if (!$attachment->parameters || !\array_key_exists('FMTTYPE', $attachment->parameters)) {
					continue;
				}

				if (!str_starts_with((string)$attachment->parameters['FMTTYPE']->getValue(), 'image/')) {
					continue;
				}

				$tmpEvent->images = (object)['image_full' => $attachment->getValue(), 'image_intro' => $attachment->getValue()];
			}
		}

		$location  = (string)$event->LOCATION;
		$locations = [];
		if ($location !== '' && $location !== '0') {
			$geo = (string)$event->GEO;
			if ($geo !== '' && $geo !== '0' && str_contains($geo, ';')) {
				static $locationModel = null;
				$app                  = $this->getApplication();
				if ($locationModel === null && $app instanceof CMSWebApplicationInterface) {
					$locationModel = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
					$locationModel->getState();
					$locationModel->setState('list.limit', 1);
				}
				[$latitude, $longitude] = explode(';', $geo);
				$locationModel->setState('filter.latitude', $latitude);
				$locationModel->setState('filter.longitude', $longitude);

				$tmp = $locationModel->getItems();
				if (!empty($tmp)) {
					$locations = $tmp;

					$tmpEvent->location_ids = [];
					foreach ($tmp as $dpLocation) {
						if ($dpLocation->state != 1) {
							continue;
						}
						$tmpEvent->location_ids[] = $dpLocation->id;
					}
				} else {
					[$latitude, $longitude] = explode(';', $geo);

					$locations[] = $this->getDPCalendar()->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($latitude . ',' . $longitude, true, $location);
				}
			} else {
				$locations[] = $this->getDPCalendar()->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($this->getDPCalendar()->getMVCFactory()->createModel('Ical', 'Administrator')->icalDecode($location));
			}
		}
		$tmpEvent->locations = array_filter($locations, static fn ($location): bool => (int)$location->state === 1);
		$tmpEvent->all_day   = $allDay ? 1 : 0;

		if ($original !== null && $recId !== null) {
			$tmpEvent->original_id = $this->identifier . '-' . $calendarId . '-' . $event->UID . '_0';
		}
		if ($original !== null && $recId === null) {
			$tmpEvent->rrule       = (string)$original->RRULE;
			$tmpEvent->original_id = -1;
		}

		return $tmpEvent;
	}

	protected function getDbCal(string $calendarId): ?ExternalCalendarInterface
	{
		$calendars = $this->fetchCalendars([$calendarId]);
		if ($calendars === []) {
			return null;
		}

		return $calendars[0];
	}

	protected function getIcalUrl(ExternalCalendarInterface $calendar): string
	{
		return '';
	}

	protected function replaceNl(string $text, ?string $replace = ''): string
	{
		return str_replace(["\r\n", "\r", "\n"], $replace !== null && $replace !== '' && $replace !== '0' ? $replace : '', $text);
	}

	protected function log(string $message): void
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return;
		}

		$app->enqueueMessage($message, 'warning');
	}

	protected function matchLocationFilterEvent(\stdClass $event, Registry $options): bool
	{
		if ($options->get('radius') == -1) {
			return true;
		}

		$location    = $options->get('location');
		$locationIds = $options->get('location_ids', []);
		if (empty($location) && empty($locationIds)) {
			return true;
		}

		$locationFilterData            = new \stdClass();
		$locationFilterData->latitude  = 0;
		$locationFilterData->longitude = 0;

		if ($location instanceof \stdClass) {
			$locationFilterData = $location;
		}

		$radius = $options->get('radius');
		if ($options->get('length-type') == 'm') {
			$radius *= 0.62137119;
		}

		$model = $this->getDPCalendar()->getMVCFactory()->createModel('Geo', 'Administrator');

		if (!$locationFilterData->latitude
			&& \is_string($location) && str_contains($location, 'latitude=') && str_contains($location, 'longitude=')) {
			[$latitude, $longitude]        = explode(';', $location);
			$locationFilterData->latitude  = str_replace('latitude=', '', $latitude);
			$locationFilterData->longitude = str_replace('longitude=', '', $longitude);
		} elseif (!$locationFilterData->latitude && !empty($location) && \is_string($location)) {
			$locationFilterData = $model->getLocation($location);
		}

		$within = false;
		foreach ($event->locations as $loc) {
			if (!\in_array($loc->id, $locationIds)
				&& !$model->within($loc, $locationFilterData->latitude, $locationFilterData->longitude, $radius)) {
				continue;
			}
			$within = true;
			break;
		}

		return $within;
	}

	protected function cleanupFormForEdit(string $calendarId, Form $form, mixed $data): void
	{
		$hideFieldsets             = [];
		$hideFieldsets['params']   = 'jbasic';
		$hideFieldsets[]           = 'booking';
		$hideFieldsets[]           = 'publishing';
		$hideFieldsets['metadata'] = 'jmetadata';

		foreach ($hideFieldsets as $group => $name) {
			foreach ($form->getFieldset($name) as $field) {
				if (!\is_string($group)) {
					$group = null;
				}

				if ($field->fieldname == 'xreference') {
					continue;
				}

				$form->removeField(DPCalendarHelper::getFieldName($field), $group);
			}
		}

		$form->removeField('show_end_time');
		$form->removeField('alias');
		$form->removeField('state');
		$form->removeField('schedule');
		$form->removeField('publish_up');
		$form->removeField('publish_down');
		$form->removeField('access');
		$form->removeField('featured');
		$form->removeField('access_content');
		$form->removeField('language');
		$form->removeField('metadesc');
		$form->removeField('metakey');
		$form->removeField('price');
		$form->removeField('earlybird_discount');
		$form->removeField('image_intro', 'images');
		$form->removeField('image_intro_alt', 'images');
		$form->removeField('image_intro_caption', 'images');
		$form->removeField('spacer1', 'images');
		$form->removeField('image_full', 'images');
		$form->removeField('image_full_alt', 'images');
		$form->removeField('image_full_caption', 'images');
		$form->removeField('rooms');
		$form->setFieldAttribute('location_ids', 'multiple', false);

		foreach ($form->getGroup('com_fields') as $item) {
			$form->removeField($item->fieldname, $item->group);
		}
	}

	protected function fetchContent(string $uri, array $curlOptions = []): string
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return '';
		}

		if ($uri === '' || $uri === '0') {
			return '';
		}

		$internal = !filter_var($uri, FILTER_VALIDATE_URL);
		if ($internal && !str_starts_with($uri, '/')) {
			$uri = JPATH_ROOT . '/' . $uri;
		}

		if ($internal && is_dir($uri)) {
			$content = '';
			foreach (Folder::files($uri, '\.ics', true, true) as $file) {
				$content .= file_get_contents($file);
			}

			return $content;
		}

		if ($internal) {
			return file_get_contents($uri) ?: '';
		}

		$u   = Uri::getInstance($uri);
		$uri = $u->toString(['scheme', 'user', 'pass', 'host', 'port', 'path']);
		$uri .= $u->toString(['query', 'fragment']);

		$user    = $app->getIdentity();
		$headers = [
			'Accept-Language: ' . ($user ? $user->getParam('language', $app->getLanguage()->getTag()) : '*'),
			'Accept: */*'
		];
		$data = $this->getClientFactory()->create()->get($uri, null, null, $headers, $curlOptions);
		if (!empty($data->dp->headers['Content-Encoding']) && $data->dp->headers['Content-Encoding'] == 'gzip' && !empty($data->dp->body)) {
			return gzinflate(substr((string)$data->dp->body, 10, -8)) ?: '';
		}

		return $data->dp->body;
	}

	protected function getDPCalendar(): DPCalendarComponent
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			throw new \Exception('App not set in ' . self::class);
		}

		return $app->bootComponent('dpcalendar');
	}
}
