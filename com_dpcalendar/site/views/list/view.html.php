<?php

use Joomla\CMS\HTML\Helpers\StringHelper;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\Location;
use DPCalendar\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class DPCalendarViewList extends BaseView
{
	/** @var string */
	public $returnPage;
	public $startDate;
	public $endDate;
	public $nextLink;
	public $prevLink;

	/**
	 * Events of the view
	 *
	 * @var array
	 */
	public $events = [];

	/**
	 * The increment property
	 *
	 * @var string
	 */
	protected $increment;

	public function display($tpl = null): void
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');

		// Add the models
		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['name' => 'listview']);
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init()
	{
		// Load admin language for filters
		Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		// Compile the return page url
		$this->returnPage = $this->input->getInt('Itemid', 0) ? 'index.php?Itemid=' . $this->input->getInt('Itemid', 0) : null;

		// Create the context
		$context             = 'com_dpcalendar.listview';
		$this->params        = $this->state->params;
		$this->increment     = $this->params->get('list_increment', '1 month');
		$this->activeFilters = $this->get('ActiveFilters');

		// The request data as array
		$listRequestData   = $this->app->getUserStateFromRequest($context . '.list', 'list', [], 'array');
		$filterRequestData = $this->app->getUserStateFromRequest($context . '.filter', 'filter', [], 'array');
		$formShown         = $this->params->get('list_manage_search_form', 1);

		// Ensure there is no filter left when the form is not shown
		if (!$formShown) {
			$this->getModel()->setState('filter.search', '');
		}

		$dateStart         = null;
		$dateEnd           = null;
		$overrideStartDate = null;
		$overrideEndDate   = null;

		try {
			// Override the date from the input, eg. navigation link
			if ($startFromInput = $this->input->get('date-start')) {
				$dateStart = $this->dateHelper->getDate($startFromInput);
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
			if (!$dateEnd && $paramEnd = $this->params->get('list_date_end')) {
				$dateEnd = $this->dateHelper->getDate($paramEnd);
			}

			// Create the start date with increment
			if ($dateEnd instanceof DateTime && !$dateStart) {
				$dateStart = clone $dateEnd;
				$dateStart->modify('- ' . $this->increment);
			}

			// Ensure the start date is set
			if (!$dateStart) {
				$dateStart = $this->dateHelper->getDate(null, true);
			}

			// If the start date is today reset the active filters, so the form is hidden
			if (array_key_exists('start-date', $this->activeFilters)
				&& $this->dateHelper->getDate()->format('Ymd') === $dateStart->format('Ymd')) {
				unset($this->activeFilters['start-date']);
			}
		} catch (Exception $exception) {
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

		if (array_key_exists('radius', $this->activeFilters) && 50 == $this->activeFilters['radius']) {
			unset($this->activeFilters['radius']);
		}

		if (array_key_exists('length-type', $this->activeFilters) && 'm' === $this->activeFilters['length-type']) {
			unset($this->activeFilters['length-type']);
		}

		if (array_key_exists('calendars', $this->activeFilters) && array_filter($this->activeFilters['calendars'], static fn ($c): bool => !empty($c)) === []) {
			unset($this->activeFilters['calendars']);
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

		// The dates for the view
		$this->startDate = clone $dateStart;
		$this->endDate   = clone $dateEnd;

		if (empty($overrideEndDate) && !$this->params->get('list_date_end')) {
			// End date is exclusive, so show day before
			$this->endDate->modify('-1 second');
		}

		// Normalize the dates
		$dateStart->setTimezone(new DateTimeZone('UTC'));
		$dateEnd->setTimezone(new DateTimeZone('UTC'));

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
		$model = BaseDatabaseModel::getInstance('Calendar', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();

		$calendars = array_filter($this->state->get('filter.calendars', []), static fn ($c): bool => !empty($c));
		if ($calendars === []) {
			$calendars = $this->params->get('ids', '-1');
		}
		$model->setState('filter.parentIds', $calendars);

		// The calendar ids
		$ids = array_column($model->getItems(), 'id');

		// The model to fetch the events
		$model = $this->getModel();

		// Initialize variables

		// Set the dates on the model
		$model->setState('list.start-date', $dateStart);
		$model->setState('list.end-date', $dateEnd);
		$model->setState('category.id', $ids);
		$model->setState('category.recursive', true);
		$model->setState('filter.featured', $this->params->get('list_filter_featured', 0));
		if ($this->params->get('list_filter_author', 0)) {
			$model->setState('filter.author', $this->params->get('list_filter_author', 0) ?: null);
		}
		$model->setState('filter.expand', $this->params->get('list_expand', 1));
		$model->setState('filter.ongoing', $this->params->get('list_include_ongoing', 0));
		$model->setState('filter.state_owner', true);

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
			$model->setState('filter.location', Location::get($location, false));
			$model->setState('filter.radius', $filterRequestData['radius'] ?? 50);
			$model->setState('filter.length-type', $filterRequestData['length-type'] ?? 'm');
		}

		// Load the filter form
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/fields');
		$this->filterForm = $this->get('FilterForm');

		// Remove not needed fields
		$this->filterForm->removeField('event_type', 'filter');
		$this->filterForm->removeField('state', 'filter');
		$this->filterForm->removeField('access', 'filter');
		$this->filterForm->removeField('tag', 'filter');
		$this->filterForm->removeField('language', 'filter');
		$this->filterForm->removeField('level', 'filter');
		$this->filterForm->removeField('fullordering', 'list');
		$this->filterForm->removeField('limit', 'list');

		// Enable autocomplete when configured
		$this->filterForm->setFieldAttribute('location', 'data-dp-autocomplete', $this->params->get('list_autocomplete', 1), 'filter');
		if (DPCalendarHelper::isJoomlaVersion('4', '<')) {
			$this->dpdocument->addScriptOptions('view.list.autocomplete', $this->params->get('list_autocomplete', 1));
		}

		// Set the dates
		$this->filterForm->setValue('start-date', 'list', $overrideStartDate ? $dateStart : null);
		$this->filterForm->setValue('end-date', 'list', $overrideEndDate ? $dateEnd : null);

		// Set the date formats
		$this->filterForm->setFieldAttribute('start-date', 'format', $this->params->get('event_form_date_format', 'd.m.Y'), 'list');
		$this->filterForm->setFieldAttribute('end-date', 'format', $this->params->get('event_form_date_format', 'd.m.Y'), 'list');

		$this->filterForm->setFieldAttribute('calendars', 'ids', implode(',', (array)$this->params->get('ids', ['-1'])), 'filter');

		// Remove the not needed fields
		foreach ($this->params->get('list_search_form_hidden_fields', []) as $field) {
			$this->filterForm->removeField($field, 'filter');
			$this->filterForm->removeField($field, 'list');

			if ($field === 'location') {
				$this->filterForm->removeField('radius', 'filter');
				$this->filterForm->removeField('length-type', 'filter');
			}
		}

		if ($this->params->get('list_filter_author', 0)) {
			$this->filterForm->removeField('created_by', 'filter');
		}

		// When there is a location set, use the proper coordinates
		if ($loc = $this->state->get('filter.location')) {
			$this->filterForm->setFieldAttribute('location', 'data-latitude', $loc->latitude, 'filter');
			$this->filterForm->setFieldAttribute('location', 'data-longitude', $loc->longitude, 'filter');

			if (DPCalendarHelper::isJoomlaVersion('4', '<')) {
				$this->dpdocument->addScriptOptions('view.list.location', $loc);
			}
		}

		// Set the new state
		$this->state = $model->getState();

		// Load the events
		$items = $this->get('Items');
		if ($items === false) {
			throw new Exception(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'));
		}

		// Trigger the event
		PluginHelper::importPlugin('dpcalendar');
		$this->app->triggerEvent('onContentDisplayEventList', ['com_dpcalendar.calendar', $ids, $items]);

		$now = $this->dateHelper->getDate();

		// Load the events
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
				$descTruncated = StringHelper::truncateComplex($desc, $this->params->get('list_description_length', null));

				// Move the dots inside the last tag
				if (DPCalendarHelper::endsWith($descTruncated, '...') && $pos = strrpos($descTruncated, '</')) {
					$descTruncated = trim(substr_replace($descTruncated, '...</', $pos, 2), '.');
				}

				if ($desc != $descTruncated) {
					$event->alternative_readmore = Text::_('COM_DPCALENDAR_READ_MORE');

					// Meta data is handled differently
					$desc = str_replace('itemprop="url"', '', $this->layoutHelper->renderLayout(
						'joomla.content.readmore',
						[
							'item'   => $event,
							'params' => new Registry(['access-view' => true]),
							'link'   => $this->router->getEventRoute($event->id, $event->catid)
						]
					));

					$desc = $descTruncated . $desc;
				}
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
				$event->displayEvent = new stdClass();
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
