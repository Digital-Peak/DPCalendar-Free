<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\List;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use DigitalPeak\Component\DPCalendar\Site\Model\EventsModel;
use DigitalPeak\Component\DPCalendar\Site\View\CalendarViewTrait;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class HtmlView extends BaseView
{
	use CalendarViewTrait;

	/**
	 * Public variable, also used outside like in YOOtheme.
	 *
	 * @var array
	 */
	public $events = [];

	/** @var string */
	protected $returnPage;

	/** @var string */
	protected $startDate;

	/** @var string */
	protected $endDate;

	/** @var string */
	protected $nextLink;

	/** @var string */
	protected $prevLink;

	/** @var string */
	protected $increment;

	/** @var array */
	protected $calendars;

	public function display($tpl = null): void
	{
		// Add the models
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel(
			'Events',
			'Site',
			['name' => $this->getName() . '.' . Factory::getApplication()->getInput()->getInt('Itemid', 0)]
		);
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		// Load admin language for filters
		$this->app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		// Compile the return page url
		$this->returnPage = $this->input->getInt('Itemid', 0) !== 0 ? 'index.php?Itemid=' . $this->input->getInt('Itemid', 0) : '';

		/** @var EventsModel $model */
		$model = $this->getModel();

		// Create the context
		$context             = 'com_dpcalendar.listview';
		$this->params        = $this->state->get('params');
		$this->increment     = $this->params->get('list_increment', '1 month');
		$this->activeFilters = $model->getActiveFilters();

		// The request data as array
		$listRequestData   = $this->app->getUserStateFromRequest($context . '.list', 'list', '', 'array') ?: [];
		$filterRequestData = $this->app->getUserStateFromRequest($context . '.filter', 'filter', '', 'array') ?: [];
		$formShown         = $this->params->get('list_filter_form', 1);

		// Ensure there is no filter left when the form is not shown
		if (!$formShown) {
			$model->setState('filter.search', '');
		}

		$dateStart         = null;
		$dateEnd           = null;
		$overrideStartDate = null;
		$overrideEndDate   = null;

		try {
			// Override the date from the input, eg. navigation link
			if ($startFromInput = $this->input->get('date-start')) {
				$dateStart = $this->dateHelper->getDate($startFromInput, \strlen((string)$startFromInput) === 10);
			}

			// Define the start date by the request data
			if (!$dateStart && $formShown && $listRequestData && !empty($listRequestData['start-date'])) {
				$overrideStartDate = $listRequestData['start-date'];
				$dateStart         = DPCalendarHelper::getDateFromString($overrideStartDate, null, true);
			}

			// Define the start date from the params
			if (!$dateStart && $paramStart = $this->params->get('list_date_start')) {
				$dateStart = $this->dateHelper->getDate($paramStart);
			}

			// Define the end date by the request data
			if ($formShown && $listRequestData && !empty($listRequestData['end-date'])) {
				$overrideEndDate = $listRequestData['end-date'];
				$dateEnd         = DPCalendarHelper::getDateFromString($overrideEndDate, null, true);
			}

			// Define the start date from the params
			if (!$dateEnd instanceof Date && $paramEnd = $this->params->get('list_date_end')) {
				$dateEnd = $this->dateHelper->getDate($paramEnd);
			}

			// Create the start date with increment
			if ($dateEnd instanceof \DateTime && !$dateStart) {
				$dateStart = clone $dateEnd;
				$dateStart->modify('- ' . $this->increment);
			}

			// Ensure the start date is set
			if (!$dateStart) {
				$dateStart = $this->dateHelper->getDate(null, true);
			}

			// If the start date is today reset the active filters, so the form is hidden
			if (\array_key_exists('start-date', $this->activeFilters)
				&& $this->dateHelper->getDate()->format('Ymd') === $dateStart->format('Ymd')) {
				unset($this->activeFilters['start-date']);
			}
		} catch (\Exception $exception) {
			// Show a warning about the invalid date
			$this->app->enqueueMessage($exception->getMessage(), 'warning');

			// Reset the invalid date
			$dateStart                     = $this->dateHelper->getDate(null, true);
			$dateEnd                       = null;
			$overrideStartDate             = '';
			$overrideEndDate               = '';
			$listRequestData['start-date'] = '';
			$listRequestData['end-date']   = '';
			$this->activeFilters           = $listRequestData;

			// Set an empty user state
			$this->app->setUserState($context . '.list', $listRequestData);
		}

		// When no end date, use the start date with the increment
		if (empty($dateEnd)) {
			$dateEnd = clone $dateStart;
			$dateEnd->modify('+ ' . $this->increment);
		}

		// Only set time when we are during the day, it will prevent day shifts
		if ($dateStart->format('H:i') !== '00:00') {
			$dateStart->setTime(0, 0, 0);
			$dateEnd->setTime(0, 0, 0);
		}

		if (empty($overrideEndDate) && !$this->params->get('list_date_end')) {
			// End date is exclusive, so show day before
			$dateEnd->modify('-1 second');
		}

		// The dates for the view
		$this->startDate = clone $dateStart;
		$this->endDate   = clone $dateEnd;

		// Normalize the dates
		$dateStart->setTimezone(new \DateTimeZone('UTC'));
		$dateEnd->setTimezone(new \DateTimeZone('UTC'));

		// The start value
		$start = clone $dateStart;
		$start->modify('+ ' . $this->increment);

		// The end value
		$end = clone $dateEnd;
		$end->modify('+ ' . $this->increment);

		// The link to the next page
		$this->nextLink = 'index.php?option=com_dpcalendar&view=list&layout=' . $this->getLayout() . '&Itemid=';
		$this->nextLink .= $this->input->getInt('Itemid', 0) . '&date-start=' . $start->format('Y-m-d');
		$this->nextLink = $this->router->route($this->nextLink);

		// Modify the start for the prev link
		$start->modify('- ' . $this->increment);
		$start->modify('- ' . $this->increment);

		// Modify the end for the prev link
		$end->modify('- ' . $this->increment);
		$end->modify('- ' . $this->increment);

		// The link to the prev page
		$this->prevLink = 'index.php?option=com_dpcalendar&view=list&layout=' . $this->getLayout() . '&Itemid=';
		$this->prevLink .= $this->input->getInt('Itemid', 0) . '&date-start=' . $start->format('Y-m-d');
		$this->prevLink = $this->router->route($this->prevLink);

		// Get the calendars and their childs
		$calendarModel = $this->getDPCalendar()->getMVCFactory()->createModel('Calendar', 'Site', ['ignore_request' => true]);
		$calendarModel->getState();
		$calendarModel->setState('filter.parentIds', $this->params->get('ids', '-1'));

		// The calendar ids
		$this->calendars = $calendarModel->getItems();
		foreach ($this->calendars as $calendar) {
			$this->fillCalendar($calendar);
		}

		$ids = array_filter($this->state->get('filter.calendars', [])) !== [] ? array_filter($this->state->get('filter.calendars', [])) : array_keys($this->calendars);

		// Set the dates on the model
		$model->setState('list.start-date', $dateStart);
		$model->setState('list.end-date', $dateEnd);
		$model->setState('category.id', $ids);
		$model->setState('category.recursive', true);
		$model->setState('filter.featured', $this->params->get('list_filter_featured', 0));
		if ($this->params->get('list_filter_author', 0)) {
			$model->setState('filter.author', $this->params->get('list_filter_author', 0));
		}
		$model->setState('filter.expand', $this->params->get('list_expand', 1));
		$model->setState('filter.ongoing', $this->params->get('list_include_ongoing', 0));

		// When no state permission, then show from owner and ensure a state
		if (!$this->getCurrentUser()->authorise('core.edit.state', 'com_dpcalendar')) {
			$model->setState('filter.state_owner', true);
			$model->setState('filter.state', $model->getState('filter.state', [1, 3]));
		}

		// The current start of the day
		$now = $this->dateHelper->getDate();
		$now->setTime(0, 0, 0);

		// Define the lit direction
		$model->setState('list.direction', $this->params->get('list_ordering', 'adaptive'));

		// When adaptive, then order past events desc
		if ($this->params->get('list_ordering', 'adaptive') === 'adaptive') {
			$model->setState('list.direction', $dateEnd->format('U') < $now->format('U') ? 'desc' : 'asc');
		}

		// No limit
		$model->setState('list.limit', 100000);

		// Location filters
		if ($formShown && $location = $filterRequestData['location'] ?? '') {
			$model->setState('filter.location', $this->getDPCalendar()->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($location, false));
			$model->setState('filter.radius', $filterRequestData['radius'] ?? 50);
			$model->setState('filter.length-type', $filterRequestData['length-type'] ?? 'm');
		}

		// Set the new state
		$this->state = $model->getState();

		if ($overrideStartDate === null && \array_key_exists('start-date', $this->activeFilters)) {
			unset($this->activeFilters['start-date']);
		}

		if ($overrideStartDate === null && \array_key_exists('end-date', $this->activeFilters)) {
			unset($this->activeFilters['end-date']);
		}

		$this->prepareForm($this->calendars);

		// Set the dates
		$this->filterForm->setValue('start-date', 'list', $overrideStartDate ? $dateStart : null);
		$this->filterForm->setValue('end-date', 'list', $overrideEndDate ? $dateEnd : null);

		// Location filters
		if ($location = $this->state->get('filter.location')) {
			if (!$location instanceof \stdClass) {
				$location = $this->getDPCalendar()->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($location, false);
			}

			$this->filterForm->setFieldAttribute('location', 'data-latitude', $location->latitude, 'filter');
			$this->filterForm->setFieldAttribute('location', 'data-longitude', $location->longitude, 'filter');
		}

		// Load the events
		$items = $model->getItems();
		if ($items === false) {
			throw new \Exception(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'));
		}

		// Trigger the event
		PluginHelper::importPlugin('dpcalendar');
		$this->app->triggerEvent('onContentDisplayEventList', ['com_dpcalendar.calendar', $ids, $items]);

		$now = $this->dateHelper->getDate();

		/** @var \stdClass $event */
		foreach ($items as $event) {
			// Ensure a rule exists
			if (empty($event->rrule) && !empty($event->original_rrule)) {
				$event->rrule = $event->original_rrule;
			}

			// Prepare like custom fields
			$event->text = $event->description ?: '';
			PluginHelper::importPlugin('content');
			$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$event->params, 0]);
			$event->description = $event->text;

			// Truncate the description and add read more
			$desc = $this->params->get('list_description_length', null) != '0' ? HTMLHelper::_('content.prepare', $event->description) : '';
			if (!$event->introText && $desc && $this->params->get('list_description_length', null) !== null) {
				$event->introText = StringHelper::truncateComplex($desc, $this->params->get('list_description_length', null));

				// Move the dots inside the last tag
				if (DPCalendarHelper::endsWith($event->introText, '...') && $pos = strrpos($event->introText, '</')) {
					$event->introText = trim(substr_replace($event->introText, '...</', $pos, 2), '.');
				}
			}

			if ($event->introText) {
				$event->alternative_readmore = Text::_('COM_DPCALENDAR_READ_MORE');

				// Meta data is handled differently
				$event->introText .= str_replace('itemprop="url"', '', (string)$this->layoutHelper->renderLayout(
					'joomla.content.readmore',
					[
						'item'   => $event,
						'params' => new Registry(['access-view' => true]),
						'link'   => $this->router->getEventRoute($event->id, $event->catid)
					]
				));
			}

			$event->truncatedDescription = $event->introText ?: $desc;

			// Determine if the event is running
			$date = $this->dateHelper->getDate($event->start_date);
			if (!empty($event->series_min_start_date) && !$this->params->get('list_expand', 1)) {
				$date = $this->dateHelper->getDate($event->series_min_start_date);
			}
			$event->ongoing_start_date = $date < $now ? $date : null;

			$date = $this->dateHelper->getDate($event->end_date);
			if (!empty($event->series_min_end_date) && !$this->params->get('list_expand', 1)) {
				$date = $this->dateHelper->getDate($event->series_min_end_date);
			}
			$event->ongoing_end_date = $date > $now ? $date : null;

			// Trigger display events
			if ($this->params->get('list_show_display_events')) {
				$event->displayEvent = new \stdClass();
				$results             = $this->app->triggerEvent(
					'onContentAfterTitle',
					['com_dpcalendar.event', &$event, &$event->params, 0]
				);
				$event->displayEvent->afterDisplayTitle = trim(implode("\n", $results));

				$results = $this->app->triggerEvent(
					'onContentBeforeDisplay',
					['com_dpcalendar.event', &$event, &$event->params, 0]
				);
				$event->displayEvent->beforeDisplayContent = trim(implode("\n", $results));

				$results = $this->app->triggerEvent(
					'onContentAfterDisplay',
					['com_dpcalendar.event', &$event, &$event->params, 0]
				);
				$event->displayEvent->afterDisplayContent = trim(implode("\n", $results));
			}
		}

		// Set the items
		$this->events = $items;
	}
}
