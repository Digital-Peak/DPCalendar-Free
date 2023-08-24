<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewCalendar extends BaseView
{
	public function init()
	{
		$items = $this->get('AllItems');

		if ($items === false) {
			return $this->setError(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'));
		}

		$this->items = $items;

		$selectedCalendars = [];
		foreach ($items as $calendar) {
			$selectedCalendars[] = $calendar->id;
			$this->fillCalendar($calendar);
		}
		$this->selectedCalendars = $selectedCalendars;

		$doNotListCalendars = [];
		foreach ($this->params->get('idsdnl', []) as $id) {
			$parent = DPCalendarHelper::getCalendar($id);
			if ($parent == null) {
				continue;
			}
			$this->fillCalendar($parent);

			if ($parent->id != 'root') {
				$doNotListCalendars[$parent->id] = $parent;
			}

			if (!$parent->external) {
				foreach ($parent->getChildren(true) as $child) {
					$doNotListCalendars[$child->id] = DPCalendarHelper::getCalendar($child->id);
					$this->fillCalendar($child);
				}
			}
		}

		// If none are selected, use selected calendars
		$this->doNotListCalendars = empty($doNotListCalendars) ? $this->items : $doNotListCalendars;

		$this->quickaddForm = $this->getModel()->getQuickAddForm($this->params);

		$this->resources = [];
		if ($this->params->get('calendar_filter_locations')
			&& $this->params->get('calendar_resource_views')
			&& $this->getLayout() == 'default'
			&& !\DPCalendar\Helper\DPCalendarHelper::isFree()) {
			// Load the model
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
			$model->getState();
			$model->setState('list.limit', 10000);
			$model->setState('filter.search', 'ids:' . implode(',', $this->params->get('calendar_filter_locations')));

			// Add the locations
			foreach ($model->getItems() as $location) {
				if ($location->rooms) {
					foreach ($location->rooms as $room) {
						$this->resources[] = (object)['id' => $location->id . '-' . $room->id, 'title' => $room->title];
					}
				}
			}
		}

		if (strpos($this->getLayout(), 'timeline') !== false) {
			$this->resources = array_values($this->doNotListCalendars);
		}

		$this->returnPage = $this->input->getInt('Itemid', null) ? 'index.php?Itemid=' . $this->input->getInt('Itemid', null) : null;

		return parent::init();
	}

	private function fillCalendar($calendar)
	{
		if (isset($calendar->event)) {
			return;
		}

		$calendar->event = new \stdClass();

		// For some plugins
		!empty($calendar->description) ? $calendar->text = $calendar->description : $calendar->text = null;

		$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.categories', &$calendar, &$calendar->params, 0]);

		$results = $this->app->triggerEvent(
			'onContentAfterTitle',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentBeforeDisplay',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->beforeDisplayContent = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentAfterDisplay',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->afterDisplayContent = trim(implode("\n", $results));

		if ($calendar->text) {
			$calendar->description = $calendar->text;
		}
	}
}
