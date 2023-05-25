<?php
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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class DPCalendarViewList extends BaseView
{
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
	protected $increment = null;

	public function display($tpl = null)
	{
		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel');
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init()
	{
		Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$this->displayData['format'] = $this->params->get('event_form_date_format', 'd.m.Y');

		$this->returnPage = $this->input->getInt('Itemid', null) ? 'index.php?Itemid=' . $this->input->getInt('Itemid', null) : null;

		$context         = 'com_dpcalendar.listview.filter.';
		$this->params    = $this->state->params;
		$this->increment = $this->params->get('list_increment', '1 month');
		$formShown       = $this->params->get('list_manage_search_form', 1);

		if (!$formShown) {
			$this->getModel()->setState('filter.search', '');
		}

		try {
			$dateStart = $this->dateHelper->getDate($this->params->get('date_start'), true);

			if ($startFromInput = $this->input->get('date-start')) {
				$dateStart = $this->dateHelper->getDate($startFromInput, true);
			}

			$dateEnd = $this->state->get('list.end-date');
			if (!empty($dateEnd)) {
				$dateEnd = $this->dateHelper->getDate($dateEnd, true);
			}

			$this->overrideStartDate = $formShown ? $this->app->getUserStateFromRequest($context . 'start', 'start-date') : null;
			if (!empty($this->overrideStartDate)) {
				$dateStart = DPCalendarHelper::getDateFromString($this->overrideStartDate, null, true);
			}
			$this->overrideEndDate = $formShown ? $this->app->getUserStateFromRequest($context . 'end', 'end-date') : null;
			if (!empty($this->overrideEndDate)) {
				$dateEnd = DPCalendarHelper::getDateFromString($this->overrideEndDate, null, true);
			}
		} catch (Exception $e) {
			$this->app->enqueueMessage($e->getMessage(), 'warning');

			$dateStart               = $this->dateHelper->getDate(null, true);
			$this->overrideStartDate = '';
			$this->overrideEndDate   = '';
		}

		if (empty($dateEnd)) {
			$dateEnd = clone $dateStart;
			$dateEnd->modify('+ ' . $this->increment);
		}

		// Only set time when we are during the day, it will prevent day shifts
		if ($dateStart->format('H:i') != '00:00') {
			$dateStart->setTime(0, 0, 0);
			$dateEnd->setTime(0, 0, 0);
		}

		$this->startDate = clone $dateStart;
		$this->endDate   = clone $dateEnd;

		if (empty($this->overrideEndDate)) {
			// End date is exclusive, so show day before
			$this->endDate->modify('-1 second');
		}

		$dateStart->setTimezone(new DateTimeZone('UTC'));
		$dateEnd->setTimezone(new DateTimeZone('UTC'));

		$this->state->set('list.start-date', $dateStart);
		$this->state->set('list.end-date', $dateEnd);

		// The start value
		$start = clone $dateStart;
		$start->modify('+ ' . $this->increment);

		// The end value
		$end = clone $dateEnd;
		$end->modify('+ ' . $this->increment);

		// The link to the next page
		$this->nextLink = 'index.php?option=com_dpcalendar&view=list&layout=' . $this->getLayout() . '&Itemid=';
		$this->nextLink .= $this->input->getInt('Itemid') . '&date-start=' . $start->format('Y-m-d');
		$this->nextLink = $this->router->route($this->nextLink);

		// Modify the start for the prev link
		$start->modify('- ' . $this->increment);
		$start->modify('- ' . $this->increment);

		// Modify the end for the prev link
		$end->modify('- ' . $this->increment);
		$end->modify('- ' . $this->increment);

		// The link to the prev page
		$this->prevLink = 'index.php?option=com_dpcalendar&view=list&layout=' . $this->getLayout() . '&Itemid=';
		$this->prevLink .= $this->input->getInt('Itemid') . '&date-start=' . $start->format('Y-m-d');
		$this->prevLink = $this->router->route($this->prevLink);

		$model = BaseDatabaseModel::getInstance('Calendar', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();
		$model->setState('filter.parentIds', $this->params->get('ids', '-1'));
		$ids = array_column($model->getItems(), 'id');

		$model = $this->getModel();

		// Initialize variables
		$model->setState('category.id', $ids);
		$model->setState('category.recursive', true);
		$model->setState('filter.featured', $this->params->get('list_filter_featured', 0));
		$model->setState('filter.author', $this->params->get('list_filter_author', 0));
		$model->setState('filter.expand', $this->params->get('list_expand', 1));
		$model->setState('filter.ongoing', $this->params->get('list_include_ongoing', 0));
		$model->setState('filter.state_owner', true);

		$now = $this->dateHelper->getDate();
		$now->setTime(0, 0, 0);
		$model->setState('list.direction', $dateEnd->format('U') < $now->format('U') ? 'desc' : 'asc');
		$model->setState('list.limit', 100000);

		// Location filters
		if ($formShown && $location = $this->app->getUserStateFromRequest($context . 'location', 'location')) {
			$model->setState('filter.location', Location::get($location, false));
			$model->setState('filter.radius', $this->app->getUserStateFromRequest($context . 'radius', 'radius'));
			$model->setState('filter.length-type', $this->app->getUserStateFromRequest($context . 'length-type', 'length-type'));
		}

		$this->state = $model->getState();

		$items = $this->get('Items');
		if ($items === false) {
			throw new Exception(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'));
		}

		PluginHelper::importPlugin('dpcalendar');
		$this->app->triggerEvent('onContentDisplayEventList', ['com_dpcalendar.calendar', $ids, $items]);

		$now = $this->dateHelper->getDate();
		foreach ($items as $event) {
			if (empty($event->rrule) && !empty($event->original_rrule)) {
				$event->rrule = $event->original_rrule;
			}

			$event->text = $event->description ?: '';
			PluginHelper::importPlugin('content');
			$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$event->params, 0]);
			$event->description = $event->text;

			$desc = $this->params->get('list_description_length', null) != '0' ? HTMLHelper::_('content.prepare', $event->description) : '';
			if (!$event->introText && $desc && $this->params->get('list_description_length', null) !== null) {
				$descTruncated = JHtmlString::truncateComplex($desc, $this->params->get('list_description_length', null));

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
		$this->events = $items;
	}
}
